-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='nome_time_1'), 'ALTER TABLE Evento ADD COLUMN nome_time_1 VARCHAR(30) NOT NULL DEFAULT ''Time 1''', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='nome_time_2'), 'ALTER TABLE Evento ADD COLUMN nome_time_2 VARCHAR(30) NOT NULL DEFAULT ''Time 2''', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='descricao_times'), 'ALTER TABLE Evento ADD COLUMN descricao_times VARCHAR(180) NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='duracao_minutos'), 'ALTER TABLE Evento ADD COLUMN duracao_minutos SMALLINT NOT NULL DEFAULT 60', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
CREATE TABLE IF NOT EXISTS Solicitacao_Vaga_Evento (
    id_solicitacao INT PRIMARY KEY AUTO_INCREMENT,
    id_evento INT NOT NULL,
    id_user INT NOT NULL,
    time_num TINYINT NULL,
    numero_vaga SMALLINT NULL,
    status_solicitacao ENUM(
        'pendente',
        'aprovada',
        'recusada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendente',
    data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_resposta DATETIME NULL,

    vaga_confirmada TINYINT
        GENERATED ALWAYS AS (
            CASE
                WHEN status_solicitacao = 'aprovada'
                     AND time_num IS NOT NULL
                     AND numero_vaga IS NOT NULL
                THEN 1
                ELSE NULL
            END
        ) STORED,

    UNIQUE KEY uq_solicitacao_evento_usuario (id_evento, id_user),
    UNIQUE KEY uq_vaga_confirmada (id_evento, time_num, numero_vaga, vaga_confirmada),
    INDEX idx_solicitacao_vaga (id_evento, time_num, numero_vaga),
    INDEX idx_solicitacao_status (id_evento, status_solicitacao),

    CONSTRAINT fk_solicitacao_vaga_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
        ON DELETE CASCADE,

    CONSTRAINT fk_solicitacao_vaga_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @zubbo_sql = IF(EXISTS (SELECT s.id_evento,s.time_num,s.numero_vaga FROM Solicitacao_Vaga_Evento s LEFT JOIN Lista_Evento l ON l.id_evento=s.id_evento AND l.id_user=s.id_user WHERE (s.status_solicitacao='aprovada' OR (s.status_solicitacao='pendente' AND l.id_user IS NOT NULL)) AND s.time_num IS NOT NULL AND s.numero_vaga IS NOT NULL GROUP BY s.id_evento,s.time_num,s.numero_vaga HAVING COUNT(*)>1), 'SELECT * FROM `ERRO_vagas_confirmadas_duplicadas_resolver_antes_de_migrar`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='vaga_confirmada'), 'UPDATE Solicitacao_Vaga_Evento s INNER JOIN Lista_Evento l ON l.id_evento=s.id_evento AND l.id_user=s.id_user SET s.status_solicitacao=''aprovada'',s.data_resposta=COALESCE(s.data_resposta,CURRENT_TIMESTAMP) WHERE s.status_solicitacao=''pendente''', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name IN ('time_num','numero_vaga') AND is_nullable='NO'), 'ALTER TABLE Solicitacao_Vaga_Evento MODIFY time_num TINYINT NULL, MODIFY numero_vaga SMALLINT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='vaga_confirmada'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD COLUMN vaga_confirmada TINYINT GENERATED ALWAYS AS (CASE WHEN status_solicitacao = ''aprovada'' AND time_num IS NOT NULL AND numero_vaga IS NOT NULL THEN 1 ELSE NULL END) STORED', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,time_num,numero_vaga,vaga_confirmada' AND non_unique=0), 'ALTER TABLE Solicitacao_Vaga_Evento ADD UNIQUE INDEX uq_vaga_confirmada (id_evento, time_num, numero_vaga, vaga_confirmada)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
CREATE TABLE IF NOT EXISTS Fila_Espera_Evento (
    id_evento INT NOT NULL,
    id_user INT NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evento,id_user),
    INDEX idx_fila_evento (id_evento,data_entrada),
    CONSTRAINT fk_fila_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE,
    CONSTRAINT fk_fila_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS Grupo_Evento (
    id_grupo INT NOT NULL,
    id_evento INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_grupo,id_evento),
    FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
