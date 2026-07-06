<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\LeadNotification;
use App\Services\LeadCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('site.contacto');
    }

    public function store(Request $request, LeadCapture $capture)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone_wa' => ['nullable', 'string', 'max:20'],
            'phone_dial' => ['nullable', 'string', 'max:5'],
            'country' => ['required', 'string', 'size:2'],
            'company' => ['required', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:120'],
            'industry' => ['required', 'string', 'in:' . implode(',', array_keys(config('experientia.industries')))],
            'company_size' => ['required', 'string', 'in:micro,pequena,mediana,grande,corporativa'],
            'desafio' => ['required', 'string', 'max:60'],
            'mensaje' => ['nullable', 'string', 'max:3000'],
            'website' => ['prohibited'], // honeypot anti-spam
        ]);

        $lead = $capture->capture(
            $data + ['locale' => app()->getLocale()],
            'contacto',
            'Solicitó diagnóstico ejecutivo',
            [
                'desafio' => __('site.form.desafios')[$data['desafio']] ?? $data['desafio'],
                'mensaje' => $data['mensaje'] ?? '',
            ],
        );

        $this->notifyAdmin($lead);

        return redirect()->to(lroute('contacto') . '?ok=1#gracias');
    }

    public static function notifyAdmin($lead): void
    {
        try {
            Mail::to(config('experientia.contact_email'))
                ->send(new LeadNotification($lead, $lead->touchpoints()->latest('id')->first()));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la notificación de lead: ' . $e->getMessage());
        }
    }
}
