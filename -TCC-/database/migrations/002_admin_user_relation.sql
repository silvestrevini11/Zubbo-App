USE app_zubbo;

SET @tem_coluna_admin = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Administrador'
      AND COLUMN_NAME = 'id_user'
);

SET @sql_admin_coluna = IF(
    @tem_coluna_admin = 0,
    'ALTER TABLE Administrador ADD COLUMN id_user INT NULL AFTER id_adm',
    'SELECT 1'
);

PREPARE stmt_admin_coluna FROM @sql_admin_coluna;
EXECUTE stmt_admin_coluna;
DEALLOCATE PREPARE stmt_admin_coluna;

UPDATE Administrador a
INNER JOIN Usuario u
    ON LOWER(TRIM(u.email_user)) = LOWER(TRIM(a.email_adm))
SET a.id_user = u.id_user
WHERE a.id_user IS NULL;

SET @tem_indice_admin = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Administrador'
      AND INDEX_NAME = 'uq_admin_usuario'
);

SET @sql_admin_indice = IF(
    @tem_indice_admin = 0,
    'ALTER TABLE Administrador ADD UNIQUE INDEX uq_admin_usuario (id_user)',
    'SELECT 1'
);

PREPARE stmt_admin_indice FROM @sql_admin_indice;
EXECUTE stmt_admin_indice;
DEALLOCATE PREPARE stmt_admin_indice;

SET @tem_fk_admin = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Administrador'
      AND CONSTRAINT_NAME = 'fk_admin_usuario'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_admin_fk = IF(
    @tem_fk_admin = 0,
    'ALTER TABLE Administrador ADD CONSTRAINT fk_admin_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE',
    'SELECT 1'
);

PREPARE stmt_admin_fk FROM @sql_admin_fk;
EXECUTE stmt_admin_fk;
DEALLOCATE PREPARE stmt_admin_fk;
