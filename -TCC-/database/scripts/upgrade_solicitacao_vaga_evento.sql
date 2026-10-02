USE app_zubbo;

-- Execute este arquivo uma única vez se a tabela Solicitacao_Vaga_Evento
-- já tiver sido criada pela versão anterior desta branch.

UPDATE Solicitacao_Vaga_Evento s
INNER JOIN Lista_Evento le
    ON le.id_evento = s.id_evento
   AND le.id_user = s.id_user
SET s.status_solicitacao = 'aprovada',
    s.data_resposta = COALESCE(s.data_resposta, CURRENT_TIMESTAMP)
WHERE s.status_solicitacao = 'pendente';

ALTER TABLE Solicitacao_Vaga_Evento
    MODIFY time_num TINYINT NULL,
    MODIFY numero_vaga SMALLINT NULL;

ALTER TABLE Solicitacao_Vaga_Evento
    ADD COLUMN vaga_confirmada TINYINT
        GENERATED ALWAYS AS (
            CASE
                WHEN status_solicitacao = 'aprovada'
                     AND time_num IS NOT NULL
                     AND numero_vaga IS NOT NULL
                THEN 1
                ELSE NULL
            END
        ) STORED,
    ADD UNIQUE KEY uq_vaga_confirmada (
        id_evento,
        time_num,
        numero_vaga,
        vaga_confirmada
    );