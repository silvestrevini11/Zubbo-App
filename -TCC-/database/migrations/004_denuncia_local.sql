-- Execute no MySQL da DEMO antes de enviar denúncias de locais.
-- Não execute na production congelada.
-- Para um banco já existente que segue criacaotables.sql.
ALTER TABLE Denuncia
    ADD COLUMN id_local INT NULL AFTER id_evento,
    ADD CONSTRAINT fk_denuncia_local
        FOREIGN KEY (id_local)
        REFERENCES LocalEsp(id_local)
        ON DELETE SET NULL;
