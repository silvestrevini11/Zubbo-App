-- Metadados para novas modalidades aprovadas pelo administrador.
ALTER TABLE Esporte
    ADD COLUMN imagem_esporte VARCHAR(255) NULL,
    ADD COLUMN regras_esporte TEXT NULL,
    ADD COLUMN jogadores_por_time SMALLINT NULL,
    ADD COLUMN quantidade_times TINYINT NOT NULL DEFAULT 2,
    ADD COLUMN exige_confirmacao_local BOOLEAN NOT NULL DEFAULT TRUE;
