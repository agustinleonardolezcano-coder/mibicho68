-- ══════════════════════════════════════════════════════
--  Migración: Tabla team_members (Nosotros)
--  Ejecutar en phpMyAdmin — UNA SOLA VEZ
-- ══════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `team_members` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(150) NOT NULL,
  `role`       VARCHAR(150) NOT NULL DEFAULT '',
  `section`    VARCHAR(60)  NOT NULL DEFAULT 'consejo_autoridades',
  `area`       VARCHAR(20)  NOT NULL DEFAULT 'doc',
  `image`      VARCHAR(255) DEFAULT NULL,
  `init_text`  VARCHAR(10)  DEFAULT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `activo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_section` (`section`),
  KEY `idx_order`   (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores de `section` válidos:
--   consejo_autoridades | consejo_sindicos | junta_escrutadora
--   junta_revisora      | docentes_asesores | vocalistas
--
-- Valores de `area` válidos:
--   info (Informática) | elec (Electromecánica) | auto (Automotriz) | doc (Directivo)
