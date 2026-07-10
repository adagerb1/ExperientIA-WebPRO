<?php
namespace Services\Connectors;

use Services\Http;
use Core\Database;

/**
 * Pasarelas de pago: Wompi, ePayco, Stripe, PayPal.
 * Crea intenciones de pago y verifica estados. Los webhooks confirman.
 */
final class PaymentConnector
{
    /** Crea una orden de pago y devuelve datos para el checkout del frontend. */
    public static function createCheckout(string $provider, array $order): array
    {
        $ref = $order['reference'] ?? ('EXP-' . strtoupper(bin2hex(random_bytes(5))));
        $amount = (int) $order['amount'];       // centavos
        $currency = $order['currency'] ?? 'COP';

        Database::run('INSERT INTO payments (lead_id, provider, reference, amount, currency, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)',
            [$order['lead_id'] ?? null, $provider, $ref, $amount, $currency, 'pendiente', now_utc(), now_utc()]);

        return match ($provider) {
            'stripe' => self::stripe($amount, $currency, $ref, $order),
            'wompi' => self::wompi($amount, $currency, $ref, $order),
            'paypal' => self::paypal($amount, $currency, $ref, $order),
            'epayco' => self::epayco($amount, $currency, $ref, $order),
            default => throw new \RuntimeException('Pasarela no soportada.', 400),
        };
    }

    private static function stripe(int $amount, string $cur, string $ref, array $o): array
    {
        $cfg = ConnectorRegistry::config('stripe');
        $r = Http::form('POST', 'https://api.stripe.com/v1/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => ($o['return_url'] ?? '') . '?ref=' . $ref . '&status=ok',
            'cancel_url' => ($o['return_url'] ?? '') . '?ref=' . $ref . '&status=cancel',
            'client_reference_id' => $ref,
            'line_items[0][price_data][currency]' => strtolower($cur),
            'line_items[0][price_data][product_data][name]' => $o['concept'] ?? 'ExperientIA',
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][quantity]' => 1,
        ], ['Authorization: Bearer ' . ($cfg['secret_key'] ?? '')]);
        return ['reference' => $ref, 'redirect' => $r['body']['url'] ?? null, 'provider' => 'stripe'];
    }

    private static function wompi(int $amount, string $cur, string $ref, array $o): array
    {
        // Wompi usa un widget/checkout con la llave pública; el backend registra la referencia.
        $cfg = ConnectorRegistry::config('wompi');
        return ['reference' => $ref, 'provider' => 'wompi', 'public_key' => $cfg['public_key'] ?? null,
            'amount_in_cents' => $amount, 'currency' => $cur, 'checkout' => 'https://checkout.wompi.co/p/'];
    }

    private static function paypal(int $amount, string $cur, string $ref, array $o): array
    {
        $cfg = ConnectorRegistry::config('paypal');
        $tok = self::paypalToken($cfg);
        $r = Http::json('POST', 'https://api-m.paypal.com/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [['reference_id' => $ref, 'amount' => ['currency_code' => $cur, 'value' => number_format($amount / 100, 2, '.', '')]]],
        ], ['Authorization: Bearer ' . $tok]);
        $approve = null;
        foreach ($r['body']['links'] ?? [] as $l) { if (($l['rel'] ?? '') === 'approve') { $approve = $l['href']; } }
        return ['reference' => $ref, 'redirect' => $approve, 'provider' => 'paypal', 'order_id' => $r['body']['id'] ?? null];
    }

    private static function paypalToken(array $cfg): string
    {
        $r = Http::form('POST', 'https://api-m.paypal.com/v1/oauth2/token', ['grant_type' => 'client_credentials'],
            ['Authorization: Basic ' . base64_encode(($cfg['client_id'] ?? '') . ':' . ($cfg['secret'] ?? ''))]);
        return $r['body']['access_token'] ?? '';
    }

    private static function epayco(int $amount, string $cur, string $ref, array $o): array
    {
        // ePayco usa checkout onpage con la llave pública; el backend registra la referencia.
        $cfg = ConnectorRegistry::config('epayco');
        return ['reference' => $ref, 'provider' => 'epayco', 'public_key' => $cfg['public_key'] ?? null,
            'amount' => number_format($amount / 100, 2, '.', ''), 'currency' => $cur];
    }

    public static function test(string $provider): array
    {
        $cfg = ConnectorRegistry::config($provider);
        $keys = ['stripe' => 'secret_key', 'wompi' => 'private_key', 'paypal' => 'client_id', 'epayco' => 'private_key'];
        return empty($cfg[$keys[$provider] ?? ''])
            ? ['ok' => false, 'error' => 'Faltan credenciales de ' . $provider . '.']
            : ['ok' => true, 'message' => 'Credenciales presentes. Verificación real al procesar el primer pago.'];
    }
}
