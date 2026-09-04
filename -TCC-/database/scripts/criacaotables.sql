CREATE DATABASE app_zubbo;
USE app_zubbo;

-- =====================================================
-- 1. TABELAS PRINCIPAIS / INDEPENDENTES
-- =====================================================

CREATE TABLE Usuario (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    nome_user VARCHAR(50) NOT NULL,
    email_user VARCHAR(70) NOT NULL UNIQUE,
    tel_user VARCHAR(20) NOT NULL,
    senha_user VARCHAR(255) NOT NULL,
    date_user DATE NOT NULL
);


CREATE TABLE Esporte (
    id_esporte INT PRIMARY KEY AUTO_INCREMENT,
    nome_esporte VARCHAR(50) NOT NULL
);


CREATE TABLE LocalEsp (
    id_local INT PRIMARY KEY AUTO_INCREMENT,
    nome_local VARCHAR(60) NOT NULL,
    endereco_local VARCHAR(120) NOT NULL,
    tipo_local ENUM(
        'quadra',
        'poliesportivo',
        'clube',
        'ginásio'
    ) NOT NULL DEFAULT 'poliesportivo'
);


-- =====================================================
-- 2. ESPORTES DOS USUÁRIOS
-- =====================================================

CREATE TABLE Usuario_Esporte (
    id_user INT NOT NULL,
    id_esporte INT NOT NULL,

    PRIMARY KEY (id_user, id_esporte),

    CONSTRAINT fk_usuarioesporte_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user),

    CONSTRAINT fk_usuarioesporte_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte)
);


-- =====================================================
-- 3. CONVERSAS
-- =====================================================

CREATE TABLE Conversa (
    id_conversa INT PRIMARY KEY AUTO_INCREMENT,

    tipo_conversa ENUM('privada', 'grupo')
        NOT NULL DEFAULT 'privada',

    nome_conversa VARCHAR(60),

    data_criacao DATETIME
        DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE Participantes_Conversa (
    id_user INT NOT NULL,
    id_conversa INT NOT NULL,

    PRIMARY KEY (id_user, id_conversa),

    CONSTRAINT fk_participante_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user),

    CONSTRAINT fk_participante_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa)
);


CREATE TABLE Mensagem (
    id_mensagem INT PRIMARY KEY AUTO_INCREMENT,
    id_conversa INT NOT NULL,
    id_remetente INT NOT NULL,

    mensagem TEXT NOT NULL,

    data_envio DATETIME
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_mensagem_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa),

    CONSTRAINT fk_mensagem_remetente
        FOREIGN KEY (id_remetente)
        REFERENCES Usuario(id_user)
);


CREATE TABLE Notificacao (
    id_notificacao INT PRIMARY KEY AUTO_INCREMENT,

    id_destinatario INT NOT NULL,
    id_remetente INT NOT NULL,

    id_conversa INT NOT NULL,
    id_mensagem INT NOT NULL,

    tipo VARCHAR(30)
        NOT NULL DEFAULT 'mensagem',

    lida BOOLEAN
        NOT NULL DEFAULT FALSE,

    data_criacao DATETIME
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacao_destinatario
        FOREIGN KEY (id_destinatario)
        REFERENCES Usuario(id_user),

    CONSTRAINT fk_notificacao_remetente
        FOREIGN KEY (id_remetente)
        REFERENCES Usuario(id_user),

    CONSTRAINT fk_notificacao_conversa
        FOREIGN KEY (id_conversa)
        REFERENCES Conversa(id_conversa),

    CONSTRAINT fk_notificacao_mensagem
        FOREIGN KEY (id_mensagem)
        REFERENCES Mensagem(id_mensagem)
);


-- =====================================================
-- 4. EQUIPES
-- =====================================================

CREATE TABLE Equipe (
    id_equipe INT PRIMARY KEY AUTO_INCREMENT,
    nome_equipe VARCHAR(30) NOT NULL,

    id_esporte INT NOT NULL,
    id_criador INT NOT NULL,

    CONSTRAINT fk_equipe_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte),

    CONSTRAINT fk_equipe_criador
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
);


CREATE TABLE ParticipantesEquipe (
    id_partEquipe INT PRIMARY KEY AUTO_INCREMENT,

    id_user INT NOT NULL,
    id_equipe INT NOT NULL,

    CONSTRAINT fk_participanteequipe_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user),

    CONSTRAINT fk_participanteequipe_equipe
        FOREIGN KEY (id_equipe)
        REFERENCES Equipe(id_equipe),

    UNIQUE (id_user, id_equipe)
);


-- =====================================================
-- 5. EVENTOS
-- =====================================================

CREATE TABLE Evento (
    id_evento INT PRIMARY KEY AUTO_INCREMENT,

    nome_evento VARCHAR(100) NOT NULL,

    data_evento DATE NOT NULL,
    horario_evento TIME NOT NULL,

    id_esporte INT NOT NULL,
    id_local INT NOT NULL,
    id_criador INT NOT NULL,

    CONSTRAINT fk_evento_esporte
        FOREIGN KEY (id_esporte)
        REFERENCES Esporte(id_esporte),

    CONSTRAINT fk_evento_local
        FOREIGN KEY (id_local)
        REFERENCES LocalEsp(id_local),

    CONSTRAINT fk_evento_usuario
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
);


CREATE TABLE EquipesEvento (
    id_evento INT NOT NULL,
    id_equipe INT NOT NULL,

    PRIMARY KEY (id_evento, id_equipe),

    CONSTRAINT fk_equipesevento_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento),

    CONSTRAINT fk_equipesevento_equipe
        FOREIGN KEY (id_equipe)
        REFERENCES Equipe(id_equipe)
);


CREATE TABLE Lista_Evento (
    id_user INT NOT NULL,
    id_evento INT NOT NULL,

    PRIMARY KEY (id_user, id_evento),

    CONSTRAINT fk_listaevento_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user),

    CONSTRAINT fk_listaevento_evento
        FOREIGN KEY (id_evento)
        REFERENCES Evento(id_evento)
);


-- =====================================================
-- 6. Sugestaos DE ESPORTE
-- =====================================================

CREATE TABLE Sugestao_Esporte (
    id_sugestao INT PRIMARY KEY AUTO_INCREMENT,

    nome_esporte VARCHAR(50) NOT NULL,

    id_user INT NOT NULL,

    status_sugestao ENUM(
        'pendente',
        'aprovada',
        'rejeitada'
    ) NOT NULL DEFAULT 'pendente',

    CONSTRAINT fk_sugestao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
);


CREATE TABLE Voto_Sugestao (
    id_sugestao INT NOT NULL,
    id_user INT NOT NULL,

    PRIMARY KEY (id_sugestao, id_user),

    CONSTRAINT fk_voto_sugestao
        FOREIGN KEY (id_sugestao)
        REFERENCES Sugestao_Esporte(id_sugestao),

    CONSTRAINT fk_voto_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
);