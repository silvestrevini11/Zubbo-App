USE app_zubbo;

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
);
