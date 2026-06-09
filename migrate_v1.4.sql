-- ============================================================
-- Quadro de Avisos — Migração v1.3.1 → v1.4 (API externa)
-- Adiciona coluna `source` para identificar a origem do aviso
-- ============================================================

ALTER TABLE `quadro_avisos`
    ADD COLUMN `source` VARCHAR(64) NULL DEFAULT NULL
        COMMENT 'Identificador da fonte remota (ex: grafana, servicenow, api)'
        AFTER `atualizado_em`;

ALTER TABLE `quadro_avisos`
    ADD KEY `idx_source` (`source`);
