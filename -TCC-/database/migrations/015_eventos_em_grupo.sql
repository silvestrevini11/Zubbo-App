-- Eventos originados em grupos. Participantes recebem convite, sem inscrição forçada.
CREATE TABLE IF NOT EXISTS Grupo_Evento (
    id_grupo INT NOT NULL,
    id_evento INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_grupo,id_evento),
    FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE
);
ALTER TABLE Notificacao
    ADD COLUMN id_evento INT NULL,
    ADD CONSTRAINT fk_notificacao_evento FOREIGN KEY (id_evento)
      REFERENCES Evento(id_evento) ON DELETE CASCADE;
