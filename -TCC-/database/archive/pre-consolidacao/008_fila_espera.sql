-- Fila para eventos de equipes completos. Aplicar uma vez no banco da demo.
CREATE TABLE IF NOT EXISTS Fila_Espera_Evento (
    id_evento INT NOT NULL,
    id_user INT NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evento,id_user),
    INDEX idx_fila_evento (id_evento,data_entrada),
    CONSTRAINT fk_fila_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE,
    CONSTRAINT fk_fila_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE
);
