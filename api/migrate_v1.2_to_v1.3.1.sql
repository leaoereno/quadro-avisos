-- ============================================================
-- Notice Board -- Migration quadro_avisos v1.2 -> v1.3.1
-- Safe to re-run (checks before each ALTER)
-- Execute: mysql -u root -p zabbix < migrate_v1.2_to_v1.3.1.sql
-- ============================================================

DROP PROCEDURE IF EXISTS `nb_migrate_v13`;

DELIMITER $$

CREATE PROCEDURE `nb_migrate_v13`()
BEGIN
    DECLARE v_usrgrpid_type VARCHAR(64);
    DECLARE v_has_para_todos INT DEFAULT 0;
    DECLARE v_has_atualizado INT DEFAULT 0;
    DECLARE v_has_fk         INT DEFAULT 0;

    SELECT COLUMN_TYPE INTO v_usrgrpid_type
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'usrgrp'
       AND COLUMN_NAME  = 'usrgrpid';

    SELECT COUNT(*) INTO v_has_para_todos
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'quadro_avisos'
       AND COLUMN_NAME  = 'para_todos';

    SELECT COUNT(*) INTO v_has_atualizado
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'quadro_avisos'
       AND COLUMN_NAME  = 'atualizado_em';

    SELECT COUNT(*) INTO v_has_fk
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA    = DATABASE()
       AND TABLE_NAME      = 'quadro_avisos'
       AND CONSTRAINT_NAME = 'fk_qa_usrgrpid'
       AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_has_para_todos = 0 THEN
        ALTER TABLE `quadro_avisos`
            ADD COLUMN `para_todos` TINYINT(1) NOT NULL DEFAULT 0 AFTER `usrgrpid`;
        SELECT 'OK: column para_todos added' AS resultado;
    ELSE
        SELECT 'SKIP: column para_todos already exists' AS resultado;
    END IF;

    IF v_has_atualizado = 0 THEN
        ALTER TABLE `quadro_avisos`
            ADD COLUMN `atualizado_em` DATETIME NOT NULL
            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            AFTER `criado_em`;
        SELECT 'OK: column atualizado_em added' AS resultado;
    ELSE
        SELECT 'SKIP: column atualizado_em already exists' AS resultado;
    END IF;

    UPDATE `quadro_avisos` SET `para_todos` = 1, `usrgrpid` = NULL WHERE `usrgrpid` = 0;
    SELECT CONCAT('OK: migrated records (usrgrpid=0 -> para_todos=1): ', ROW_COUNT()) AS resultado;

    SET @alter_col = CONCAT(
        'ALTER TABLE `quadro_avisos` MODIFY COLUMN `usrgrpid` ',
        v_usrgrpid_type, ' DEFAULT NULL'
    );
    PREPARE stmt FROM @alter_col;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    SELECT CONCAT('OK: usrgrpid column adjusted to type: ', v_usrgrpid_type) AS resultado;

    IF v_has_fk = 0 THEN
        ALTER TABLE `quadro_avisos`
            ADD CONSTRAINT `fk_qa_usrgrpid`
            FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE;
        SELECT 'OK: FK fk_qa_usrgrpid added' AS resultado;
    ELSE
        SELECT 'SKIP: FK fk_qa_usrgrpid already exists' AS resultado;
    END IF;

    BEGIN
        DECLARE CONTINUE HANDLER FOR SQLSTATE '42000' BEGIN END;
        ALTER TABLE `quadro_avisos` ADD KEY `idx_para_todos` (`para_todos`);
        SELECT 'OK: index idx_para_todos added' AS resultado;
    END;

    SELECT 'DONE: migration quadro_avisos v1.2 -> v1.3.1 complete' AS resultado;
END$$

DELIMITER ;

CALL `nb_migrate_v13`();
DROP PROCEDURE IF EXISTS `nb_migrate_v13`;
