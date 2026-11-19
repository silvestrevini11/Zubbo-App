-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='data_leitura'), 'ALTER TABLE Notificacao ADD COLUMN data_leitura DATETIME NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Notificacao' GROUP BY index_name,non_unique) z WHERE cols='id_destinatario,lida,data_leitura'), 'ALTER TABLE Notificacao ADD INDEX idx_notificacao_expirar (id_destinatario, lida, data_leitura)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_evento'), 'ALTER TABLE Notificacao ADD COLUMN id_evento INT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_evento' AND referenced_table_name='Evento'), 'ALTER TABLE Notificacao ADD CONSTRAINT fk_notificacao_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
CREATE TABLE IF NOT EXISTS PushSubscription (
    id_push BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    endpoint_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    dados_json JSON NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_endpoint (endpoint_hash),
    INDEX idx_push_usuario (id_user),
    CONSTRAINT fk_push_user FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
UPDATE Notificacao SET data_leitura=NOW() WHERE lida=TRUE AND data_leitura IS NULL;

-- BEGIN GENERATED CONTRACT
-- Gerado por php database/tools/migration-contract.php --write.
-- Completa apenas colunas com NULL/default e vínculos/índices ausentes.
-- Definições incompatíveis interrompem sem apagar ou adivinhar dados.
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='data_leitura' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Notificacao_data_leitura`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_evento' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Notificacao_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Notificacao' GROUP BY index_name,non_unique) z WHERE cols='id_notificacao' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Notificacao ADD PRIMARY KEY (id_notificacao)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Notificacao' GROUP BY index_name,non_unique) z WHERE cols='id_destinatario,lida,data_leitura' AND non_unique=1 AND prefixes=0), 'ALTER TABLE Notificacao ADD INDEX idx_notificacao_expirar (id_destinatario, lida, data_leitura)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_destinatario' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_destinatario' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Notificacao_id_destinatario`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_destinatario' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Notificacao ADD CONSTRAINT fk_notificacao_destinatario FOREIGN KEY (id_destinatario) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_remetente' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_remetente' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('SET NULL') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Notificacao_id_remetente`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_remetente' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('SET NULL') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Notificacao ADD CONSTRAINT fk_notificacao_remetente FOREIGN KEY (id_remetente) REFERENCES Usuario(id_user) ON DELETE SET NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_conversa' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_conversa' AND k.referenced_table_name='Conversa' AND k.referenced_column_name='id_conversa' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Notificacao_id_conversa`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_conversa' AND k.referenced_table_name='Conversa' AND k.referenced_column_name='id_conversa' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Notificacao ADD CONSTRAINT fk_notificacao_conversa FOREIGN KEY (id_conversa) REFERENCES Conversa(id_conversa) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_mensagem' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_mensagem' AND k.referenced_table_name='Mensagem' AND k.referenced_column_name='id_mensagem' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Notificacao_id_mensagem`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_mensagem' AND k.referenced_table_name='Mensagem' AND k.referenced_column_name='id_mensagem' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Notificacao ADD CONSTRAINT fk_notificacao_mensagem FOREIGN KEY (id_mensagem) REFERENCES Mensagem(id_mensagem) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='id_evento' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Notificacao_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Notificacao' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Notificacao ADD CONSTRAINT fk_notificacao_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='criado_em'), 'ALTER TABLE PushSubscription ADD COLUMN criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='atualizado_em'), 'ALTER TABLE PushSubscription ADD COLUMN atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='id_push' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='bigint unsigned' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=1 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_PushSubscription_id_push`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='id_user' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_PushSubscription_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='endpoint_hash' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='char(64)' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='ascii_bin' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_PushSubscription_endpoint_hash`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='dados_json' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='json' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_PushSubscription_dados_json`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='criado_em' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_PushSubscription_criado_em`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='atualizado_em' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=1 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_PushSubscription_atualizado_em`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='PushSubscription' GROUP BY index_name,non_unique) z WHERE cols='id_push' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE PushSubscription ADD PRIMARY KEY (id_push)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='PushSubscription' GROUP BY index_name,non_unique) z WHERE cols='endpoint_hash' AND non_unique=0 AND prefixes=0), 'ALTER TABLE PushSubscription ADD UNIQUE KEY uq_push_endpoint (endpoint_hash)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='PushSubscription' GROUP BY index_name,non_unique) z WHERE cols='id_user' AND non_unique=1 AND prefixes=0), 'ALTER TABLE PushSubscription ADD INDEX idx_push_usuario (id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='PushSubscription' AND column_name='id_user' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='PushSubscription' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_PushSubscription_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='PushSubscription' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE PushSubscription ADD CONSTRAINT fk_push_user FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
-- END GENERATED CONTRACT
