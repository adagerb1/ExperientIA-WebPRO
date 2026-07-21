<?php
namespace Services\Connectors;

use Core\Database;
use Services\Http;

/**
 * Google Business Profile (antes Google My Business): gestiona la ficha de la
 * empresa en Google/Maps. Con este conector ExperientIA puede:
 *   • Leer las reseñas y su calificación (prueba social para el sitio).
 *   • Responder reseñas (AlexIA puede redactar el borrador).
 *   • Publicar novedades/ofertas (Google Posts) en la ficha.
 *   • Consultar la cuenta y ubicaciones.
 * OAuth (scope business.manage) con refresh token, igual patrón que Calendar.
 */
final class GoogleBusinessConnector
{
    private const REVIEWS_BASE = 'https://mybusiness.googleapis.com/v4';

    private static function accessToken(array $cfg): ?string
    {
        if (empty($cfg['refresh_token'])) { return null; }
        $r = Http::form('POST', 'https://oauth2.googleapis.com/token', [
            'client_id' => $cfg['client_id'] ?? '', 'client_secret' => $cfg['client_secret'] ?? '',
            'refresh_token' => $cfg['refresh_token'], 'grant_type' => 'refresh_token',
        ]);
        return $r['body']['access_token'] ?? null;
    }

    /** account/location en formato "accounts/123/locations/456". */
    private static function resource(array $cfg): string
    {
        $acc = trim((string) ($cfg['account_id'] ?? ''), '/');
        $loc = trim((string) ($cfg['location_id'] ?? ''), '/');
        return $acc . '/' . $loc;
    }

    public static function test(): array
    {
        $cfg = ConnectorRegistry::config('google_business');
        if (empty($cfg['refresh_token'])) { return ['ok' => false, 'error' => 'Configure el refresh token de Google (scope business.manage).']; }
        $token = self::accessToken($cfg);
        if (! $token) { return ['ok' => false, 'error' => 'No se pudo renovar el token de Google.']; }
        // Verifica que la cuenta responda (Account Management API).
        $r = Http::json('GET', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', null, ['Authorization: Bearer ' . $token]);
        if ($r['status'] === 200) {
            $n = count($r['body']['accounts'] ?? []);
            return ['ok' => true, 'message' => 'Google Business conectado (' . $n . ' cuenta(s) accesibles).'];
        }
        return ['ok' => false, 'error' => 'Autenticación válida, pero la API respondió ' . $r['status'] . '. Verifica que la API de Business Profile esté habilitada y con acceso aprobado.'];
    }

    /** Descarga las reseñas y las guarda en gb_reviews. Devuelve resumen. */
    public static function syncReviews(): array
    {
        $cfg = ConnectorRegistry::config('google_business');
        $token = self::accessToken($cfg);
        if (! $token) { return ['ok' => false, 'error' => 'Configure Google Business (refresh token).']; }
        $res = self::resource($cfg);
        if (trim($res, '/') === '') { return ['ok' => false, 'error' => 'Configure el Account ID y el Location ID.']; }

        $r = Http::json('GET', self::REVIEWS_BASE . '/' . $res . '/reviews', null, ['Authorization: Bearer ' . $token]);
        if ($r['status'] !== 200 || ! is_array($r['body'])) {
            return ['ok' => false, 'error' => 'No se pudieron leer las reseñas (Google ' . $r['status'] . ').'];
        }
        $estrellas = ['STAR_RATING_UNSPECIFIED' => 0, 'ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
        $reviews = $r['body']['reviews'] ?? [];
        $n = 0;
        foreach ($reviews as $rv) {
            $stars = $estrellas[$rv['starRating'] ?? ''] ?? 0;
            self::guardar([
                'review_id' => $rv['reviewId'] ?? ($rv['name'] ?? ''),
                'name' => $rv['name'] ?? '',
                'author' => $rv['reviewer']['displayName'] ?? 'Anónimo',
                'stars' => $stars,
                'comment' => $rv['comment'] ?? '',
                'reply' => $rv['reviewReply']['comment'] ?? null,
                'created_at' => isset($rv['createTime']) ? gmdate('Y-m-d H:i:s', strtotime($rv['createTime'])) : now_utc(),
            ]);
            $n++;
        }
        $avg = $r['body']['averageRating'] ?? null;
        return ['ok' => true, 'message' => $n . ' reseña(s) sincronizada(s)' . ($avg ? ' · promedio ' . round($avg, 1) . '★' : '') . '.'];
    }

    /** Responde una reseña (reviewName completo devuelto por la API). */
    public static function replyReview(string $reviewName, string $comment): array
    {
        $cfg = ConnectorRegistry::config('google_business');
        $token = self::accessToken($cfg);
        if (! $token) { return ['ok' => false, 'error' => 'Configure Google Business.']; }
        $r = Http::json('PUT', self::REVIEWS_BASE . '/' . ltrim($reviewName, '/') . '/reply', ['comment' => $comment], ['Authorization: Bearer ' . $token]);
        return ($r['status'] >= 200 && $r['status'] < 300)
            ? ['ok' => true, 'message' => 'Respuesta publicada.']
            : ['ok' => false, 'error' => 'No se pudo responder (Google ' . $r['status'] . ').'];
    }

    /** Publica una novedad (Google Post) en la ficha, opcional con enlace/CTA. */
    public static function localPost(string $texto, ?string $url = null): array
    {
        $cfg = ConnectorRegistry::config('google_business');
        $token = self::accessToken($cfg);
        if (! $token) { return ['ok' => false, 'error' => 'Configure Google Business.']; }
        $res = self::resource($cfg);
        $body = ['languageCode' => 'es', 'summary' => mb_substr($texto, 0, 1500), 'topicType' => 'STANDARD'];
        if ($url) { $body['callToAction'] = ['actionType' => 'LEARN_MORE', 'url' => $url]; }
        $r = Http::json('POST', self::REVIEWS_BASE . '/' . $res . '/localPosts', $body, ['Authorization: Bearer ' . $token]);
        return ($r['status'] >= 200 && $r['status'] < 300)
            ? ['ok' => true, 'message' => 'Publicación creada en Google Business.']
            : ['ok' => false, 'error' => 'No se pudo publicar (Google ' . $r['status'] . ').'];
    }

    private static function guardar(array $r): void
    {
        try {
            $sql = Database::isSqlite()
                ? 'INSERT INTO gb_reviews (review_id, name, author, stars, comment, reply, created_at, synced_at) VALUES (?,?,?,?,?,?,?,?) ON CONFLICT(review_id) DO UPDATE SET stars=excluded.stars, comment=excluded.comment, reply=excluded.reply, synced_at=excluded.synced_at'
                : 'INSERT INTO gb_reviews (review_id, name, author, stars, comment, reply, created_at, synced_at) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE stars=VALUES(stars), comment=VALUES(comment), reply=VALUES(reply), synced_at=VALUES(synced_at)';
            Database::pdo()->prepare($sql)->execute([$r['review_id'], $r['name'], $r['author'], $r['stars'], $r['comment'], $r['reply'], $r['created_at'], now_utc()]);
        } catch (\Throwable $e) { /* tabla sin migrar */ }
    }
}
