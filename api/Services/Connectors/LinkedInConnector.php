<?php
namespace Services\Connectors;

use Services\Http;

/**
 * LinkedIn: publica contenido del Content Studio en una página de empresa o en
 * un perfil, y verifica la sesión. Usa la Posts API (REST) con un access token
 * OAuth (scope w_organization_social para páginas, w_member_social para perfil).
 * El token vence (~60 días): cuando falle, se renueva desde LinkedIn Developers.
 */
final class LinkedInConnector
{
    private const VERSION = '202409';

    private static function headers(string $token): array
    {
        return [
            'Authorization: Bearer ' . $token,
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: ' . self::VERSION,
        ];
    }

    /**
     * Publica un texto (y opcionalmente un enlace) como el autor configurado.
     * @return array{ok:bool,message?:string,error?:string,id?:string}
     */
    public static function publish(string $texto, ?string $url = null): array
    {
        $cfg = ConnectorRegistry::config('linkedin');
        $token = $cfg['access_token'] ?? '';
        $autor = $cfg['author_urn'] ?? '';
        if ($token === '' || $autor === '') {
            return ['ok' => false, 'error' => 'Configure el access token y el URN del autor en LinkedIn.'];
        }

        $post = [
            'author' => $autor,
            'commentary' => $texto,
            'visibility' => 'PUBLIC',
            'distribution' => ['feedDistribution' => 'MAIN_FEED', 'targetEntities' => [], 'thirdPartyDistributionChannels' => []],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];
        // Enlace como tarjeta del artículo (LinkedIn genera la vista previa).
        if ($url) {
            $post['content'] = ['article' => ['source' => $url]];
        }

        $r = Http::json('POST', 'https://api.linkedin.com/rest/posts', $post, self::headers($token));
        if ($r['status'] >= 200 && $r['status'] < 300) {
            // El id de la publicación viene en la cabecera x-restli-id (no en el body).
            return ['ok' => true, 'message' => 'Publicado en LinkedIn.', 'id' => $r['body']['id'] ?? ''];
        }
        $msg = is_array($r['body']) ? ($r['body']['message'] ?? '') : (string) $r['body'];
        return ['ok' => false, 'error' => 'LinkedIn respondió ' . $r['status'] . ($msg ? ': ' . $msg : '') . '. Verifica token, scope y URN.'];
    }

    public static function test(): array
    {
        $cfg = ConnectorRegistry::config('linkedin');
        $token = $cfg['access_token'] ?? '';
        if ($token === '') { return ['ok' => false, 'error' => 'Configure el access token de LinkedIn.']; }

        // /userinfo (OpenID) valida el token sin requerir scopes de escritura.
        $r = Http::json('GET', 'https://api.linkedin.com/v2/userinfo', null, ['Authorization: Bearer ' . $token]);
        if ($r['status'] === 200 && ! empty($r['body']['sub'])) {
            $nombre = $r['body']['name'] ?? 'sesión activa';
            $autor = ($cfg['author_urn'] ?? '') !== '' ? ' · autor: ' . $cfg['author_urn'] : ' · falta configurar el URN del autor';
            return ['ok' => true, 'message' => 'LinkedIn conectado (' . $nombre . ')' . $autor];
        }
        return ['ok' => false, 'error' => 'Token inválido o vencido (LinkedIn ' . $r['status'] . '). Genera uno nuevo en LinkedIn Developers.'];
    }
}
