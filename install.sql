-- ============================================================
-- Quadro de Avisos — Script de instalação do banco de dados
-- Compatível com MySQL 5.7+ / MariaDB 10.3+
-- Execute como root ou usuário com permissão no banco do Zabbix
-- ============================================================

CREATE TABLE IF NOT EXISTS `quadro_avisos` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`        VARCHAR(255)     NOT NULL,
  `conteudo`      MEDIUMTEXT       NOT NULL,
  `tipo_borda`    VARCHAR(32)      NOT NULL DEFAULT 'info'
                  COMMENT 'info | success | warning | danger | mudanca | evento',
  `criado_por`    INT UNSIGNED     NOT NULL COMMENT 'users.userid',
  `usrgrpid`      INT UNSIGNED     NOT NULL COMMENT 'usrgrp.usrgrpid',
  `inicio`        DATETIME         NOT NULL COMMENT 'Início da exibição',
  `fim`           DATETIME         NOT NULL COMMENT 'Fim da exibição',
  `criado_em`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_periodo`  (`inicio`, `fim`),
  KEY `idx_usrgrpid` (`usrgrpid`),
  KEY `idx_criado_por` (`criado_por`),
  CONSTRAINT `fk_qa_usrgrpid`
      FOREIGN KEY (`usrgrpid`)  REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_criado_por`
      FOREIGN KEY (`criado_por`) REFERENCES `users`  (`userid`)  ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Quadro de Avisos — mensagens para equipes no Zabbix';

-- Índice extra para consulta no dashboard (apenas ativos)
CREATE INDEX IF NOT EXISTS `idx_qa_ativos`
    ON `quadro_avisos` (`usrgrpid`, `inicio`, `fim`);
