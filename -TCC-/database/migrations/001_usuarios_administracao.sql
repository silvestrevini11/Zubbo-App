-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Usuario' AND column_name='sobre_mim'), 'ALTER TABLE Usuario ADD COLUMN sobre_mim TEXT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Administrador' AND column_name='id_user'), 'ALTER TABLE Administrador ADD COLUMN id_user INT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT id_user FROM Administrador WHERE id_user IS NOT NULL GROUP BY id_user HAVING COUNT(*)>1), 'SELECT * FROM `ERRO_administradores_duplicados_resolver_antes_de_migrar`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Administrador' GROUP BY index_name,non_unique) z WHERE cols='id_user' AND non_unique=0), 'ALTER TABLE Administrador ADD UNIQUE INDEX uq_admin_usuario (id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Administrador' AND column_name='id_user' AND referenced_table_name='Usuario'), 'ALTER TABLE Administrador ADD CONSTRAINT fk_admin_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
CREATE TABLE IF NOT EXISTS Recuperacao_Senha (
    id_recuperacao INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expiracao DATETIME NOT NULL,
    usado BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_recuperacao_usuario (id_user),
    INDEX idx_recuperacao_expiracao (expiracao),

    CONSTRAINT fk_recuperacao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS Bloqueio_Usuario (
    id_bloqueador INT NOT NULL,
    id_bloqueado INT NOT NULL,
    data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bloqueador, id_bloqueado),
    CONSTRAINT fk_bloqueio_de FOREIGN KEY (id_bloqueador) REFERENCES Usuario(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_bloqueio_para FOREIGN KEY (id_bloqueado) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BEGIN GENERATED CONTRACT
-- Gerado por php database/tools/migration-contract.php --write.
-- Completa apenas colunas com NULL/default e vínculos/índices ausentes.
-- Definições incompatíveis interrompem sem apagar ou adivinhar dados.
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Usuario' AND column_name='sobre_mim' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='text' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Usuario_sobre_mim`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Usuario' GROUP BY index_name,non_unique) z WHERE cols='id_user' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Usuario ADD PRIMARY KEY (id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Usuario' GROUP BY index_name,non_unique) z WHERE cols='email_user' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Usuario ADD UNIQUE KEY (email_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Administrador' AND column_name='id_user' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Administrador_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Administrador' GROUP BY index_name,non_unique) z WHERE cols='id_adm' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Administrador ADD PRIMARY KEY (id_adm)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Administrador' GROUP BY index_name,non_unique) z WHERE cols='email_adm' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Administrador ADD UNIQUE KEY (email_adm)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Administrador' GROUP BY index_name,non_unique) z WHERE cols='id_user' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Administrador ADD UNIQUE KEY uq_admin_usuario (id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Administrador' AND column_name='id_user' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Administrador' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Administrador_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Administrador' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Administrador ADD CONSTRAINT fk_admin_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='usado'), 'ALTER TABLE Recuperacao_Senha ADD COLUMN usado BOOLEAN NOT NULL DEFAULT FALSE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='data_criacao'), 'ALTER TABLE Recuperacao_Senha ADD COLUMN data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='id_recuperacao' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=1 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Recuperacao_Senha_id_recuperacao`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='id_user' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Recuperacao_Senha_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='token_hash' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='char(64)' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Recuperacao_Senha_token_hash`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='expiracao' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Recuperacao_Senha_expiracao`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='usado' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='tinyint' AND is_nullable='NO' AND REPLACE(column_default,'()','')='0' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Recuperacao_Senha_usado`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='data_criacao' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Recuperacao_Senha_data_criacao`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' GROUP BY index_name,non_unique) z WHERE cols='id_recuperacao' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Recuperacao_Senha ADD PRIMARY KEY (id_recuperacao)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' GROUP BY index_name,non_unique) z WHERE cols='token_hash' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Recuperacao_Senha ADD UNIQUE KEY (token_hash)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' GROUP BY index_name,non_unique) z WHERE cols='id_user' AND non_unique=1 AND prefixes=0), 'ALTER TABLE Recuperacao_Senha ADD INDEX idx_recuperacao_usuario (id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' GROUP BY index_name,non_unique) z WHERE cols='expiracao' AND non_unique=1 AND prefixes=0), 'ALTER TABLE Recuperacao_Senha ADD INDEX idx_recuperacao_expiracao (expiracao)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Recuperacao_Senha' AND column_name='id_user' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Recuperacao_Senha' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Recuperacao_Senha_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Recuperacao_Senha' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Recuperacao_Senha ADD CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' AND column_name='data_bloqueio'), 'ALTER TABLE Bloqueio_Usuario ADD COLUMN data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' AND column_name='id_bloqueador' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Bloqueio_Usuario_id_bloqueador`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' AND column_name='id_bloqueado' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Bloqueio_Usuario_id_bloqueado`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' AND column_name='data_bloqueio' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Bloqueio_Usuario_data_bloqueio`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' GROUP BY index_name,non_unique) z WHERE cols='id_bloqueador,id_bloqueado' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Bloqueio_Usuario ADD PRIMARY KEY (id_bloqueador, id_bloqueado)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' AND column_name='id_bloqueador' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Bloqueio_Usuario' AND k.column_name='id_bloqueador' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Bloqueio_Usuario_id_bloqueador`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Bloqueio_Usuario' AND k.column_name='id_bloqueador' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Bloqueio_Usuario ADD CONSTRAINT fk_bloqueio_de FOREIGN KEY (id_bloqueador) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Bloqueio_Usuario' AND column_name='id_bloqueado' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Bloqueio_Usuario' AND k.column_name='id_bloqueado' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Bloqueio_Usuario_id_bloqueado`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Bloqueio_Usuario' AND k.column_name='id_bloqueado' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Bloqueio_Usuario ADD CONSTRAINT fk_bloqueio_para FOREIGN KEY (id_bloqueado) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
-- END GENERATED CONTRACT
