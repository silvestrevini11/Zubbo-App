-- Confirmações manuais de modalidades por local registradas pelo ZubboADM.
-- Não representam integração automática com a prefeitura nem uma reserva de quadra.
-- Modalidades básicas anteriores mantêm compatibilidade enquanto são verificadas.
ALTER TABLE Esporte MODIFY COLUMN exige_confirmacao_local BOOLEAN NOT NULL DEFAULT FALSE;
UPDATE Esporte SET exige_confirmacao_local = FALSE
 WHERE nome_esporte IN ('Futebol','Futsal','Basquete','Vôlei','Handebol','Corrida');

CREATE TABLE IF NOT EXISTS Local_Esporte_Confirmacao (
    id_local INT NOT NULL,
    id_esporte INT NOT NULL,
    id_adm INT NOT NULL,
    referencia_contato VARCHAR(150) NOT NULL,
    confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_local,id_esporte),
    CONSTRAINT fk_lec_local FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE,
    CONSTRAINT fk_lec_esporte FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE,
    CONSTRAINT fk_lec_adm FOREIGN KEY (id_adm) REFERENCES Administrador(id_adm)
);
