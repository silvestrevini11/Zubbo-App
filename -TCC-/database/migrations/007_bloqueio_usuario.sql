-- Bloqueio de contatos privados. Executar antes de ativar a funcionalidade.
CREATE TABLE IF NOT EXISTS Bloqueio_Usuario (
    id_bloqueador INT NOT NULL,
    id_bloqueado INT NOT NULL,
    data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bloqueador, id_bloqueado),
    CONSTRAINT fk_bloqueio_de FOREIGN KEY (id_bloqueador) REFERENCES Usuario(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_bloqueio_para FOREIGN KEY (id_bloqueado) REFERENCES Usuario(id_user) ON DELETE CASCADE
);
