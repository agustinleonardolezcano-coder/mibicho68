-- ══════════════════════════════════════════════════════
--  Migración: Sistema de Solicitudes
--  Ejecutar en phpMyAdmin (una sola vez)
-- ══════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `solicitudes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `campaign_id` INT UNSIGNED DEFAULT NULL,
  `dni` VARCHAR(20) NOT NULL,
  `talle` VARCHAR(20) DEFAULT NULL,
  `cantidad` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sol_user`   (`user_id`),
  KEY `idx_sol_status` (`status`),
  KEY `idx_sol_camp`   (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
