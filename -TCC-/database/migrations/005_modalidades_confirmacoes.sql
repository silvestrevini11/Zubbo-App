-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='imagem_esporte'), 'ALTER TABLE Esporte ADD COLUMN imagem_esporte VARCHAR(255) NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='regras_esporte'), 'ALTER TABLE Esporte ADD COLUMN regras_esporte TEXT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='jogadores_por_time'), 'ALTER TABLE Esporte ADD COLUMN jogadores_por_time SMALLINT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='quantidade_times'), 'ALTER TABLE Esporte ADD COLUMN quantidade_times TINYINT NOT NULL DEFAULT 2', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='exige_confirmacao_local'), 'ALTER TABLE Esporte ADD COLUMN exige_confirmacao_local BOOLEAN NOT NULL DEFAULT FALSE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Esporte' AND column_name='exige_confirmacao_local' AND column_default <> '0'), 'ALTER TABLE Esporte ALTER COLUMN exige_confirmacao_local SET DEFAULT FALSE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS Local_Esporte_Permitido (
    id_local INT NOT NULL,
 id_esporte INT NOT NULL,
 fonte VARCHAR(500) NOT NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id_local,id_esporte),
 FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE,
 FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
