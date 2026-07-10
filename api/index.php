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
$r->post('/descarga', 'PublicApi\\ResourceController', 'descargar');
$r->get('/descarga-archivo', 'PublicApi\\ResourceController', 'archivo');
$r->post('/diagnostico', 'PublicApi\\DiagnosticController', 'evaluar');
$r->post('/reserva', 'PublicApi\\BookingController', 'reservar');
$r->post('/alexia', 'PublicApi\\ChatController', 'mensaje');           // AlexIA comercial (web)

// ─── Webhooks de conectores (entrantes de terceros) ────────────────
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
$r->get('/admin/reservas', 'Admin\\BookingsController', 'index');
$r->patch('/admin/reservas/{id}', 'Admin\\BookingsController', 'update');
$r->get('/admin/{tabla}/list', 'Admin\\ContentController', 'index');
$r->post('/admin/{tabla}', 'Admin\\ContentController', 'store');
$r->put('/admin/{tabla}/{id}', 'Admin\\ContentController', 'update');
$r->delete('/admin/{tabla}/{id}', 'Admin\\ContentController', 'destroy');
$r->post('/admin/archivo', 'Admin\\ContentController', 'upload');
$r->get('/admin/connectors', 'Admin\\ConnectorsController', 'index');
$r->put('/admin/connectors/{provider}', 'Admin\\ConnectorsController', 'update');
$r->post('/admin/connectors/{provider}/test', 'Admin\\ConnectorsController', 'test');
$r->get('/admin/email-templates', 'Admin\\ConnectorsController', 'templates');
$r->put('/admin/email-templates/{tkey}', 'Admin\\ConnectorsController', 'saveTemplate');
$r->post('/admin/alexia', 'Admin\\ChatController', 'mensaje');         // AlexIA interno (admin)

$r->resolve($req);
