-- ═══════════════════════════════════════════════════════
--  Cooperativa FLB — Esquema completo (con sub-campañas)
-- ═══════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `dni` VARCHAR(20) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('donor','admin') NOT NULL DEFAULT 'donor',
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `theme` ENUM('dark','light') NOT NULL DEFAULT 'dark',
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `campaigns` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `goal_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `current_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` ENUM('active','paused','completed') NOT NULL DEFAULT 'active',
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `emoji` VARCHAR(10) DEFAULT '🎯',
  `image` VARCHAR(255) DEFAULT NULL,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `campaign_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('mercadopago','card','manual') NOT NULL DEFAULT 'mercadopago',
  `mp_preference_id` VARCHAR(200) DEFAULT NULL,
  `mp_payment_id` VARCHAR(200) DEFAULT NULL,
  `mp_status` VARCHAR(100) DEFAULT NULL,
  `receipt_token` VARCHAR(64) DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `confirmed_by` INT UNSIGNED DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_campaign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  UNIQUE KEY `uq_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) DEFAULT NULL,
  `target_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Configuración inicial ───
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_subtitle',    'Juntos construimos el futuro de nuestros estudiantes'),
('hero_message',     'Cada aporte hace la diferencia. Ayudá a los chicos de la Escuela Fray Luis Beltrán a tener la ropa, las herramientas y el espacio que merecen para crecer y aprender.'),
('goal_total',       '500000'),
('donation_amounts', '500,1000,2000'),
('contact_email',    'cooperativa@flb.edu.ar');

-- ─── Campañas principales ───
INSERT IGNORE INTO `campaigns` (`id`,`name`,`description`,`goal_amount`,`status`,`emoji`,`created_by`) VALUES
(1, 'Indumentaria Escolar',  'Recaudación para vestimenta institucional: ropa de trabajo, educación física y uniforme para todos los estudiantes.',               150000, 'active', '👕', 1),
(2, 'Reparación de Aulas',   'Fondo para reparación y mantenimiento de aulas, ventanas, puertas y mobiliario escolar deteriorado.',                              200000, 'active', '🏫', 1),
(3, 'Aires Acondicionados',  'Instalación de sistemas de climatización en las aulas para mejorar las condiciones de aprendizaje durante todo el año.',            250000, 'active', '❄️', 1);

-- ─── Sub-campañas de Indumentaria Escolar (parent_id = 1) ───
INSERT IGNORE INTO `campaigns` (`id`,`name`,`description`,`goal_amount`,`status`,`emoji`,`parent_id`,`created_by`) VALUES
(4, 'Ropa Normal (chomba y pantalón)',  'Chombas institucionales bordadas y pantalones de trabajo para uso diario en la institución.',       50000, 'active', '👔', 1, 1),
(5, 'Ropa de Educación Física',         'Buzo, pantalón de jogging y zapatillas para las clases de educación física y deportes.',              45000, 'active', '🏃', 1, 1),
(6, 'Ropa de Grafa',                    'Ropa de trabajo resistente (grafa): camisa, pantalón y botas de seguridad para talleres y prácticas.', 55000, 'active', '🦺', 1, 1);
