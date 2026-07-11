<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Response;
use Core\Taxonomy;

/** Metadatos públicos para los formularios: industrias, países, tamaños, idiomas. */
final class MetaController extends Controller
{
    public function index(): void
    {
        Response::ok([
            'industries' => Taxonomy::industries(),
            'countries' => Taxonomy::countries(),
            'company_sizes' => biz('company_sizes'),
            'lead_statuses' => biz('lead_statuses'),
            'locales' => biz('locales'),
        ]);
    }
}
