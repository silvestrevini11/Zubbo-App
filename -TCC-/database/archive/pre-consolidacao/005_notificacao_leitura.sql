-- Executar uma única vez antes de publicar os ajustes de notificações.
ALTER TABLE Notificacao
    ADD COLUMN data_leitura DATETIME NULL AFTER lida,
    ADD INDEX idx_notificacao_expirar (id_destinatario, lida, data_leitura);
UPDATE Notificacao SET data_leitura = NOW() WHERE lida = TRUE AND data_leitura IS NULL;
