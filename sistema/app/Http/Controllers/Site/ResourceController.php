<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\ResourceDelivery;
use App\Models\Resource;
use App\Services\LeadCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ResourceController extends Controller
{
    public function index()
    {
        return view('site.recursos', ['resources' => Resource::active()->get()]);
    }

    public function show(string $slug)
    {
        $resource = Resource::active()->where('slug', $slug)->firstOrFail();

        return view($resource->isDownload() ? 'site.recurso-descarga' : 'site.recurso-articulo', [
            'resource' => $resource,
        ]);
    }

    public function download(Request $request, LeadCapture $capture, string $slug)
    {
        $resource = Resource::active()->where('slug', $slug)->where('type', 'download')->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone_wa' => ['nullable', 'string', 'max:20'],
            'phone_dial' => ['nullable', 'string', 'max:5'],
            'country' => ['required', 'string', 'size:2'],
            'company' => ['nullable', 'string', 'max:160'],
            'industry' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('experientia.industries')))],
            'company_size' => ['nullable', 'string', 'in:micro,pequena,mediana,grande,corporativa'],
            'website' => ['prohibited'],
        ]);

        $lead = $capture->capture(
            $data + ['locale' => app()->getLocale()],
            'descarga',
            'Descargó: ' . tr($resource->titulo, 'es'),
            ['recurso' => $resource->slug],
        );

        $resource->increment('downloads');

        $signed = URL::temporarySignedRoute(app()->getLocale() . '.recurso.archivo', now()->addDays(7), [
            'slug' => $resource->slug,
        ]);

        try {
            Mail::to($lead->email)->send(new ResourceDelivery($resource, $signed, app()->getLocale()));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el recurso por correo: ' . $e->getMessage());
        }

        ContactController::notifyAdmin($lead);

        return redirect()->to(lroute('recurso', ['slug' => $resource->slug]) . '?ok=1')
            ->with('download_url', $signed);
    }

    public function file(Request $request, string $slug)
    {
        abort_unless($request->hasValidSignature(), 403);

        $resource = Resource::where('slug', $slug)->where('type', 'download')->firstOrFail();

        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download(
            $resource->file_path,
            \Illuminate\Support\Str::slug(tr($resource->titulo, 'es')) . '.pdf',
        );
    }

    public function newsletter(Request $request, LeadCapture $capture)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'website' => ['prohibited'],
        ]);

        $capture->capture(
            ['name' => $data['email'], 'email' => $data['email'], 'locale' => app()->getLocale()],
            'newsletter',
            'Se suscribió al newsletter',
        );

        return redirect()->to(lroute('recursos') . '?news=1#newsletter');
    }
}
