-- ExperientIA · Esquema relacional (MySQL). Integridad con FK y CASCADE.
-- (El instalador adapta los tipos a SQLite en desarrollo.)

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'staff',       -- owner | admin | staff
  telegram_user_id VARCHAR(40) NULL,
  active TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NULL,
  phone_wa VARCHAR(20) NULL,
  phone_dial VARCHAR(5) NULL,
  country CHAR(2) NULL,
  company VARCHAR(160) NULL,
  role VARCHAR(120) NULL,
  industry VARCHAR(60) NULL,
  company_size VARCHAR(20) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'nuevo',
  source VARCHAR(30) NULL,
  channel VARCHAR(20) NOT NULL DEFAULT 'web',      -- web | telegram | whatsapp
  locale CHAR(2) NOT NULL DEFAULT 'es',
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_email (email),
  INDEX idx_phone (phone_wa),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS touchpoints (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  type VARCHAR(30) NOT NULL,
  title VARCHAR(255) NOT NULL,
  payload JSON NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solutions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skey VARCHAR(40) NOT NULL UNIQUE,
  icon VARCHAR(30) NOT NULL DEFAULT 'target',
  titulo JSON NOT NULL, pilar JSON NOT NULL, problema JSON NOT NULL,
  como JSON NOT NULL, cambia JSON NOT NULL,
  sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  icon VARCHAR(30) NOT NULL DEFAULT 'cube',
  nombre JSON NOT NULL, rol JSON NOT NULL, texto JSON NOT NULL,
  destacado TINYINT NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS case_studies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sector JSON NOT NULL, titulo JSON NOT NULL, contexto JSON NOT NULL,
  intervencion JSON NOT NULL, resultados JSON NOT NULL,
  sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pregunta JSON NOT NULL, respuesta JSON NOT NULL,
  sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  type VARCHAR(20) NOT NULL DEFAULT 'article',
  tipo_label JSON NOT NULL, titulo JSON NOT NULL, extracto JSON NOT NULL,
  cuerpo JSON NULL, file_path VARCHAR(255) NULL,
  downloads INT NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1,
  published_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  weekday TINYINT NOT NULL, start_time VARCHAR(5) NOT NULL, end_time VARCHAR(5) NOT NULL,
  active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'confirmada',
  visitor_timezone VARCHAR(60) NULL, tema TEXT NULL,
  gcal_event_id VARCHAR(120) NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  INDEX idx_starts (starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conectores: credenciales y estado por proveedor (config segura desde el panel)
CREATE TABLE IF NOT EXISTS connectors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(40) NOT NULL UNIQUE,            -- openai | sendgrid | telegram | whatsapp | wompi | epayco | stripe | paypal | google_calendar
  enabled TINYINT NOT NULL DEFAULT 0,
  config JSON NULL,                                -- credenciales cifradas / ajustes
  status VARCHAR(20) NOT NULL DEFAULT 'sin_configurar',
  last_check DATETIME NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plantillas de email (SendGrid / correo)
CREATE TABLE IF NOT EXISTS email_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tkey VARCHAR(60) NOT NULL UNIQUE,                -- lead_notify | booking_confirm | resource_delivery | newsletter_welcome
  subject JSON NOT NULL, body JSON NOT NULL,       -- {es,en,pt}, HTML con {{variables}}
  active TINYINT NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversaciones de AlexIA (web, telegram, whatsapp)
CREATE TABLE IF NOT EXISTS ai_conversations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel VARCHAR(20) NOT NULL,                    -- web | telegram | whatsapp
  scope VARCHAR(20) NOT NULL DEFAULT 'comercial',  -- comercial | interno
  lead_id INT UNSIGNED NULL,
  admin_id INT UNSIGNED NULL,
  external_id VARCHAR(120) NULL,                   -- chat_id de telegram / wa_id
  previous_response_id VARCHAR(120) NULL,          -- OpenAI Responses API (encadenado)
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_ext (channel, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  role VARCHAR(12) NOT NULL,                        -- user | assistant | tool
  content MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos / órdenes (pasarelas)
CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NULL,
  provider VARCHAR(20) NOT NULL,                   -- wompi | epayco | stripe | paypal
  reference VARCHAR(120) NOT NULL,
  amount BIGINT NOT NULL,                           -- en centavos
  currency CHAR(3) NOT NULL DEFAULT 'COP',
  status VARCHAR(20) NOT NULL DEFAULT 'pendiente',  -- pendiente | aprobado | rechazado | reembolsado
  external_id VARCHAR(160) NULL,
  meta JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
  INDEX idx_ref (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  hits INT NOT NULL DEFAULT 0,
  reset_at INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
