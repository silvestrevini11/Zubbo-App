-- Supported baseline predating admin links, location uniqueness and confirmed slots.
-- Applied ONLY to the archived CREATE script inside an authorized, empty test database.
ALTER TABLE Administrador DROP FOREIGN KEY fk_admin_usuario, DROP INDEX id_user, DROP COLUMN id_user;
ALTER TABLE Usuario DROP COLUMN sobre_mim;
ALTER TABLE LocalEsp DROP INDEX uq_local_nome_endereco;
ALTER TABLE Denuncia DROP FOREIGN KEY fk_denuncia_local, DROP COLUMN id_local;
ALTER TABLE Solicitacao_Vaga_Evento DROP INDEX uq_vaga_confirmada, DROP COLUMN vaga_confirmada,
    MODIFY time_num TINYINT NOT NULL, MODIFY numero_vaga SMALLINT NOT NULL;
