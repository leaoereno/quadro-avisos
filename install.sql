-- ============================================================
-- Quadro de Avisos — Script de instalação do banco de dados
-- Versão 1.3.1 — Compatível com MySQL 5.7+ / MariaDB 10.3+
-- ============================================================
-- NOTA: Os tipos de usrgrpid e userid são detectados dinamicamente
-- a partir das tabelas originais do Zabbix para evitar erros de FK.
-- ============================================================

DROP PROCEDURE IF EXISTS `quadro_avisos_install`;

DELIMITER $$

CREATE PROCEDURE `quadro_avisos_install`()
BEGIN
    DECLARE v_usrgrpid_type VARCHAR(64);
    DECLARE v_userid_type   VARCHAR(64);
    DECLARE v_col_sql       TEXT;

    -- Descobre o tipo exato de usrgrp.usrgrpid e users.userid
    SELECT COLUMN_TYPE INTO v_usrgrpid_type
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'usrgrp'
       AND COLUMN_NAME  = 'usrgrpid';

    SELECT COLUMN_TYPE INTO v_userid_type
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'users'
       AND COLUMN_NAME  = 'userid';

    -- Cria a tabela apenas se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'quadro_avisos'
    ) THEN
        SET v_col_sql = CONCAT(
            'CREATE TABLE `quadro_avisos` (',
            '  `id`            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,',
            '  `titulo`        VARCHAR(255)      NOT NULL,',
            '  `conteudo`      MEDIUMTEXT        NOT NULL,',
            '  `tipo_borda`    VARCHAR(32)       NOT NULL DEFAULT ''info'',',
            '  `criado_por`    ', v_userid_type,  ' NOT NULL COMMENT ''users.userid'',',
            '  `usrgrpid`      ', v_usrgrpid_type,' DEFAULT NULL COMMENT ''usrgrp.usrgrpid — NULL quando para_todos=1'',',
            '  `para_todos`    TINYINT(1)        NOT NULL DEFAULT 0',
            '                  COMMENT ''1 = visível para todos os grupos (ignora usrgrpid)'',',
            '  `inicio`        DATETIME          NOT NULL,',
            '  `fim`           DATETIME          NOT NULL,',
            '  `criado_em`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,',
            '  `atualizado_em` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
            '  PRIMARY KEY (`id`),',
            '  KEY `idx_periodo`    (`inicio`, `fim`),',
            '  KEY `idx_usrgrpid`   (`usrgrpid`),',
            '  KEY `idx_criado_por` (`criado_por`),',
            '  KEY `idx_para_todos` (`para_todos`),',
            '  KEY `idx_qa_ativos`  (`usrgrpid`, `inicio`, `fim`),',
            '  CONSTRAINT `fk_qa_usrgrpid`  FOREIGN KEY (`usrgrpid`)  REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE,',
            '  CONSTRAINT `fk_qa_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `users`  (`userid`)  ON DELETE CASCADE',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            '  COMMENT=''Quadro de Avisos — mensagens para equipes no Zabbix'';'
        );
        SET @sql = v_col_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        SELECT 'Tabela quadro_avisos criada com sucesso.' AS resultado;
    ELSE
        SELECT 'Tabela quadro_avisos já existe — nenhuma alteração feita.' AS resultado;
    END IF;
END$$

DELIMITER ;

CALL `quadro_avisos_install`();
DROP PROCEDURE IF EXISTS `quadro_avisos_install`;

-- ============================================================
-- MIGRAÇÃO (apenas se estiver atualizando da v1.2 para v1.3)
-- Descomente e execute separadamente se a tabela já existir:
-- ============================================================
-- ALTER TABLE `quadro_avisos`
--     MODIFY COLUMN `usrgrpid` <MESMO_TIPO_DE_usrgrp.usrgrpid> DEFAULT NULL,
--     ADD COLUMN `para_todos` TINYINT(1) NOT NULL DEFAULT 0
--         COMMENT '1 = visível para todos os grupos'
--         AFTER `usrgrpid`;
-- UPDATE `quadro_avisos` SET `para_todos` = 1, `usrgrpid` = NULL WHERE `usrgrpid` = 0;
-- ALTER TABLE `quadro_avisos`
--     ADD CONSTRAINT `fk_qa_usrgrpid`
--         FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE;
-- ============================================================
