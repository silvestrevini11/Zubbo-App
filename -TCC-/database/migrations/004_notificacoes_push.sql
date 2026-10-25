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
