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
            'resource_categories' => Taxonomy::segments('category'),
            'company_sizes' => Taxonomy::segments('company_size'),
            'lead_sources' => Taxonomy::segments('source'),
            'lead_channels' => Taxonomy::segments('channel'),
            'lead_statuses' => biz('lead_statuses'),
            'locales' => biz('locales'),
        ]);
    }
}
