-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(EXISTS (SELECT LOWER(TRIM(nome_local)),LOWER(TRIM(endereco_local)) FROM LocalEsp GROUP BY LOWER(TRIM(nome_local)),LOWER(TRIM(endereco_local)) HAVING COUNT(*)>1), 'SELECT * FROM `ERRO_locais_duplicados_resolver_antes_de_migrar`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='LocalEsp' AND column_name='foto_local'), 'ALTER TABLE LocalEsp ADD COLUMN foto_local VARCHAR(255) NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='LocalEsp' AND column_name='tipo_local' AND column_type LIKE '%campo%'), 'ALTER TABLE LocalEsp MODIFY COLUMN tipo_local ENUM(''quadra'',''poliesportivo'',''clube'',''ginásio'',''campo'') NOT NULL DEFAULT ''poliesportivo''', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='LocalEsp' GROUP BY index_name,non_unique) z WHERE cols='nome_local,endereco_local' AND non_unique=0), 'ALTER TABLE LocalEsp ADD UNIQUE INDEX uq_local_nome_endereco (nome_local, endereco_local)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Denuncia' AND column_name='id_local'), 'ALTER TABLE Denuncia ADD COLUMN id_local INT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Denuncia' AND column_name='id_local' AND referenced_table_name='LocalEsp'), 'ALTER TABLE Denuncia ADD CONSTRAINT fk_denuncia_local FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE SET NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
