-- Instalação nova: estrutura final. Selecione explicitamente o banco vazio.
-- Dados iniciais: insertstables.sql. Bancos existentes: ../migrations/001 a 005.

CREATE TABLE Usuario (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    nome_user VARCHAR(50) NOT NULL,
    email_user VARCHAR(70) NOT NULL UNIQUE,
    tel_user VARCHAR(20) NOT NULL,
    senha_user VARCHAR(255) NOT NULL,
    date_user DATE NOT NULL,
    foto_user VARCHAR(255) NULL,
    email_verificado BOOLEAN NOT NULL DEFAULT FALSE,
    status_user ENUM('ativo', 'suspenso', 'banido') NOT NULL DEFAULT 'ativo',
    sobre_mim TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Esporte (
    id_esporte INT PRIMARY KEY AUTO_INCREMENT,
    nome_esporte VARCHAR(50) NOT NULL UNIQUE,
    imagem_esporte VARCHAR(255) NULL,
    regras_esporte TEXT NULL,
    jogadores_por_time SMALLINT NULL,
    quantidade_times TINYINT NOT NULL DEFAULT 2,
    exige_confirmacao_local BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LocalEsp (
    id_local INT PRIMARY KEY AUTO_INCREMENT,
    nome_local VARCHAR(60) NOT NULL,
    endereco_local VARCHAR(120) NOT NULL,
    tipo_local ENUM(
        'quadra',
        'poliesportivo',
        'clube',
        'ginásio', 'campo', 'parque'
    ) NOT NULL DEFAULT 'poliesportivo',
    status_local ENUM(
        'pendente',
        'aprovado',
        'rejeitado'
    ) NOT NULL DEFAULT 'aprovado',
    id_criador INT NULL,

    UNIQUE KEY uq_local_nome_endereco (nome_local, endereco_local),

    CONSTRAINT fk_local_criador
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL,
    foto_local VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Usuario_Esporte (
    id_user INT NOT NULL,
    id_esporte INT NOT NULL,

    PRIMARY KEY (id_user, id_esporte),

    CONSTRAINT fk_usuarioesporte_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_usuarioesporte_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Verificacao_Email (
    id_verificacao INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    expiracao DATETIME NOT NULL,

    CONSTRAINT fk_verificacao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Recuperacao_Senha (
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

CREATE TABLE Solicitacao_Amizade (
    id_solicitacao INT AUTO_INCREMENT PRIMARY KEY,
    id_remetente INT NOT NULL,
    id_destinatario INT NOT NULL,
    status ENUM('pendente', 'aceita', 'recusada') NOT NULL DEFAULT 'pendente',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_solicitacao_remetente
        FOREIGN KEY (id_remetente)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_solicitacao_destinatario
        FOREIGN KEY (id_destinatario)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    UNIQUE (id_remetente, id_destinatario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Amizade (
    id_amizade INT AUTO_INCREMENT PRIMARY KEY,
    id_user_1 INT NOT NULL,
    id_user_2 INT NOT NULL,
    data_aceita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_amizade_user1
        FOREIGN KEY (id_user_1)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_amizade_user2
        FOREIGN KEY (id_user_2)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    UNIQUE (id_user_1, id_user_2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Conversa (
    id_conversa INT AUTO_INCREMENT PRIMARY KEY,
    tipo_conversa ENUM('privado', 'grupo', 'comunidade') NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Participantes_Conversa (
    id_user INT NOT NULL,
    id_conversa INT NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_user, id_conversa),

    CONSTRAINT fk_participante_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_participante_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Grupo (
    id_grupo INT PRIMARY KEY AUTO_INCREMENT,
    id_conversa INT NOT NULL UNIQUE,
    id_criador INT NULL,
    nome_grupo VARCHAR(80) NOT NULL,
    descricao_grupo VARCHAR(255) NULL,
    foto_grupo VARCHAR(255) NULL,

    CONSTRAINT fk_grupo_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
        ON DELETE CASCADE,

    CONSTRAINT fk_grupo_criador
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Grupo_Moderador (
    id_grupo INT NOT NULL,
    id_user INT NOT NULL,
    PRIMARY KEY (id_grupo, id_user),
    CONSTRAINT fk_grupo_moderador_grupo
        FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE,
    CONSTRAINT fk_grupo_moderador_usuario
        FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Comunidade (
    id_comunidade INT PRIMARY KEY AUTO_INCREMENT,
    id_conversa INT NOT NULL UNIQUE,
    id_esporte INT NOT NULL,
    nome_comunidade VARCHAR(80) NOT NULL,
    cidade_comunidade VARCHAR(60) NOT NULL,
    bairro_comunidade VARCHAR(60) NULL,

    CONSTRAINT fk_comunidade_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
        ON DELETE CASCADE,

    CONSTRAINT fk_comunidade_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Mensagem (
    id_mensagem INT AUTO_INCREMENT PRIMARY KEY,
    id_conversa INT NOT NULL,
    id_remetente INT NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_mensagem_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
        ON DELETE CASCADE,

    CONSTRAINT fk_mensagem_remetente
        FOREIGN KEY (id_remetente)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Equipe (
    id_equipe INT PRIMARY KEY AUTO_INCREMENT,
    nome_equipe VARCHAR(30) NOT NULL,
    id_esporte INT NOT NULL,
    id_criador INT NULL,

    CONSTRAINT fk_equipe_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte),

    CONSTRAINT fk_equipe_criador
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ParticipantesEquipe (
    id_partEquipe INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    id_equipe INT NOT NULL,

    CONSTRAINT fk_participanteequipe_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_participanteequipe_equipe
        FOREIGN KEY (id_equipe)
        REFERENCES Equipe(id_equipe)
        ON DELETE CASCADE,

    UNIQUE (id_user, id_equipe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Evento (
    id_evento INT PRIMARY KEY AUTO_INCREMENT,
    nome_evento VARCHAR(100) NOT NULL,
    data_evento DATE NOT NULL,
    horario_evento TIME NOT NULL,
    id_esporte INT NOT NULL,
    id_local INT NOT NULL,
    id_criador INT NULL,
    status_evento ENUM('ativo', 'cancelado', 'removido') NOT NULL DEFAULT 'ativo',

    CONSTRAINT fk_evento_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte),

    CONSTRAINT fk_evento_local
        FOREIGN KEY (id_local)
        REFERENCES LocalEsp(id_local),

    CONSTRAINT fk_evento_usuario
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL,
    nome_time_1 VARCHAR(30) NOT NULL DEFAULT 'Time 1',
    nome_time_2 VARCHAR(30) NOT NULL DEFAULT 'Time 2',
    descricao_times VARCHAR(180) NULL,
    duracao_minutos SMALLINT NOT NULL DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE EquipesEvento (
    id_evento INT NOT NULL,
    id_equipe INT NOT NULL,

    PRIMARY KEY (id_evento, id_equipe),

    CONSTRAINT fk_equipesevento_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
        ON DELETE CASCADE,

    CONSTRAINT fk_equipesevento_equipe
        FOREIGN KEY (id_equipe)
        REFERENCES Equipe(id_equipe)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Lista_Evento (
    id_user INT NOT NULL,
    id_evento INT NOT NULL,

    PRIMARY KEY (id_user, id_evento),

    CONSTRAINT fk_listaevento_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_listaevento_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Solicitacao_Vaga_Evento (
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

CREATE TABLE Bloqueio_Usuario (
    id_bloqueador INT NOT NULL,
    id_bloqueado INT NOT NULL,
    data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bloqueador, id_bloqueado),
    CONSTRAINT fk_bloqueio_de FOREIGN KEY (id_bloqueador) REFERENCES Usuario(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_bloqueio_para FOREIGN KEY (id_bloqueado) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Fila_Espera_Evento (
    id_evento INT NOT NULL,
    id_user INT NOT NULL,
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evento,id_user),
    INDEX idx_fila_evento (id_evento,data_entrada),
    CONSTRAINT fk_fila_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE,
    CONSTRAINT fk_fila_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE PushSubscription (
    id_push BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    endpoint_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    dados_json JSON NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_endpoint (endpoint_hash),
    INDEX idx_push_usuario (id_user),
    CONSTRAINT fk_push_user FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Local_Esporte_Permitido (
    id_local INT NOT NULL,
 id_esporte INT NOT NULL,
 fonte VARCHAR(500) NOT NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id_local,id_esporte),
 FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE,
 FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Grupo_Evento (
    id_grupo INT NOT NULL,
    id_evento INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_grupo,id_evento),
    FOREIGN KEY (id_grupo) REFERENCES Grupo(id_grupo) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Administrador (
    id_adm INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NULL,
    nome_adm VARCHAR(50) NOT NULL,
    email_adm VARCHAR(70) NOT NULL UNIQUE,
    senha_adm VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_usuario (id_user),
    CONSTRAINT fk_admin_usuario FOREIGN KEY (id_user) REFERENCES Usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Notificacao (
    id_notificacao INT AUTO_INCREMENT PRIMARY KEY,
    id_destinatario INT NOT NULL,
    id_remetente INT NULL,
    id_conversa INT NULL,
    id_mensagem INT NULL,
    tipo VARCHAR(30) NOT NULL DEFAULT 'mensagem',
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacao_destinatario
        FOREIGN KEY (id_destinatario)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_notificacao_remetente
        FOREIGN KEY (id_remetente)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL,

    CONSTRAINT fk_notificacao_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
        ON DELETE CASCADE,

    CONSTRAINT fk_notificacao_mensagem
        FOREIGN KEY (id_mensagem)
        REFERENCES Mensagem(id_mensagem)
        ON DELETE CASCADE,
    data_leitura DATETIME NULL,
    INDEX idx_notificacao_expirar (id_destinatario, lida, data_leitura),
    id_evento INT NULL,
    CONSTRAINT fk_notificacao_evento FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Sugestao_Esporte (
    id_sugestao INT PRIMARY KEY AUTO_INCREMENT,
    nome_esporte VARCHAR(50) NOT NULL,
    id_user INT NOT NULL,
    id_adm INT NULL,
    status_sugestao ENUM(
        'pendente',
        'aprovada',
        'rejeitada'
    ) NOT NULL DEFAULT 'pendente',
    data_sugestao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_analise DATETIME NULL,

    CONSTRAINT fk_sugestao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_sugestao_adm
        FOREIGN KEY (id_adm)
        REFERENCES Administrador(id_adm)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Voto_Sugestao (
    id_sugestao INT NOT NULL,
    id_user INT NOT NULL,

    PRIMARY KEY (id_sugestao, id_user),

    CONSTRAINT fk_voto_sugestao
        FOREIGN KEY (id_sugestao)
        REFERENCES Sugestao_Esporte(id_sugestao)
        ON DELETE CASCADE,

    CONSTRAINT fk_voto_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Denuncia (
    id_denuncia INT PRIMARY KEY AUTO_INCREMENT,
    id_denunciante INT NOT NULL,
    id_denunciado INT NULL,
    id_mensagem INT NULL,
    id_conversa INT NULL,
    id_evento INT NULL,
    id_local INT NULL,
    id_adm INT NULL,
    motivo VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    status_denuncia ENUM('pendente', 'em_analise', 'resolvida', 'rejeitada')
        NOT NULL DEFAULT 'pendente',
    data_denuncia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_analise DATETIME NULL,

    CONSTRAINT fk_denuncia_denunciante
        FOREIGN KEY (id_denunciante)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_denuncia_denunciado
        FOREIGN KEY (id_denunciado)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL,

    CONSTRAINT fk_denuncia_mensagem
        FOREIGN KEY (id_mensagem)
        REFERENCES Mensagem(id_mensagem)
        ON DELETE SET NULL,

    CONSTRAINT fk_denuncia_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
        ON DELETE SET NULL,

    CONSTRAINT fk_denuncia_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
        ON DELETE SET NULL,

    CONSTRAINT fk_denuncia_local
        FOREIGN KEY (id_local)
        REFERENCES LocalEsp(id_local)
        ON DELETE SET NULL,

    CONSTRAINT fk_denuncia_adm
        FOREIGN KEY (id_adm)
        REFERENCES Administrador(id_adm)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Acao_Administrativa (
    id_acao INT PRIMARY KEY AUTO_INCREMENT,
    id_adm INT NOT NULL,
    id_user INT NULL,
    id_denuncia INT NULL,
    id_evento INT NULL,
    id_local INT NULL,
    id_grupo INT NULL,
    id_comunidade INT NULL,
    tipo_acao VARCHAR(50) NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    data_acao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_acao_adm
        FOREIGN KEY (id_adm)
        REFERENCES Administrador(id_adm),

    CONSTRAINT fk_acao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL,

    CONSTRAINT fk_acao_denuncia
        FOREIGN KEY (id_denuncia)
        REFERENCES Denuncia(id_denuncia)
        ON DELETE SET NULL,

    CONSTRAINT fk_acao_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
        ON DELETE SET NULL,

    CONSTRAINT fk_acao_local
        FOREIGN KEY (id_local)
        REFERENCES LocalEsp(id_local)
        ON DELETE SET NULL,

    CONSTRAINT fk_acao_grupo
        FOREIGN KEY (id_grupo)
        REFERENCES Grupo(id_grupo)
        ON DELETE SET NULL,

    CONSTRAINT fk_acao_comunidade
        FOREIGN KEY (id_comunidade)
        REFERENCES Comunidade(id_comunidade)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Local_Esporte_Confirmacao (
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
