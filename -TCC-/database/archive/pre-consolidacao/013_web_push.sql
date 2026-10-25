-- Requer MySQL 8/MariaDB com suporte a JSON. Execute uma vez na base de TESTE.
CREATE TABLE PushSubscription (
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
);
