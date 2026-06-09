-- Quadro de Avisos - Migracao v1.2 para v1.3.1
-- Execute: mysql -u root -p zabbix < migrate_v1.2_to_v1.3.1.sql

DROP PROCEDURE IF EXISTS `qa_migrate_v13`;

DELIMITER $$

CREATE PROCEDURE `qa_migrate_v13`()
BEGIN
    DECLARE v_usrgrpid_type VARCHAR(64);
    DECLARE v_has_para_todos INT DEFAULT 0;
    DECLARE v_has_atualizado INT DEFAULT 0;
    DECLARE v_has_fk         INT DEFAULT 0;

    -- 1. Detecta tipo real de usrgrpid no Zabbix
    SELECT COLUMN_TYPE INTO v_usrgrpid_type
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'usrgrp'
       AND COLUMN_NAME  = 'usrgrpid';

    -- 2. Verifica se para_todos ja existe
    SELECT COUNT(*) INTO v_has_para_todos
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'quadro_avisos'
       AND COLUMN_NAME  = 'para_todos';

    -- 3. Verifica se atualizado_em ja existe
    SELECT COUNT(*) INTO v_has_atualizado
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'quadro_avisos'
       AND COLUMN_NAME  = 'atualizado_em';

    -- 4. Verifica se a FK de usrgrpid ja existe
    SELECT COUNT(*) INTO v_has_fk
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA    = DATABASE()
       AND TABLE_NAME      = 'quadro_avisos'
       AND CONSTRAINT_NAME = 'fk_qa_usrgrpid'
       AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    -- 5. Adiciona coluna para_todos (se ausente)
    IF v_has_para_todos = 0 THEN
        ALTER TABLE `quadro_avisos`
            ADD COLUMN `para_todos` TINYINT(1) NOT NULL DEFAULT 0
            AFTER `usrgrpid`;
        SELECT 'OK: coluna para_todos adicionada' AS resultado;
    ELSE
        SELECT 'SKIP: coluna para_todos ja existe' AS resultado;
    END IF;

    -- 6. Adiciona coluna atualizado_em (se ausente)
    IF v_has_atualizado = 0 THEN
        ALTER TABLE `quadro_avisos`
            ADD COLUMN `atualizado_em` DATETIME NOT NULL
            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            AFTER `criado_em`;
        SELECT 'OK: coluna atualizado_em adicionada' AS resultado;
    ELSE
        SELECT 'SKIP: coluna atualizado_em ja existe' AS resultado;
    END IF;

    -- 7. Migra registros antigos com usrgrpid=0 para para_todos=1
    UPDATE `quadro_avisos`
       SET `para_todos` = 1,
           `usrgrpid`   = NULL
     WHERE `usrgrpid` = 0;
    SELECT CONCAT('OK: registros migrados (usrgrpid=0 -> para_todos=1): ', ROW_COUNT()) AS resultado;

    -- 8. Ajusta tipo da coluna usrgrpid para aceitar NULL e compatibilizar FK
    SET @alter_col = CONCAT(
        'ALTER TABLE `quadro_avisos` MODIFY COLUMN `usrgrpid` ',
        v_usrgrpid_type,
        ' DEFAULT NULL'
    );
    PREPARE stmt FROM @alter_col;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    SELECT CONCAT('OK: coluna usrgrpid ajustada para tipo: ', v_usrgrpid_type) AS resultado;

    -- 9. Adiciona FK (se ausente)
    IF v_has_fk = 0 THEN
        ALTER TABLE `quadro_avisos`
            ADD CONSTRAINT `fk_qa_usrgrpid`
            FOREIGN KEY (`usrgrpid`) REFERENCES `usrgrp` (`usrgrpid`) ON DELETE CASCADE;
        SELECT 'OK: FK fk_qa_usrgrpid adicionada' AS resultado;
    ELSE
        SELECT 'SKIP: FK fk_qa_usrgrpid ja existe' AS resultado;
    END IF;

    -- 10. Indice auxiliar para para_todos
    BEGIN
        DECLARE CONTINUE HANDLER FOR SQLSTATE '42000' BEGIN END;
        ALTER TABLE `quadro_avisos` ADD KEY `idx_para_todos` (`para_todos`);
        SELECT 'OK: indice idx_para_todos adicionado' AS resultado;
    END;

    SELECT 'CONCLUIDO: migracao v1.2 -> v1.3.1 finalizada' AS resultado;
END$$

DELIMITER ;

CALL `qa_migrate_v13`();
DROP PROCEDURE IF EXISTS `qa_migrate_v13`;
