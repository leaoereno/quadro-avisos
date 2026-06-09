-- ============================================================
-- Notice Board -- Migration v1.3.1 -> v1.4.0
-- Renames table quadro_avisos -> notice_board
-- Adds source column for external API integrations
-- Execute: mysql -u root -p zabbix < migrate_v1.4.sql
-- ============================================================

DROP PROCEDURE IF EXISTS `nb_migrate_v14`;

DELIMITER $$

CREATE PROCEDURE `nb_migrate_v14`()
BEGIN
    DECLARE v_old_exists INT DEFAULT 0;
    DECLARE v_new_exists INT DEFAULT 0;
    DECLARE v_has_source INT DEFAULT 0;

    SELECT COUNT(*) INTO v_old_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quadro_avisos';

    SELECT COUNT(*) INTO v_new_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notice_board';

    -- Step 1: Rename table
    IF v_old_exists = 1 AND v_new_exists = 0 THEN
        RENAME TABLE `quadro_avisos` TO `notice_board`;
        SELECT 'OK: table renamed quadro_avisos -> notice_board' AS resultado;
    ELSEIF v_new_exists = 1 THEN
        SELECT 'SKIP: notice_board already exists' AS resultado;
    ELSE
        SELECT 'SKIP: quadro_avisos not found -- fresh install?' AS resultado;
    END IF;

    -- Step 2: Add source column
    SELECT COUNT(*) INTO v_has_source
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'notice_board'
       AND COLUMN_NAME  = 'source';

    IF v_has_source = 0 THEN
        ALTER TABLE `notice_board`
            ADD COLUMN `source` VARCHAR(64) DEFAULT NULL AFTER `atualizado_em`;
        SELECT 'OK: column source added' AS resultado;
    ELSE
        SELECT 'SKIP: column source already exists' AS resultado;
    END IF;

    -- Step 3: Rename FK constraints to nb_ prefix
    BEGIN
        DECLARE CONTINUE HANDLER FOR SQLSTATE '42000' BEGIN END;
        ALTER TABLE `notice_board`
            DROP FOREIGN KEY `fk_qa_usrgrpid`,
            ADD CONSTRAINT `fk_nb_usrgrpid` FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE;
        SELECT 'OK: FK renamed to fk_nb_usrgrpid' AS resultado;
    END;

    BEGIN
        DECLARE CONTINUE HANDLER FOR SQLSTATE '42000' BEGIN END;
        ALTER TABLE `notice_board`
            DROP FOREIGN KEY `fk_qa_criado_por`,
            ADD CONSTRAINT `fk_nb_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `users` (`userid`) ON DELETE CASCADE;
        SELECT 'OK: FK renamed to fk_nb_criado_por' AS resultado;
    END;

    -- Step 4: Add source index
    BEGIN
        DECLARE CONTINUE HANDLER FOR SQLSTATE '42000' BEGIN END;
        ALTER TABLE `notice_board` ADD KEY `idx_nb_source` (`source`);
        SELECT 'OK: index idx_nb_source added' AS resultado;
    END;

    SELECT 'DONE: migration v1.3.1 -> v1.4.0 complete' AS resultado;
END$$

DELIMITER ;

CALL `nb_migrate_v14`();
DROP PROCEDURE IF EXISTS `nb_migrate_v14`;
