<?php
/**
 * Front controller de la API REST (JSON puro, no renderiza HTML).
 * Punto de entrada de todo /api/*.
 */

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/helpers.php';

use Core\Request;
use Core\Cors;
use Core\Router;

$req = new Request();
Cors::handle($req);

$r = new Router();

// ─── API pública (sitio) ───────────────────────────────────────────
$r->get('/content/{seccion}', 'PublicApi\\ContentController', 'show');
$r->get('/slots', 'PublicApi\\BookingController', 'slots');
$r->post('/contacto', 'PublicApi\\LeadController', 'contacto');
$r->post('/newsletter', 'PublicApi\\LeadController', 'newsletter');
$r->post('/interes', 'PublicApi\\LeadController', 'interes');
$r->post('/descarga', 'PublicApi\\ResourceController', 'descargar');
$r->get('/descarga-archivo', 'PublicApi\\ResourceController', 'archivo');
$r->get('/meta', 'PublicApi\\MetaController', 'index');
$r->get('/resenas', 'PublicApi\\ReviewsController', 'index');   // prueba social pública (Google)
$r->get('/testimonios', 'PublicApi\\TestimonialsController', 'index');           // testimonios publicados
$r->get('/propuesta/{code}', 'PublicApi\\ProposalController', 'meta');            // compuerta de la propuesta
$r->post('/propuesta/{code}/acceso', 'PublicApi\\ProposalController', 'acceso');  // valida email+NIT y entrega
$r->post('/propuesta/{code}/aceptar', 'PublicApi\\ProposalController', 'aceptar'); // aceptación del cliente
$r->get('/testimonio/{code}', 'PublicApi\\TestimonialsController', 'invitacion'); // invitación (código corto)
$r->post('/testimonio/{code}', 'PublicApi\\TestimonialsController', 'enviar');    // cliente envía testimonio
$r->get('/diagnosticos', 'PublicApi\\DiagnosticController', 'index');
$r->get('/diagnosticos/{dkey}', 'PublicApi\\DiagnosticController', 'show');
$r->post('/diagnostico', 'PublicApi\\DiagnosticController', 'evaluar');
$r->post('/reserva', 'PublicApi\\BookingController', 'reservar');
$r->get('/captcha', 'PublicApi\\CaptchaController', 'nuevo');          // reto captcha (gate del chat)
$r->post('/hit', 'Admin\\FunnelController', 'hit');                    // beacon de visita (sin cookies)
$r->get('/growthboard/config', 'PublicApi\\GrowthBoardController', 'config');      // método: zonas, líneas, bandas
$r->post('/growthboard/diagnostico', 'PublicApi\\GrowthBoardController', 'diagnostico'); // diagnóstico 11 zonas
$r->get('/growthboard/demo/{industry}', 'PublicApi\\GrowthBoardController', 'demo');     // demo con datos de ejemplo
$r->get('/growthboard/demo', 'PublicApi\\GrowthBoardController', 'demo');
$r->post('/mi-tablero/acceso', 'PublicApi\\ClientBoardController', 'acceso');   // enlace de acceso por correo
$r->get('/mi-tablero', 'PublicApi\\ClientBoardController', 'board');            // tablero del cliente (token gbl)
$r->post('/mi-tablero/jugadas/{id}/estado', 'PublicApi\\ClientBoardController', 'estado');
$r->post('/mi-tablero/checkin', 'PublicApi\\ClientBoardController', 'checkin'); // marcador semanal
$r->post('/alexia', 'PublicApi\\ChatController', 'mensaje');           // AlexIA comercial (web)
$r->post('/alexia/lead', 'PublicApi\\ChatController', 'lead');         // captura al iniciar chat
$r->post('/alexia/resumen', 'PublicApi\\ChatController', 'resumen');   // resumen por correo al cerrar

// ─── Webhooks de conectores (entrantes de terceros) ────────────────
$r->get('/cron/run', 'PublicApi\\CronController', 'run');
$r->post('/cron/run', 'PublicApi\\CronController', 'run');
$r->post('/webhook/telegram/{bot}', 'PublicApi\\WebhookController', 'telegram');
$r->post('/webhook/whatsapp', 'PublicApi\\WebhookController', 'whatsapp');
$r->get('/webhook/whatsapp', 'PublicApi\\WebhookController', 'whatsappVerify');
$r->post('/webhook/pago/{provider}', 'PublicApi\\WebhookController', 'pago');

// ─── Autenticación admin ───────────────────────────────────────────
$r->post('/admin/login', 'Admin\\AuthController', 'login');
$r->get('/admin/me', 'Admin\\AuthController', 'me');

// ─── Admin (protegido por Bearer en cada controlador) ──────────────
$r->get('/admin/resumen', 'Admin\\DashboardController', 'resumen');
$r->get('/admin/analitica', 'Admin\\AnalyticsController', 'panel');
$r->get('/admin/leads', 'Admin\\LeadsController', 'index');
$r->get('/admin/leads/{id}', 'Admin\\LeadsController', 'show');
$r->patch('/admin/leads/{id}', 'Admin\\LeadsController', 'update');
$r->delete('/admin/leads/{id}', 'Admin\\LeadsController', 'destroy');
$r->get('/admin/leads-export', 'Admin\\LeadsController', 'export');
$r->get('/admin/pipeline', 'Admin\\LeadsController', 'pipeline');
$r->post('/admin/leads/{id}/sugerencia', 'Admin\\LeadsController', 'sugerencia');
$r->get('/admin/secuencias', 'Admin\\SequencesController', 'index');
$r->post('/admin/secuencias', 'Admin\\SequencesController', 'save');
$r->delete('/admin/secuencias/{id}', 'Admin\\SequencesController', 'destroy');
$r->post('/admin/secuencias/procesar', 'Admin\\SequencesController', 'procesar');
$r->get('/admin/campanas', 'Admin\\CampaignsController', 'panel');
$r->get('/admin/segmentos/preview', 'Admin\\SegmentsController', 'preview');
$r->get('/admin/segmentos/export', 'Admin\\SegmentsController', 'export');
$r->post('/admin/segmentos/enviar', 'Admin\\SegmentsController', 'enviar');
$r->get('/admin/reservas', 'Admin\\BookingsController', 'index');
$r->patch('/admin/reservas/{id}', 'Admin\\BookingsController', 'update');
// Rutas específicas ANTES de las genéricas /admin/{tabla} (evita colisiones).
$r->post('/admin/archivo', 'Admin\\ContentController', 'upload');
$r->post('/admin/cms/redactar', 'Admin\\CmsAssistController', 'redactar');
$r->post('/admin/recursos/generar', 'Admin\\ResourceStudioController', 'generar');
$r->post('/admin/recursos/portada', 'Admin\\ResourceStudioController', 'portada');
$r->post('/admin/recursos/subir-imagen', 'Admin\\ResourceStudioController', 'subirImagen');
$r->post('/admin/recursos/audio', 'Admin\\ResourceStudioController', 'audio');
$r->get('/admin/connectors', 'Admin\\ConnectorsController', 'index');
$r->put('/admin/connectors/{provider}', 'Admin\\ConnectorsController', 'update');
$r->post('/admin/connectors/{provider}/test', 'Admin\\ConnectorsController', 'test');
$r->post('/admin/connectors/{provider}/accion/{accion}', 'Admin\\ConnectorsController', 'accion');
$r->get('/admin/email-templates', 'Admin\\ConnectorsController', 'templates');
$r->put('/admin/email-templates/{tkey}', 'Admin\\ConnectorsController', 'saveTemplate');
// Usuarios del portal admin (gestión de equipo + notificaciones Telegram)
$r->get('/admin/usuarios', 'Admin\\UsersController', 'index');
$r->post('/admin/usuarios', 'Admin\\UsersController', 'store');
$r->put('/admin/usuarios/{id}', 'Admin\\UsersController', 'update');
$r->delete('/admin/usuarios/{id}', 'Admin\\UsersController', 'destroy');
// Funnel comercial y costo por lead
$r->get('/admin/funnel', 'Admin\\FunnelController', 'resumen');
$r->put('/admin/funnel/gasto', 'Admin\\FunnelController', 'gasto');
// GrowthBoard AI Content Studio (estrategia → brief → calendario → piezas)
$r->get('/admin/studio/estrategias', 'Admin\\ContentStudioController', 'index');
$r->post('/admin/studio/estrategias', 'Admin\\ContentStudioController', 'store');
$r->get('/admin/studio/estrategias/{id}', 'Admin\\ContentStudioController', 'show');
$r->put('/admin/studio/estrategias/{id}', 'Admin\\ContentStudioController', 'update');
$r->delete('/admin/studio/estrategias/{id}', 'Admin\\ContentStudioController', 'destroy');
$r->post('/admin/studio/estrategias/{id}/brief', 'Admin\\ContentStudioController', 'brief');
$r->post('/admin/studio/estrategias/{id}/calendario', 'Admin\\ContentStudioController', 'calendario');
$r->post('/admin/studio/estrategias/{id}/items', 'Admin\\ContentStudioController', 'itemStore');
$r->get('/admin/studio/estrategias/{id}/export', 'Admin\\ContentStudioController', 'export');
$r->post('/admin/studio/items/{pid}/generar', 'Admin\\ContentStudioController', 'generarPieza');
$r->post('/admin/studio/items/{pid}/publicar', 'Admin\\ContentStudioController', 'publicar');
$r->put('/admin/studio/items/{pid}', 'Admin\\ContentStudioController', 'itemUpdate');
$r->delete('/admin/studio/items/{pid}', 'Admin\\ContentStudioController', 'itemDestroy');
// GrowthBoard · seguimiento del acompañamiento (consultor)
$r->get('/admin/growthboard/clientes', 'Admin\\GrowthBoardAdminController', 'clientes');
$r->get('/admin/growthboard/clientes/{id}', 'Admin\\GrowthBoardAdminController', 'cliente');
$r->get('/admin/growthboard/clientes/{id}/acceso', 'Admin\\GrowthBoardAdminController', 'acceso');
$r->post('/admin/growthboard/clientes/{id}/enviar-acceso', 'Admin\\GrowthBoardAdminController', 'enviarAcceso');
$r->post('/admin/growthboard/clientes/{id}/jugadas', 'Admin\\GrowthBoardAdminController', 'jugadaStore');
$r->put('/admin/growthboard/clientes/{id}/jugadas/{pid}', 'Admin\\GrowthBoardAdminController', 'jugadaUpdate');
$r->delete('/admin/growthboard/clientes/{id}/jugadas/{pid}', 'Admin\\GrowthBoardAdminController', 'jugadaDestroy');
// Propuestas comerciales: crear, editar, generar con AlexIA, enviar y trazar.
$r->get('/admin/propuestas', 'Admin\\ProposalsController', 'index');
$r->post('/admin/propuestas', 'Admin\\ProposalsController', 'store');
$r->put('/admin/propuestas/{id}', 'Admin\\ProposalsController', 'update');
$r->post('/admin/propuestas/{id}/generar', 'Admin\\ProposalsController', 'generar');
$r->post('/admin/propuestas/{id}/enviar', 'Admin\\ProposalsController', 'enviar');
$r->delete('/admin/propuestas/{id}', 'Admin\\ProposalsController', 'destroy');
// Testimonios de clientes: invitar (código corto), enviar, revisar y publicar.
$r->get('/admin/testimonios', 'Admin\\TestimonialsController', 'index');
$r->post('/admin/testimonios', 'Admin\\TestimonialsController', 'store');
$r->post('/admin/testimonios/{id}/enviar', 'Admin\\TestimonialsController', 'enviar');
$r->put('/admin/testimonios/{id}', 'Admin\\TestimonialsController', 'update');
$r->delete('/admin/testimonios/{id}', 'Admin\\TestimonialsController', 'destroy');
// Reseñas de Google: prueba social + respuestas de AlexIA (sugerir/aprobar/destacar/auto-piloto).
$r->get('/admin/resenas', 'Admin\\ReviewsController', 'index');
$r->post('/admin/resenas/ajustes', 'Admin\\ReviewsController', 'ajustes');
$r->post('/admin/resenas/{id}/sugerir', 'Admin\\ReviewsController', 'sugerir');
$r->post('/admin/resenas/{id}/aprobar', 'Admin\\ReviewsController', 'aprobar');
$r->post('/admin/resenas/{id}/destacar', 'Admin\\ReviewsController', 'destacar');
// Segmentos configurables y trilingües (categorías, tamaños, orígenes, canales).
$r->get('/admin/taxonomia/{kind}', 'Admin\\SegmentTaxonomyController', 'index');
$r->post('/admin/taxonomia/{kind}', 'Admin\\SegmentTaxonomyController', 'store');
$r->put('/admin/taxonomia/{kind}/{id}', 'Admin\\SegmentTaxonomyController', 'update');
$r->delete('/admin/taxonomia/{kind}/{id}', 'Admin\\SegmentTaxonomyController', 'destroy');
$r->post('/admin/alexia/estrategia', 'Admin\\ChatController', 'estrategia'); // AlexIA estratega (BI)
$r->post('/admin/alexia/analista', 'Admin\\AnalystController', 'consultar'); // AlexIA analista (SQL solo lectura)
$r->post('/admin/alexia', 'Admin\\ChatController', 'mensaje');         // AlexIA interno (admin)
// Generador de landing con IA (AlexIA orquesta estratega + copywriter + traductor)
$r->post('/admin/landing/{tabla}/{id}/generar', 'Admin\\ContentController', 'generarLanding');
// Genéricas de contenido (soluciones, productos, casos, faqs, recursos, disponibilidad)
$r->get('/admin/{tabla}/list', 'Admin\\ContentController', 'index');
$r->post('/admin/{tabla}', 'Admin\\ContentController', 'store');
$r->put('/admin/{tabla}/{id}', 'Admin\\ContentController', 'update');
$r->delete('/admin/{tabla}/{id}', 'Admin\\ContentController', 'destroy');

$r->resolve($req);
