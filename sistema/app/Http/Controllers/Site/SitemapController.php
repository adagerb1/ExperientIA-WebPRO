<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Resource;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $locales = ['es', 'en', 'pt'];
        $pages = ['home', 'soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'faq', 'contacto', 'diagnostico', 'agenda'];

        $urls = [];
        foreach ($pages as $page) {
            $alternates = collect($locales)->mapWithKeys(fn ($l) => [$l => route("{$l}.{$page}")]);
            foreach ($locales as $l) {
                $urls[] = ['loc' => $alternates[$l], 'alternates' => $alternates];
            }
        }

        foreach (Resource::active()->get() as $r) {
            $alternates = collect($locales)->mapWithKeys(fn ($l) => [$l => route("{$l}.recurso", ['slug' => $r->slug])]);
            foreach ($locales as $l) {
                $urls[] = ['loc' => $alternates[$l], 'alternates' => $alternates, 'lastmod' => $r->updated_at?->toDateString()];
            }
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
