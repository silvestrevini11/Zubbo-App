-- Banco existente: backup e conferência do destino obrigatórios. Execute 001 a 005 em ordem.
-- Reexecutável, sem exclusão de dados. Execute sem opção de ignorar erros.

SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Usuario' AND column_name='sobre_mim'), 'ALTER TABLE Usuario ADD COLUMN sobre_mim TEXT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Administrador' AND column_name='id_user'), 'ALTER TABLE Administrador ADD COLUMN id_user INT NULL', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(EXISTS (SELECT id_user FROM Administrador WHERE id_user IS NOT NULL GROUP BY id_user HAVING COUNT(*)>1), 'SELECT * FROM `ERRO_administradores_duplicados_resolver_antes_de_migrar`', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM (SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='Administrador' GROUP BY index_name,non_unique) z WHERE cols='id_user' AND non_unique=0), 'ALTER TABLE Administrador ADD UNIQUE INDEX uq_admin_usuario (id_user)', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
SET @zubbo_sql = IF(NOT EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name='Administrador' AND column_name='id_user' AND referenced_table_name='Usuario'), 'ALTER TABLE Administrador ADD CONSTRAINT fk_admin_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE', 'SELECT 1');
PREPARE zubbo_stmt FROM @zubbo_sql;
EXECUTE zubbo_stmt;
DEALLOCATE PREPARE zubbo_stmt;
CREATE TABLE IF NOT EXISTS Recuperacao_Senha (
    id_recuperacao INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expiracao DATETIME NOT NULL,
    usado BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_recuperacao_usuario (id_user),
    INDEX idx_recuperacao_expiracao (expiracao),

    CONSTRAINT fk_recuperacao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS Bloqueio_Usuario (
    id_bloqueador INT NOT NULL,
    id_bloqueado INT NOT NULL,
    data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bloqueador, id_bloqueado),
    CONSTRAINT fk_bloqueio_de FOREIGN KEY (id_bloqueador) REFERENCES Usuario(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_bloqueio_para FOREIGN KEY (id_bloqueado) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
