<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Touchpoint;
use Illuminate\Support\Facades\DB;

/**
 * Captura de leads con deduplicación.
 *
 * Un lead se identifica por su correo o su teléfono WhatsApp: si ya existe,
 * no se duplica — se enriquece su ficha con los datos nuevos y se registra
 * la interacción como un touchpoint más de su historial.
 */
class LeadCapture
{
    /**
     * @param array $data  Datos del lead (name, email, phone_wa, country, company, …)
     * @param string $type Tipo de touchpoint: contacto|descarga|diagnostico|reserva|newsletter
     * @param string $title Título legible del touchpoint
     * @param array $payload Detalle de la interacción
     */
    public function capture(array $data, string $type, string $title, array $payload = []): Lead
    {
        return DB::transaction(function () use ($data, $type, $title, $payload) {
            $email = isset($data['email']) ? mb_strtolower(trim($data['email'])) : null;
            $phone = isset($data['phone_wa']) ? preg_replace('/\D+/', '', $data['phone_wa']) : null;

            $lead = Lead::query()
                ->when($email || $phone, function ($q) use ($email, $phone) {
                    $q->where(function ($q) use ($email, $phone) {
                        if ($email) {
                            $q->orWhere('email', $email);
                        }
                        if ($phone) {
                            $q->orWhere('phone_wa', $phone);
                        }
                    });
                }, fn ($q) => $q->whereRaw('1 = 0'))
                ->first();

            $attributes = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $email,
                'phone_wa' => $phone,
                'phone_dial' => $data['phone_dial'] ?? null,
                'country' => isset($data['country']) ? strtoupper($data['country']) : null,
                'company' => $data['company'] ?? null,
                'role' => $data['role'] ?? null,
                'industry' => $data['industry'] ?? null,
                'company_size' => $data['company_size'] ?? null,
                'locale' => $data['locale'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if ($lead) {
                // Enriquecer sin borrar información existente.
                $lead->fill(array_diff_key($attributes, array_filter($lead->only(array_keys($attributes)))));
                $lead->save();
            } else {
                $attributes['source'] = $type;
                $lead = Lead::create($attributes);
            }

            $lead->touchpoints()->create([
                'type' => $type,
                'title' => $title,
                'payload' => $payload,
            ]);

            return $lead;
        });
    }
}
