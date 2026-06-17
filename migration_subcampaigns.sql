-- Migración: Sub-campañas e imágenes en campañas
-- Ejecutar una sola vez en la base de datos existente

ALTER TABLE `campaigns`
  ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `emoji`,
  ADD COLUMN `parent_id` INT UNSIGNED DEFAULT NULL AFTER `image`;

-- Índice para consultas de sub-campañas
ALTER TABLE `campaigns`
  ADD KEY `idx_parent` (`parent_id`);

-- Crear directorio de uploads (hacerlo manualmente en el servidor):
-- mkdir -p /htdocs/uploads/campaigns
-- chmod 755 /htdocs/uploads/campaigns
