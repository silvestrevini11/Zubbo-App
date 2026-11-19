-- Configuração independente dos nomes dos dois times em cada evento.
ALTER TABLE Evento
    ADD COLUMN nome_time_1 VARCHAR(30) NOT NULL DEFAULT 'Time 1',
    ADD COLUMN nome_time_2 VARCHAR(30) NOT NULL DEFAULT 'Time 2',
    ADD COLUMN descricao_times VARCHAR(180) NULL;
