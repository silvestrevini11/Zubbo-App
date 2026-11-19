-- Executar uma única vez no banco da demonstração antes de ativar esta branch.
ALTER TABLE LocalEsp
    ADD COLUMN foto_local VARCHAR(255) NULL AFTER endereco_local;
