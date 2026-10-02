USE app_zubbo;

CREATE TABLE IF NOT EXISTS Solicitacao_Vaga_Evento (
    id_solicitacao INT PRIMARY KEY AUTO_INCREMENT,
    id_evento INT NOT NULL,
    id_user INT NOT NULL,
    time_num TINYINT NOT NULL,
    numero_vaga SMALLINT NOT NULL,
    status_solicitacao ENUM(
        'pendente',
        'aprovada',
        'recusada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendente',
    data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_resposta DATETIME NULL,

    UNIQUE KEY uq_solicitacao_evento_usuario (id_evento, id_user),
    INDEX idx_solicitacao_vaga (id_evento, time_num, numero_vaga),
    INDEX idx_solicitacao_status (id_evento, status_solicitacao),

    CONSTRAINT fk_solicitacao_vaga_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
        ON DELETE CASCADE,

    CONSTRAINT fk_solicitacao_vaga_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
);
