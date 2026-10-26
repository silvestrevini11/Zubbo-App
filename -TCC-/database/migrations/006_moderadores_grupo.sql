-- Moderação adicional; o responsável atual mantém seus privilégios.
CREATE TABLE IF NOT EXISTS Grupo_Moderador (
    id_grupo INT NOT NULL,
    id_user INT NOT NULL,
    PRIMARY KEY (id_grupo, id_user),
    CONSTRAINT fk_grupo_moderador_grupo
        FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE,
    CONSTRAINT fk_grupo_moderador_usuario
        FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BEGIN GENERATED CONTRACT
-- Gerado por php database/tools/migration-contract.php --write.
-- Completa apenas colunas com NULL/default e vínculos/índices ausentes.
-- Definições incompatíveis interrompem sem apagar ou adivinhar dados.
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Grupo_Moderador' AND column_name='id_grupo' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Grupo_Moderador_id_grupo`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Grupo_Moderador' AND column_name='id_user' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Grupo_Moderador_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Grupo_Moderador' GROUP BY index_name,non_unique) z WHERE cols='id_grupo,id_user' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Grupo_Moderador ADD PRIMARY KEY (id_grupo, id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Grupo_Moderador' AND column_name='id_grupo' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Moderador' AND k.column_name='id_grupo' AND k.referenced_table_name='Grupo' AND k.referenced_column_name='id_grupo' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Grupo_Moderador_id_grupo`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Moderador' AND k.column_name='id_grupo' AND k.referenced_table_name='Grupo' AND k.referenced_column_name='id_grupo' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Grupo_Moderador ADD CONSTRAINT fk_grupo_moderador_grupo FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Grupo_Moderador' AND column_name='id_user' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Moderador' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Grupo_Moderador_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Moderador' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Grupo_Moderador ADD CONSTRAINT fk_grupo_moderador_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
-- END GENERATED CONTRACT
