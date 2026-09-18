CREATE DATABASE IF NOT EXISTS app_zubbo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE app_zubbo;

-- =========================================================
-- 1. TABELAS PRINCIPAIS / CADASTROS
-- =========================================================

CREATE TABLE Administrador (
    id_adm INT PRIMARY KEY AUTO_INCREMENT,
    nome_adm VARCHAR(50) NOT NULL,
    email_adm VARCHAR(70) NOT NULL UNIQUE,
    senha_adm VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Usuario (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    nome_user VARCHAR(50) NOT NULL,
    email_user VARCHAR(70) NOT NULL UNIQUE,
    tel_user VARCHAR(20) NOT NULL,
    senha_user VARCHAR(255) NOT NULL,
    date_user DATE NOT NULL,
    foto_user VARCHAR(255) NULL,
    email_verificado BOOLEAN NOT NULL DEFAULT FALSE,
    status_user ENUM('ativo', 'suspenso', 'banido') NOT NULL DEFAULT 'ativo'
);

CREATE TABLE Esporte (
    id_esporte INT PRIMARY KEY AUTO_INCREMENT,
    nome_esporte VARCHAR(50) NOT NULL UNIQUE
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
    ) NOT NULL DEFAULT 'poliesportivo',
    status_local ENUM(
        'pendente',
        'aprovado',
        'rejeitado'
    ) NOT NULL DEFAULT 'aprovado',
    id_criador INT NULL,

    CONSTRAINT fk_local_criador
        FOREIGN KEY (id_criador)
        REFERENCES Usuario(id_user)
        ON DELETE SET NULL
);


-- =========================================================
-- 2. PERFIL E RELACIONAMENTOS DO USUÁRIO
-- =========================================================

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
);

CREATE TABLE Verificacao_Email (
    id_verificacao INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    expiracao DATETIME NOT NULL,

    CONSTRAINT fk_verificacao_usuario
        FOREIGN KEY (id_user)
        REFERENCES Usuario(id_user)
        ON DELETE CASCADE
);


-- =========================================================
-- 3. AMIZADES
-- =========================================================

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
);

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
);


-- =========================================================
-- 4. CONVERSAS / CHATS
-- privado = conversa entre duas pessoas
-- grupo = criado por usuário
-- comunidade = relacionada a esporte + localização
-- =========================================================

CREATE TABLE Conversa (
    id_conversa INT AUTO_INCREMENT PRIMARY KEY,
    tipo_conversa ENUM('privado', 'grupo', 'comunidade') NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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
);

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
);

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
);

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
);

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
        ON DELETE CASCADE
);


-- =========================================================
-- 5. EQUIPES
-- =========================================================

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
);

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
);


-- =========================================================
-- 6. EVENTOS
-- =========================================================

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
        ON DELETE SET NULL
);

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
);

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
);


-- =========================================================
-- 7. SUGESTÕES DE ESPORTES E VOTAÇÃO
-- =========================================================

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
);

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
);


-- =========================================================
-- 8. DENÚNCIAS E MODERAÇÃO ADMINISTRATIVA
-- Uma denúncia pode apontar para usuário, mensagem,
-- conversa ou evento. Os campos não utilizados ficam NULL.
-- =========================================================

CREATE TABLE Denuncia (
    id_denuncia INT PRIMARY KEY AUTO_INCREMENT,
    id_denunciante INT NOT NULL,
    id_denunciado INT NULL,
    id_mensagem INT NULL,
    id_conversa INT NULL,
    id_evento INT NULL,
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

    CONSTRAINT fk_denuncia_adm
        FOREIGN KEY (id_adm)
        REFERENCES Administrador(id_adm)
        ON DELETE SET NULL
);

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
);