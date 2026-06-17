-- ══════════════════════════════════════════════════════
--  Migración: Sistema de Servicios de Taller
--  Ejecutar en phpMyAdmin (una sola vez)
-- ══════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `servicios_solicitudes` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT UNSIGNED NOT NULL,
  `taller`           ENUM('taller1','taller2','taller3') NOT NULL,
  `descripcion`      TEXT NOT NULL,
  `nombre_contacto`  VARCHAR(120) NOT NULL,
  `telefono`         VARCHAR(30) NOT NULL DEFAULT '',
  `direccion`        VARCHAR(200) NOT NULL DEFAULT '',
  `urgencia`         ENUM('normal','urgente','flexible') NOT NULL DEFAULT 'normal',
  `status`           ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `admin_notes`      TEXT DEFAULT NULL,
  `reviewed_by`      INT UNSIGNED DEFAULT NULL,
  `reviewed_at`      DATETIME DEFAULT NULL,
  `email_sent`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_srv_user`   (`user_id`),
  KEY `idx_srv_status` (`status`),
  KEY `idx_srv_taller` (`taller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar ajustes de email en settings si no existen
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `updated_by`) VALUES
  ('smtp_host',     'smtp.gmail.com', 1),
  ('smtp_port',     '587',            1),
  ('smtp_user',     '',               1),
  ('smtp_pass',     '',               1),
  ('smtp_from',     '',               1),
  ('smtp_from_name','Cooperativa FLB',1);
