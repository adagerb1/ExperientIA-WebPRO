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
$r->get('/diagnosticos', 'PublicApi\\DiagnosticController', 'index');
$r->get('/diagnosticos/{dkey}', 'PublicApi\\DiagnosticController', 'show');
$r->post('/diagnostico', 'PublicApi\\DiagnosticController', 'evaluar');
$r->post('/reserva', 'PublicApi\\BookingController', 'reservar');
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
$r->post('/admin/alexia/estrategia', 'Admin\\ChatController', 'estrategia'); // AlexIA estratega (BI)
$r->post('/admin/alexia', 'Admin\\ChatController', 'mensaje');         // AlexIA interno (admin)
// Genéricas de contenido (soluciones, productos, casos, faqs, recursos, disponibilidad)
$r->get('/admin/{tabla}/list', 'Admin\\ContentController', 'index');
$r->post('/admin/{tabla}', 'Admin\\ContentController', 'store');
$r->put('/admin/{tabla}/{id}', 'Admin\\ContentController', 'update');
$r->delete('/admin/{tabla}/{id}', 'Admin\\ContentController', 'destroy');

$r->resolve($req);
