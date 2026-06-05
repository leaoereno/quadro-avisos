-- ============================================================
-- Quadro de Avisos — Script de instalação do banco de dados
-- Versão 1.3.0 — Compatível com MySQL 5.7+ / MariaDB 10.3+
-- Execute como root ou usuário com permissão no banco do Zabbix
-- ============================================================

-- CORREÇÃO: usrgrpid=0 violava a FK de usrgrp.usrgrpid.
-- A coluna `para_todos` substitui o uso de usrgrpid=0 para
-- indicar "visível para todos os grupos". A FK agora é segura.

CREATE TABLE IF NOT EXISTS `quadro_avisos` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`        VARCHAR(255)     NOT NULL,
  `conteudo`      MEDIUMTEXT       NOT NULL,
  `tipo_borda`    VARCHAR(32)      NOT NULL DEFAULT 'info'
                  COMMENT 'info | success | warning | danger | mudanca | evento',
  `criado_por`    INT UNSIGNED     NOT NULL COMMENT 'users.userid',
  `usrgrpid`      INT UNSIGNED              COMMENT 'usrgrp.usrgrpid — NULL quando para_todos=1',
  `para_todos`    TINYINT(1)       NOT NULL DEFAULT 0
                  COMMENT '1 = visível para todos os grupos (ignora usrgrpid)',
  `inicio`        DATETIME         NOT NULL COMMENT 'Início da exibição',
  `fim`           DATETIME         NOT NULL COMMENT 'Fim da exibição',
  `criado_em`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_periodo`    (`inicio`, `fim`),
  KEY `idx_usrgrpid`   (`usrgrpid`),
  KEY `idx_criado_por` (`criado_por`),
  KEY `idx_para_todos` (`para_todos`),
  CONSTRAINT `fk_qa_usrgrpid`
      FOREIGN KEY (`usrgrpid`)  REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_criado_por`
      FOREIGN KEY (`criado_por`) REFERENCES `users`  (`userid`)  ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Quadro de Avisos — mensagens para equipes no Zabbix';

-- Índice composto para a query do dashboard (avisos ativos do grupo)
CREATE INDEX IF NOT EXISTS `idx_qa_ativos`
    ON `quadro_avisos` (`usrgrpid`, `inicio`, `fim`);

-- ============================================================
-- MIGRAÇÃO (se estiver atualizando da v1.2 para v1.3)
-- Execute somente se a tabela já existir:
-- ============================================================
-- ALTER TABLE `quadro_avisos`
--     ADD COLUMN `para_todos` TINYINT(1) NOT NULL DEFAULT 0
--         COMMENT '1 = visível para todos os grupos'
--         AFTER `usrgrpid`,
--     MODIFY COLUMN `usrgrpid` INT UNSIGNED NULL
--         COMMENT 'usrgrp.usrgrpid — NULL quando para_todos=1';
--
-- -- Converte os registros antigos com usrgrpid=0
-- UPDATE `quadro_avisos` SET `para_todos` = 1, `usrgrpid` = NULL WHERE `usrgrpid` = 0;
--
-- -- Adiciona a FK (só depois de limpar os usrgrpid=0)
-- ALTER TABLE `quadro_avisos`
--     ADD CONSTRAINT `fk_qa_usrgrpid`
--         FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE;
-- ============================================================
