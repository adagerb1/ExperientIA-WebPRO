<?php
/**
 * Ayuda guiada de cada conector: qué hace, pasos para activarlo y explicación
 * de cada campo (qué es y dónde se obtiene). Se fusiona en /admin/connectors y
 * se muestra con los botones "?" del panel. Pensado para usuarios no técnicos.
 */
return [
    'openai' => [
        'que' => 'El cerebro de AlexIA: redacta contenido, analiza datos, responde el chat y genera imágenes y audio para los recursos.',
        'guia' => [
            'Entra a platform.openai.com y crea una cuenta (o inicia sesión).',
            'Ve a "Billing" y agrega un método de pago con algo de saldo (el uso es por consumo).',
            'En el menú, abre "API keys" y haz clic en "Create new secret key".',
            'Copia la clave (empieza por sk-...) y pégala en el campo "API key" aquí.',
            'Deja el modelo sugerido (gpt-4o-mini) o pon otro válido, guarda y pulsa "Probar".',
        ],
        'campos' => [
            'api_key' => 'La clave secreta de tu cuenta de OpenAI. Se obtiene en platform.openai.com → API keys → Create new secret key. Empieza por "sk-". Guárdala bien: OpenAI no la vuelve a mostrar.',
            'model' => 'Modelo de texto para chat y análisis. "gpt-4o-mini" es económico y rápido. Puedes usar gpt-4o o gpt-4.1 para mayor calidad.',
            'image_model' => 'Modelo para generar las portadas de los recursos. gpt-image-1 es el actual.',
            'audio_model' => 'Modelo para convertir el artículo en audio (narración). gpt-4o-mini-tts es el recomendado.',
            'voice' => 'La voz de la narración. Prueba varias: "nova" y "shimmer" suenan cálidas; "onyx" es más grave.',
        ],
    ],
    'anthropic' => [
        'que' => 'Cerebro alternativo de AlexIA (Claude) para redacción y análisis estratégico. Puedes usar este o OpenAI.',
        'guia' => [
            'Entra a console.anthropic.com y crea tu cuenta.',
            'Agrega saldo en "Plans & Billing".',
            'Abre "API Keys" → "Create Key" y copia la clave (empieza por sk-ant-...).',
            'Pégala en el campo "API key", elige un modelo válido y guarda.',
            'Pulsa "Probar" para confirmar la conexión.',
        ],
        'campos' => [
            'api_key' => 'Clave de Anthropic. Se obtiene en console.anthropic.com → API Keys. Empieza por "sk-ant-".',
            'model' => 'Modelo de Claude. Válidos hoy: claude-sonnet-4-5 (equilibrado), claude-opus-4-1 (máxima calidad), claude-haiku-4-5 (rápido).',
        ],
    ],
    'linkedin' => [
        'que' => 'Publica automáticamente el contenido del Content Studio en tu página de empresa o perfil de LinkedIn.',
        'guia' => [
            'Entra a linkedin.com/developers y pulsa "Create app". Asóciala a tu página de empresa.',
            'En la pestaña "Products", solicita "Share on LinkedIn" y "Sign In with LinkedIn using OpenID Connect".',
            'En "Auth", copia el Client ID y el Client Secret y pégalos aquí.',
            'Genera un access token con los scopes w_member_social (perfil) o w_organization_social (página). Puedes usar el "OAuth 2.0 tools" de LinkedIn o tu propio flujo.',
            'Pega el access token y el URN del autor (ver ayuda del campo), guarda y pulsa "Probar".',
            'Usa "Publicar prueba" para confirmar que puede publicar. El token vence en ~60 días; renuévalo cuando falle.',
        ],
        'campos' => [
            'client_id' => 'Identificador público de tu app de LinkedIn. Está en linkedin.com/developers → tu app → pestaña "Auth".',
            'client_secret' => 'Clave secreta de la app, junto al Client ID en la pestaña "Auth". No la compartas.',
            'access_token' => 'Token OAuth que autoriza a publicar. Debe incluir el scope de escritura (w_member_social para perfil, w_organization_social para página). Se genera con las herramientas OAuth de LinkedIn. Vence a los ~60 días.',
            'author_urn' => 'Identifica en nombre de quién se publica. Para una PÁGINA usa urn:li:organization:ID (el ID sale en la URL de administración de la página). Para tu PERFIL usa urn:li:person:ID (tu member ID). ',
        ],
    ],
    'google_business' => [
        'que' => 'Gestiona tu ficha de Google (Search y Maps): lee y responde reseñas, publica novedades y ofertas, y consulta tu cuenta.',
        'guia' => [
            'Entra a console.cloud.google.com y crea (o elige) un proyecto.',
            'En "APIs y servicios" → "Biblioteca", habilita "Google Business Profile API" (y "Account Management" e "Information" si aparecen).',
            'IMPORTANTE: solicita acceso a la API en el formulario oficial de Google (developers.google.com/my-business → "Request access"). Google debe aprobarlo.',
            'En "Credenciales" crea un "ID de cliente de OAuth" (tipo Aplicación web) y copia Client ID y Client Secret.',
            'Autoriza tu cuenta con el scope https://www.googleapis.com/auth/business.manage y obtén un refresh token (puedes usar el OAuth Playground de Google).',
            'Pega Client ID, Client Secret y refresh token. Luego el Account ID y Location ID de tu ficha (ver ayuda de cada campo).',
            'Guarda, pulsa "Probar" y luego "Sincronizar reseñas".',
        ],
        'campos' => [
            'client_id' => 'ID de cliente OAuth de tu proyecto en Google Cloud. Se crea en console.cloud.google.com → Credenciales → Crear ID de cliente OAuth. Termina en .apps.googleusercontent.com.',
            'client_secret' => 'Secreto del mismo ID de cliente OAuth, en la pantalla de credenciales de Google Cloud.',
            'refresh_token' => 'Token de larga duración que permite renovar el acceso sin volver a iniciar sesión. Se obtiene al autorizar con el scope business.manage (p. ej. con el OAuth 2.0 Playground de Google).',
            'account_id' => 'Identificador de tu cuenta de Business Profile, con el formato accounts/1234567890. Lo devuelve la propia API al listar cuentas, o aparece en el gestor de perfiles de negocio.',
            'location_id' => 'Identificador de la ubicación/ficha concreta, formato locations/9876543210. Lo devuelve la API al listar las ubicaciones de tu cuenta.',
        ],
    ],
    'sendgrid' => [
        'que' => 'Envía los correos del sistema (notificaciones de leads, resultados de diagnóstico, accesos, confirmaciones) de forma confiable.',
        'guia' => [
            'Entra a sendgrid.com y crea una cuenta gratuita.',
            'Verifica un remitente: "Settings" → "Sender Authentication" (autentica tu dominio o al menos un "Single Sender").',
            'Ve a "Settings" → "API Keys" → "Create API Key" (permiso "Full Access" o al menos "Mail Send").',
            'Copia la clave y pégala en "API key". Completa el correo y nombre del remitente (debe ser el verificado).',
            'Guarda y usa "Enviar correo de prueba" para confirmar que llega (revisa spam la primera vez).',
        ],
        'campos' => [
            'api_key' => 'Clave de API de SendGrid. Se crea en Settings → API Keys. Necesita permiso de envío de correo ("Mail Send").',
            'from_email' => 'Correo remitente que verá el destinatario. DEBE estar verificado en SendGrid (Sender Authentication), si no, los correos se rechazan.',
            'from_name' => 'Nombre que acompaña al remitente, p. ej. "ExperientIA".',
            'test_to' => 'Correo al que se envía la prueba. Solo se usa al pulsar "Enviar correo de prueba"; no se guarda.',
        ],
    ],
    'google_mail' => [
        'que' => 'Conecta tu correo corporativo de Google Workspace. La plataforma envía propuestas y seguimientos con tu identidad real (quedan en tus "Enviados") y AlexIA lee la bandeja comercial para clasificar los correos, avisarte de los importantes y sugerir la respuesta.',
        'guia' => [
            'Entra a console.cloud.google.com con tu cuenta de Google Workspace y elige (o crea) un proyecto.',
            'En "APIs y servicios" → "Biblioteca", busca "Gmail API" y pulsa "Habilitar".',
            'En "Pantalla de consentimiento OAuth", elige tipo "Interno" (solo tu organización) y guarda.',
            'En "Credenciales" → "Crear credenciales" → "ID de cliente de OAuth" (tipo Aplicación web). Copia el Client ID y el Client Secret y pégalos aquí.',
            'Autoriza el buzón con los permisos https://www.googleapis.com/auth/gmail.send y https://www.googleapis.com/auth/gmail.readonly y obtén el refresh token (puedes usar el OAuth 2.0 Playground de Google: engrane → "Use your own OAuth credentials").',
            'Pega el refresh token, escribe el correo del buzón (p. ej. comercial@experientia.pro) y el nombre con el que saldrán los envíos.',
            'Guarda, pulsa "Probar" y luego "Enviar correo de prueba". Activa el conector: desde ese momento los correos del sistema salen con tu identidad corporativa.',
        ],
        'campos' => [
            'client_id' => 'ID de cliente OAuth de tu proyecto en Google Cloud (termina en .apps.googleusercontent.com). Se crea en console.cloud.google.com → Credenciales.',
            'client_secret' => 'Secreto del mismo ID de cliente OAuth, visible en la pantalla de credenciales. No lo compartas.',
            'refresh_token' => 'Token de larga duración que permite renovar el acceso al buzón sin volver a iniciar sesión. Se obtiene al autorizar el buzón con los scopes gmail.send y gmail.readonly (p. ej. con el OAuth 2.0 Playground).',
            'mailbox' => 'El correo corporativo conectado, tal como enviará (p. ej. comercial@experientia.pro). Debe ser el mismo buzón que autorizaste.',
            'from_name' => 'Nombre que verá el destinatario junto al correo, p. ej. "Tonny Dager · ExperientIA".',
            'test_to' => 'Correo al que se envía la prueba. Solo se usa al pulsar "Enviar correo de prueba"; no se guarda.',
        ],
    ],
    'telegram' => [
        'que' => 'Dos bots: uno comercial (capta leads desde Telegram) y uno interno (AlexIA para el equipo y notificaciones de negocio).',
        'guia' => [
            'Abre Telegram y escribe a @BotFather.',
            'Envía /newbot y sigue los pasos para crear el BOT COMERCIAL; copia el token que te da.',
            'Repite /newbot para crear el BOT INTERNO (del equipo) y copia su token.',
            'Pega ambos tokens en sus campos. Define un "secreto de webhook" (una frase larga inventada).',
            'Guarda y pulsa "Registrar webhooks" para que Telegram envíe los mensajes a la plataforma.',
            'Para notificaciones: cada persona del equipo escribe al bot interno, este le responde su ID; regístralo en Plataforma → Usuarios y activa "recibe notificaciones".',
        ],
        'campos' => [
            'commercial_token' => 'Token del bot público que capta leads. Lo entrega @BotFather al crear el bot. Formato: 123456789:AA...',
            'internal_token' => 'Token del bot interno del equipo (AlexIA + notificaciones). También lo da @BotFather con un segundo bot.',
            'authorized_chats' => 'IDs de chat autorizados para el bot interno de AlexIA, separados por coma. Opcional: restringe quién puede conversar con AlexIA por Telegram.',
            'webhook_secret' => 'Una frase secreta que tú inventas. Telegram la envía en cada webhook para verificar que el mensaje es legítimo.',
        ],
    ],
    'whatsapp' => [
        'que' => 'AlexIA comercial en WhatsApp (Cloud API de Meta): responde y capta leads desde tu número de WhatsApp Business.',
        'guia' => [
            'Entra a developers.facebook.com, crea una app de tipo "Business" y agrega el producto "WhatsApp".',
            'En WhatsApp → "API Setup", copia el "Temporary access token" (o genera uno permanente con un usuario de sistema) y el "Phone number ID".',
            'Copia también el "WhatsApp Business Account ID" (WABA ID).',
            'Inventa un "verify token" (frase libre) y pégalo aquí junto a los demás datos.',
            'Guarda y pulsa "Ver URL de webhook": copia esa URL y el verify token en la configuración de webhooks de Meta y suscríbete a los mensajes.',
        ],
        'campos' => [
            'token' => 'Access token de la Cloud API de Meta. En developers.facebook.com → tu app → WhatsApp → API Setup. Empieza por "EAAG...".',
            'phone_id' => 'Phone number ID del número de WhatsApp Business, en la misma pantalla "API Setup" (NO es el número telefónico).',
            'verify_token' => 'Frase que tú inventas; Meta la usa para verificar tu webhook. Debe coincidir con la que pongas en la configuración de Meta.',
            'waba_id' => 'WhatsApp Business Account ID, identifica tu cuenta de WhatsApp Business en Meta.',
        ],
    ],
    'wompi' => [
        'que' => 'Cobra consultas y servicios con Wompi (Bancolombia). Solo una pasarela de pago puede estar activa a la vez.',
        'guia' => [
            'Entra a comercios.wompi.co y crea/accede a tu comercio.',
            'En "Desarrolladores", copia las llaves (pública, privada) y los secretos de integridad y eventos.',
            'Pega los cuatro valores en sus campos.',
            'Guarda y pulsa "Probar".',
        ],
        'campos' => [
            'public_key' => 'Llave pública de Wompi (empieza por pub_prod_ o pub_test_). En comercios.wompi.co → Desarrolladores.',
            'private_key' => 'Llave privada de Wompi. En la misma sección de Desarrolladores. No la compartas.',
            'integrity_secret' => 'Secreto de integridad para firmar las transacciones, en Desarrolladores.',
            'events_secret' => 'Secreto de eventos para validar las notificaciones (webhooks) de Wompi.',
        ],
    ],
    'epayco' => [
        'que' => 'Cobra con ePayco (Davivienda). Solo una pasarela de pago puede estar activa a la vez.',
        'guia' => [
            'Entra a dashboard.epayco.co y accede a tu cuenta.',
            'En "Integraciones" → "Llaves API", copia el Public Key, el P_CUST_ID y el P_KEY.',
            'Pega los valores; elige "Modo prueba" true mientras pruebas y false para cobrar de verdad.',
            'Guarda y pulsa "Probar".',
        ],
        'campos' => [
            'public_key' => 'Llave pública de ePayco, en dashboard.epayco.co → Integraciones → Llaves API.',
            'p_cust_id' => 'ID de cliente de ePayco (P_CUST_ID), en la misma sección de llaves.',
            'private_key' => 'Llave privada P_KEY de ePayco. No la compartas.',
            'test_mode' => 'true = ambiente de pruebas (no cobra dinero real). false = producción (cobra de verdad).',
        ],
    ],
    'stripe' => [
        'que' => 'Cobra con Stripe (internacional). Solo una pasarela de pago puede estar activa a la vez.',
        'guia' => [
            'Entra a dashboard.stripe.com y crea tu cuenta.',
            'En "Developers" → "API keys", copia la "Secret key" (sk_live_... para producción).',
            'En "Developers" → "Webhooks", crea un endpoint y copia su "Signing secret" (whsec_...).',
            'Pega ambos valores, guarda y pulsa "Probar".',
        ],
        'campos' => [
            'secret_key' => 'Clave secreta de Stripe. En dashboard.stripe.com → Developers → API keys. sk_live_ (real) o sk_test_ (prueba).',
            'webhook_secret' => 'Secreto de firma del webhook, para validar las notificaciones de Stripe. Empieza por whsec_.',
        ],
    ],
    'paypal' => [
        'que' => 'Cobra con PayPal. Solo una pasarela de pago puede estar activa a la vez.',
        'guia' => [
            'Entra a developer.paypal.com → "Apps & Credentials".',
            'Crea una app (o usa la Default). Copia el Client ID y el Secret.',
            'Elige entorno: "sandbox" para pruebas o "live" para cobrar de verdad.',
            'Pega los datos, guarda y pulsa "Probar".',
        ],
        'campos' => [
            'client_id' => 'Client ID de tu app de PayPal, en developer.paypal.com → Apps & Credentials.',
            'secret' => 'Secret de la misma app de PayPal. No lo compartas.',
            'mode' => 'live = cobra dinero real. sandbox = ambiente de pruebas de PayPal.',
        ],
    ],
    'google_calendar' => [
        'que' => 'Sincroniza las reservas 1:1 con tu Google Calendar: cada sesión agendada crea un evento.',
        'guia' => [
            'Entra a console.cloud.google.com y elige/crea un proyecto.',
            'En "Biblioteca" habilita la "Google Calendar API".',
            'En "Credenciales" crea un "ID de cliente de OAuth" (Aplicación web) y copia Client ID y Client Secret.',
            'Autoriza con el scope https://www.googleapis.com/auth/calendar.events y obtén un refresh token (p. ej. con el OAuth 2.0 Playground).',
            'Pega Client ID, Client Secret, refresh token y el Calendar ID (usa "primary" para tu calendario principal).',
            'Guarda, pulsa "Probar" y luego "Crear evento de prueba".',
        ],
        'campos' => [
            'client_id' => 'ID de cliente OAuth de Google Cloud (termina en .apps.googleusercontent.com).',
            'client_secret' => 'Secreto del mismo ID de cliente OAuth.',
            'refresh_token' => 'Token de larga duración para renovar el acceso. Se obtiene al autorizar con el scope calendar.events.',
            'calendar_id' => 'Qué calendario usar. "primary" es tu calendario principal; o pega el ID de un calendario específico.',
        ],
    ],
];
