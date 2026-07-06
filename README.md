# ExperientIA · WebPRO

Plataforma digital de **ExperientIA** — *Automatización · Growth · IA*.

## Estructura del repositorio

| Carpeta | Qué es |
| --- | --- |
| **`sistema/`** | **La plataforma en producción**: sitio público trilingüe (ES/EN/PT) + CRM de leads + CMS de contenido + diagnóstico en línea + agendamiento de sesiones 1:1. Laravel 13 + Filament 5 + MySQL. |
| Raíz (`src/`, `public/`, `astro.config.mjs`) | Versión estática original (Astro) del sitio, conservada como referencia de diseño. El diseño fue portado íntegro a `sistema/`. |

## El sistema (`sistema/`)

- **Sitio público**: dark premium según el Manual de Identidad 2026, con slugs
  localizados (`/es/soluciones`, `/en/solutions`, `/pt/solucoes`), hreflang,
  sitemap.xml, schema Organization + FAQPage.
- **CRM**: leads con deduplicación por correo/WhatsApp, historial de
  touchpoints (contacto, descargas, diagnóstico, reservas, newsletter),
  pipeline de estados y notificaciones por correo.
- **CMS trilingüe con fallback**: soluciones, productos, casos, FAQs y
  recursos editables desde `/admin` (pestañas ES/EN/PT; el español es el único
  obligatorio).
- **Recursos**: artículos de lectura libre y descargables con formulario de
  captura (entrega inmediata + enlace firmado por correo).
- **Diagnóstico en línea**: 8 preguntas → recomendación de solución + lead
  calificado con sus respuestas.
- **Agenda 1:1**: disponibilidad configurable desde el panel, selección de
  horario en la zona horaria del visitante, reserva ligada al lead y
  confirmación por correo.
- **Formularios**: país e industria con combobox buscable, teléfono WhatsApp
  con bandera e indicativo buscable (se muestra `+57`, se guarda `57300…` para
  `wa.me`), rango de empleados (micro → corporativa), honeypot y rate limiting.

### Desarrollo local

```bash
cd sistema
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed --seeder=ContentSeeder
php artisan make:filament-user
npm run build          # o npm run dev en otra terminal
php artisan serve      # http://localhost:8000 · panel en /admin
```

### Despliegue

Ver **`sistema/DESPLIEGUE.md`** (guía paso a paso para cPanel/WHM).
