-- ============================================================
-- Notice Board (module-zbx-notice-board) -- Install script
-- Version: 1.4.0 -- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================
-- NOTE: usrgrpid and userid column types are detected at runtime
-- from the Zabbix schema to avoid FK compatibility errors.
-- ============================================================

DROP PROCEDURE IF EXISTS `notice_board_install`;

DELIMITER $$

CREATE PROCEDURE `notice_board_install`()
BEGIN
    DECLARE v_usrgrpid_type VARCHAR(64);
    DECLARE v_userid_type   VARCHAR(64);

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

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'notice_board'
    ) THEN
        SET @sql = CONCAT(
            'CREATE TABLE `notice_board` (',
            '  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,',
            '  `titulo`        VARCHAR(255)    NOT NULL,',
            '  `conteudo`      MEDIUMTEXT      NOT NULL,',
            '  `tipo_borda`    VARCHAR(32)     NOT NULL DEFAULT ''info'',',
            '  `criado_por`    ', v_userid_type,  ' NOT NULL,',
            '  `usrgrpid`      ', v_usrgrpid_type,' DEFAULT NULL,',
            '  `para_todos`    TINYINT(1)      NOT NULL DEFAULT 0,',
            '  `inicio`        DATETIME        NOT NULL,',
            '  `fim`           DATETIME        NOT NULL,',
            '  `source`        VARCHAR(64)     DEFAULT NULL,',
            '  `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,',
            '  `atualizado_em` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
            '  PRIMARY KEY (`id`),',
            '  KEY `idx_nb_periodo`    (`inicio`, `fim`),',
            '  KEY `idx_nb_usrgrpid`   (`usrgrpid`),',
            '  KEY `idx_nb_criado_por` (`criado_por`),',
            '  KEY `idx_nb_para_todos` (`para_todos`),',
            '  KEY `idx_nb_source`     (`source`),',
            '  KEY `idx_nb_ativos`     (`usrgrpid`, `inicio`, `fim`),',
            '  CONSTRAINT `fk_nb_usrgrpid`  FOREIGN KEY (`usrgrpid`)  REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE,',
            '  CONSTRAINT `fk_nb_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `users`  (`userid`)  ON DELETE CASCADE',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT 'notice_board table created successfully.' AS resultado;
    ELSE
        SELECT 'notice_board table already exists -- no changes made.' AS resultado;
    END IF;
END$$

DELIMITER ;

CALL `notice_board_install`();
DROP PROCEDURE IF EXISTS `notice_board_install`;
