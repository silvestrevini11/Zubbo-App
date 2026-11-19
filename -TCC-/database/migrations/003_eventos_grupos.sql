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

-- BEGIN GENERATED CONTRACT
-- Gerado por php database/tools/migration-contract.php --write.
-- Completa apenas colunas com NULL/default e vínculos/índices ausentes.
-- Definições incompatíveis interrompem sem apagar ou adivinhar dados.
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='nome_time_1' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='varchar(30)' AND is_nullable='NO' AND REPLACE(column_default,'()','')='Time 1' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Evento_nome_time_1`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='nome_time_2' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='varchar(30)' AND is_nullable='NO' AND REPLACE(column_default,'()','')='Time 2' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Evento_nome_time_2`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='descricao_times' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='varchar(180)' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Evento_descricao_times`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='duracao_minutos' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='smallint' AND is_nullable='NO' AND REPLACE(column_default,'()','')='60' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Evento_duracao_minutos`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Evento ADD PRIMARY KEY (id_evento)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='id_esporte' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Evento' AND k.column_name='id_esporte' AND k.referenced_table_name='Esporte' AND k.referenced_column_name='id_esporte' AND r.delete_rule IN ('RESTRICT', 'NO ACTION') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Evento_id_esporte`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Evento' AND k.column_name='id_esporte' AND k.referenced_table_name='Esporte' AND k.referenced_column_name='id_esporte' AND r.delete_rule IN ('RESTRICT', 'NO ACTION') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Evento ADD CONSTRAINT fk_evento_esporte FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='id_local' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Evento' AND k.column_name='id_local' AND k.referenced_table_name='LocalEsp' AND k.referenced_column_name='id_local' AND r.delete_rule IN ('RESTRICT', 'NO ACTION') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Evento_id_local`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Evento' AND k.column_name='id_local' AND k.referenced_table_name='LocalEsp' AND k.referenced_column_name='id_local' AND r.delete_rule IN ('RESTRICT', 'NO ACTION') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Evento ADD CONSTRAINT fk_evento_local FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='id_criador' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Evento' AND k.column_name='id_criador' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('SET NULL') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Evento_id_criador`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Evento' AND k.column_name='id_criador' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('SET NULL') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Evento ADD CONSTRAINT fk_evento_usuario FOREIGN KEY (id_criador) REFERENCES Usuario(id_user) ON DELETE SET NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='time_num'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD COLUMN time_num TINYINT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='numero_vaga'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD COLUMN numero_vaga SMALLINT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='status_solicitacao'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD COLUMN status_solicitacao ENUM( ''pendente'', ''aprovada'', ''recusada'', ''cancelada'' ) NOT NULL DEFAULT ''pendente''', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='data_solicitacao'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD COLUMN data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='data_resposta'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD COLUMN data_resposta DATETIME NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='id_solicitacao' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=1 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_id_solicitacao`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='id_evento' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='id_user' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='time_num' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='tinyint' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_time_num`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='numero_vaga' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='smallint' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_numero_vaga`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='status_solicitacao' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='enum(''pendente'',''aprovada'',''recusada'',''cancelada'')' AND is_nullable='NO' AND REPLACE(column_default,'()','')='pendente' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND collation_name='utf8mb4_unicode_ci' AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_status_solicitacao`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='data_solicitacao' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_data_solicitacao`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='data_resposta' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_data_resposta`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='vaga_confirmada' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='tinyint' AND is_nullable='YES' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND LOWER(extra) LIKE '%stored generated%' AND LOWER(REGEXP_REPLACE(REPLACE(REPLACE(generation_expression,'_utf8mb4',''),CHAR(92),''),'[[:space:]`()]+',''))='casewhenstatus_solicitacao=''aprovada''andtime_numisnotnullandnumero_vagaisnotnullthen1elsenullend'), 'SELECT * FROM `ERRO_def_Solicitacao_Vaga_Evento_vaga_confirmada`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_solicitacao' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Solicitacao_Vaga_Evento ADD PRIMARY KEY (id_solicitacao)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,id_user' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Solicitacao_Vaga_Evento ADD UNIQUE KEY uq_solicitacao_evento_usuario (id_evento, id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,time_num,numero_vaga,vaga_confirmada' AND non_unique=0 AND prefixes=0), 'ALTER TABLE Solicitacao_Vaga_Evento ADD UNIQUE KEY uq_vaga_confirmada (id_evento, time_num, numero_vaga, vaga_confirmada)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,time_num,numero_vaga' AND non_unique=1 AND prefixes=0), 'ALTER TABLE Solicitacao_Vaga_Evento ADD INDEX idx_solicitacao_vaga (id_evento, time_num, numero_vaga)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,status_solicitacao' AND non_unique=1 AND prefixes=0), 'ALTER TABLE Solicitacao_Vaga_Evento ADD INDEX idx_solicitacao_status (id_evento, status_solicitacao)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='id_evento' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Solicitacao_Vaga_Evento' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Solicitacao_Vaga_Evento_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Solicitacao_Vaga_Evento' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Solicitacao_Vaga_Evento ADD CONSTRAINT fk_solicitacao_vaga_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Solicitacao_Vaga_Evento' AND column_name='id_user' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Solicitacao_Vaga_Evento' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Solicitacao_Vaga_Evento_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Solicitacao_Vaga_Evento' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Solicitacao_Vaga_Evento ADD CONSTRAINT fk_solicitacao_vaga_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' AND column_name='data_entrada'), 'ALTER TABLE Fila_Espera_Evento ADD COLUMN data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' AND column_name='id_evento' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Fila_Espera_Evento_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' AND column_name='id_user' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Fila_Espera_Evento_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' AND column_name='data_entrada' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Fila_Espera_Evento_data_entrada`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,id_user' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Fila_Espera_Evento ADD PRIMARY KEY (id_evento,id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_evento,data_entrada' AND non_unique=1 AND prefixes=0), 'ALTER TABLE Fila_Espera_Evento ADD INDEX idx_fila_evento (id_evento,data_entrada)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' AND column_name='id_evento' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Fila_Espera_Evento' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Fila_Espera_Evento_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Fila_Espera_Evento' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Fila_Espera_Evento ADD CONSTRAINT fk_fila_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Fila_Espera_Evento' AND column_name='id_user' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Fila_Espera_Evento' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Fila_Espera_Evento_id_user`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Fila_Espera_Evento' AND k.column_name='id_user' AND k.referenced_table_name='Usuario' AND k.referenced_column_name='id_user' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Fila_Espera_Evento ADD CONSTRAINT fk_fila_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' AND column_name='criado_em'), 'ALTER TABLE Grupo_Evento ADD COLUMN criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' AND column_name='id_grupo' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Grupo_Evento_id_grupo`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' AND column_name='id_evento' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='int' AND is_nullable='NO' AND column_default IS NULL AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Grupo_Evento_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' AND column_name='criado_em' AND REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')='datetime' AND is_nullable='NO' AND LOWER(REPLACE(column_default,'()',''))='current_timestamp' AND (extra LIKE '%auto_increment%')=0 AND (LOWER(extra) LIKE '%on update current_timestamp%')=0 AND COALESCE(generation_expression,'')=''), 'SELECT * FROM `ERRO_def_Grupo_Evento_criado_em`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' GROUP BY index_name,non_unique) z WHERE cols='id_grupo,id_evento' AND non_unique=0 AND prefixes=0 AND index_name='PRIMARY'), 'ALTER TABLE Grupo_Evento ADD PRIMARY KEY (id_grupo,id_evento)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' AND column_name='id_grupo' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Evento' AND k.column_name='id_grupo' AND k.referenced_table_name='Grupo' AND k.referenced_column_name='id_grupo' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Grupo_Evento_id_grupo`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Evento' AND k.column_name='id_grupo' AND k.referenced_table_name='Grupo' AND k.referenced_column_name='id_grupo' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Grupo_Evento ADD FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Grupo_Evento' AND column_name='id_evento' AND referenced_table_name IS NOT NULL) AND NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Evento' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'SELECT * FROM `ERRO_fk_Grupo_Evento_id_evento`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name='Grupo_Evento' AND k.column_name='id_evento' AND k.referenced_table_name='Evento' AND k.referenced_column_name='id_evento' AND r.delete_rule IN ('CASCADE') AND r.update_rule IN ('RESTRICT','NO ACTION')), 'ALTER TABLE Grupo_Evento ADD FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
-- END GENERATED CONTRACT
