-- Duracao dos eventos Zubbo para evitar sobreposicao no mesmo local.
-- Aplicar uma vez, depois das migrations 005-010.
ALTER TABLE Evento
    ADD COLUMN duracao_minutos SMALLINT NOT NULL DEFAULT 60 AFTER horario_evento;
