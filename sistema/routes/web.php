<?php

use App\Http\Controllers\Site\BookingController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\DiagnosticoController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ResourceController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// Raíz: redirige al idioma del navegador (ES por defecto).
Route::get('/', function () {
    $locale = request()->getPreferredLanguage(['es', 'en', 'pt']) ?? 'es';

    return redirect()->to("/{$locale}");
});

foreach (['es', 'en', 'pt'] as $locale) {
    $slug = fn (string $key) => trans("site.slugs.{$key}", [], $locale);

    Route::prefix($locale)
        ->name("{$locale}.")
        ->middleware(SetLocale::class . ':' . $locale)
        ->group(function () use ($slug) {
            Route::get('/', [PageController::class, 'home'])->name('home');
            Route::get($slug('soluciones'), [PageController::class, 'soluciones'])->name('soluciones');
            Route::get($slug('tablero'), [PageController::class, 'tablero'])->name('tablero');
            Route::get($slug('productos'), [PageController::class, 'productos'])->name('productos');
            Route::get($slug('casos'), [PageController::class, 'casos'])->name('casos');
            Route::get($slug('nosotros'), [PageController::class, 'nosotros'])->name('nosotros');
            Route::get($slug('faq'), [PageController::class, 'faq'])->name('faq');

            Route::get($slug('recursos'), [ResourceController::class, 'index'])->name('recursos');
            Route::post($slug('recursos') . '/newsletter', [ResourceController::class, 'newsletter'])->name('newsletter');
            Route::get($slug('recursos') . '/{slug}', [ResourceController::class, 'show'])->name('recurso');
            Route::post($slug('recursos') . '/{slug}', [ResourceController::class, 'download'])
                ->middleware('throttle:10,1')->name('recurso.descargar');
            Route::get($slug('recursos') . '/{slug}/archivo', [ResourceController::class, 'file'])->name('recurso.archivo');

            Route::get($slug('contacto'), [ContactController::class, 'show'])->name('contacto');
            Route::post($slug('contacto'), [ContactController::class, 'store'])
                ->middleware('throttle:10,1')->name('contacto.enviar');

            Route::get($slug('diagnostico'), [DiagnosticoController::class, 'show'])->name('diagnostico');
            Route::post($slug('diagnostico'), [DiagnosticoController::class, 'store'])
                ->middleware('throttle:10,1')->name('diagnostico.enviar');

            Route::get($slug('agenda'), [BookingController::class, 'show'])->name('agenda');
            Route::post($slug('agenda'), [BookingController::class, 'store'])
                ->middleware('throttle:10,1')->name('agenda.reservar');
        });
}

Route::get('/sitemap.xml', App\Http\Controllers\Site\SitemapController::class)->name('sitemap');
