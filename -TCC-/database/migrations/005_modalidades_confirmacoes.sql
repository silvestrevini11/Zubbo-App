-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='imagem_esporte'), 'ALTER TABLE Esporte ADD COLUMN imagem_esporte VARCHAR(255) NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='regras_esporte'), 'ALTER TABLE Esporte ADD COLUMN regras_esporte TEXT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='jogadores_por_time'), 'ALTER TABLE Esporte ADD COLUMN jogadores_por_time SMALLINT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='quantidade_times'), 'ALTER TABLE Esporte ADD COLUMN quantidade_times TINYINT NOT NULL DEFAULT 2', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='exige_confirmacao_local'), 'ALTER TABLE Esporte ADD COLUMN exige_confirmacao_local BOOLEAN NOT NULL DEFAULT FALSE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='exige_confirmacao_local' AND column_default <> '0'), 'ALTER TABLE Esporte ALTER COLUMN exige_confirmacao_local SET DEFAULT FALSE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
CREATE TABLE IF NOT EXISTS Local_Esporte_Confirmacao (
    id_local INT NOT NULL,
    id_esporte INT NOT NULL,
    id_adm INT NOT NULL,
    referencia_contato VARCHAR(150) NOT NULL,
    confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_local,id_esporte),
    CONSTRAINT fk_lec_local FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE,
    CONSTRAINT fk_lec_esporte FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE,
    CONSTRAINT fk_lec_adm FOREIGN KEY (id_adm) REFERENCES Administrador(id_adm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS Local_Esporte_Permitido (
    id_local INT NOT NULL,
 id_esporte INT NOT NULL,
 fonte VARCHAR(500) NOT NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id_local,id_esporte),
 FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE,
 FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BEGIN GENERATED CONTRACT
-- Gerado por php database/tools/migration-contract.php --write.
-- Completa apenas colunas com NULL/default e vínculos/índices ausentes.
-- Definições incompatíveis interrompem sem apagar ou adivinhar dados.
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='imagem_esporte' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='varchar(255)' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Esporte_imagem_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='regras_esporte' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='text' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Esporte_regras_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='jogadores_por_time' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='smallint' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Esporte_jogadores_por_time`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='quantidade_times' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='tinyint' AND is_nullable='NO' AND REPLACE(column_default,'()','')='2' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Esporte_quantidade_times`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='exige_confirmacao_local' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='tinyint' AND is_nullable='NO' AND REPLACE(column_default,'()','')='0' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Esporte_exige_confirmacao_local`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Esporte' GROUP BY index_name,non_unique) z WHERE cols='id_esporte' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Esporte ADD PRIMARY KEY (id_esporte)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Esporte' GROUP BY index_name,non_unique) z WHERE cols='nome_esporte' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Esporte ADD UNIQUE KEY (nome_esporte)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='confirmado_em'), 'ALTER TABLE Local_Esporte_Confirmacao ADD COLUMN confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='id_local' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Confirmacao_id_local`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='id_esporte' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Confirmacao_id_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='id_adm' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Confirmacao_id_adm`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='referencia_contato' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='varchar(150)' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Confirmacao_referencia_contato`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='confirmado_em' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Confirmacao_confirmado_em`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' GROUP BY index_name,non_unique) z WHERE cols='id_local,id_esporte' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Local_Esporte_Confirmacao ADD PRIMARY KEY (id_local,id_esporte)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='id_local' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Confirmacao' AND k.column_name='id_local' AND k.referenced_table_name='LocalEsp' AND k.referenced_column_name='id_local' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Local_Esporte_Confirmacao_id_local`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Confirmacao' AND k.column_name='id_local' AND k.referenced_table_name='LocalEsp' AND k.referenced_column_name='id_local' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Local_Esporte_Confirmacao ADD CONSTRAINT fk_lec_local FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='id_esporte' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Confirmacao' AND k.column_name='id_esporte' AND k.referenced_table_name='Esporte' AND k.referenced_column_name='id_esporte' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Local_Esporte_Confirmacao_id_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Confirmacao' AND k.column_name='id_esporte' AND k.referenced_table_name='Esporte' AND k.referenced_column_name='id_esporte' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Local_Esporte_Confirmacao ADD CONSTRAINT fk_lec_esporte FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao' AND column_name='id_adm' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Confirmacao' AND k.column_name='id_adm' AND k.referenced_table_name='Administrador' AND k.referenced_column_name='id_adm' AND r.delete_rule IN ('RESTRICT', 'NO ACTION') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Local_Esporte_Confirmacao_id_adm`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Confirmacao' AND k.column_name='id_adm' AND k.referenced_table_name='Administrador' AND k.referenced_column_name='id_adm' AND r.delete_rule IN ('RESTRICT', 'NO ACTION') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Local_Esporte_Confirmacao ADD CONSTRAINT fk_lec_adm FOREIGN KEY (id_adm) REFERENCES Administrador(id_adm)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='atualizado_em'), 'ALTER TABLE Local_Esporte_Permitido ADD COLUMN atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='id_local' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Permitido_id_local`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='id_esporte' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Permitido_id_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='fonte' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='varchar(500)' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Permitido_fonte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='atualizado_em' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Local_Esporte_Permitido_atualizado_em`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' GROUP BY index_name,non_unique) z WHERE cols='id_local,id_esporte' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Local_Esporte_Permitido ADD PRIMARY KEY (id_local,id_esporte)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='id_local' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Permitido' AND k.column_name='id_local' AND k.referenced_table_name='LocalEsp' AND k.referenced_column_name='id_local' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Local_Esporte_Permitido_id_local`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Permitido' AND k.column_name='id_local' AND k.referenced_table_name='LocalEsp' AND k.referenced_column_name='id_local' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Local_Esporte_Permitido ADD FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Permitido' AND column_name='id_esporte' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Permitido' AND k.column_name='id_esporte' AND k.referenced_table_name='Esporte' AND k.referenced_column_name='id_esporte' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Local_Esporte_Permitido_id_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Local_Esporte_Permitido' AND k.column_name='id_esporte' AND k.referenced_table_name='Esporte' AND k.referenced_column_name='id_esporte' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Local_Esporte_Permitido ADD FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
-- END GENERATED CONTRACT
