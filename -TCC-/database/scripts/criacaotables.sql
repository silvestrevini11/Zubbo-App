create database app_zubbo;
use app_zubbo;

CREATE TABLE Usuario(
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
CREATE TABLE Usuario_Esporte (
    id_user INT NOT NULL,
    id_esporte INT NOT NULL,

    PRIMARY KEY (id_user, id_esporte),

    FOREIGN KEY (id_user) REFERENCES Usuario(id_user),
    FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte)
);

CREATE TABLE Conversa (
    id_conversa INT AUTO_INCREMENT PRIMARY KEY,
    id_user_a INT NOT NULL,
    id_user_b INT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_user_a) REFERENCES Usuario(id_user),
    FOREIGN KEY (id_user_b) REFERENCES Usuario(id_user),

    UNIQUE (id_user_a, id_user_b)
);

CREATE TABLE Mensagem (
    id_mensagem INT AUTO_INCREMENT PRIMARY KEY,
    id_conversa INT NOT NULL,
    id_remetente INT NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_conversa) REFERENCES Conversa(id_conversa),
    FOREIGN KEY (id_remetente) REFERENCES Usuario(id_user)
);
create table Participantes_Conversa(
id_user int,
id_conversa int,

 primary key(id_user, id_conversa), /*define as duas FK como chave primaria composta, porque a combinação entre as duas FK nunca pode se repetir entao o mesmo usuario nao pode estar duas vezes na conversa*/
 constraint userFK foreign key (id_user) references Usuario(id_user),
 constraint conversaFK foreign key (id_conversa) references Conversa(id_conversa)
);

create table Equipe(
id_equipe int primary key auto_increment,
nome_equipe varchar(30) not null,
id_esporte int,
id_criador int,

constraint id_esporteFK foreign key (id_esporte) references Esporte(id_esporte),
constraint id_criadorFK foreign key (id_criador) references Usuario(id_user)
);

CREATE TABLE ParticipantesEquipe (
    id_partEquipe int primary key auto_increment,
    id_user int,
    id_equipe int,

    constraint id_usuarioFK foreign key(id_user) references Usuario(id_user),
    constraint id_equipeFK foreign key (id_equipe) references Equipe(id_equipe),

    UNIQUE(id_user, id_equipe)
);
create table evento (
    id_evento int primary key auto_increment,
    data_evento date not null,
    nome_evento varchar(100) not null,
    horario_evento time not null,
    id_esporte int not null,
    id_local int not null,
    id_criador int not null,

    constraint fk_evento_esporte foreign key (id_esporte) references esporte(id_esporte),
	constraint fklocal_evento foreign key (id_local) references LocalEsp(id_local),
    constraint fk_evento_usuario foreign key (id_criador) references Usuario(id_user)
);

create table equipesevento (
    id_evento int not null,
    id_equipe int not null,

    primary key (id_evento, id_equipe),

    constraint fk_equipesevento_evento foreign key (id_evento) references evento(id_evento),
    constraint fk_equipesevento_equipe foreign key (id_equipe) references equipe(id_equipe)
);

create table lista_evento (
    id_user int not null,
    id_evento int not null,

    primary key (id_user, id_evento),

    constraint fk_listaevento_usuario foreign key (id_user) references Usuario(id_user),
    constraint fk_listaevento_evento foreign key (id_evento) references evento(id_evento)
);
create table voto_sugestao (
    id_sugestao int not null,
    id_user int not null,

    primary key (id_sugestao, id_user),
    constraint fk_voto_sugestao foreign key (id_sugestao) references sugestao_esporte(id_sugestao),
    constraint fk_voto_usuario foreign key (id_user) references Usuario(id_user)
);

create table sugestao_esporte (
    cod_sugestao int primary key auto_increment,
    nome_esporte varchar(50) not null,
    id_user int not null,
    status_sugestao enum('pendente', 'aprovada', 'rejeitada') not null default 'pendente',

    constraint fk_sugestao_usuario foreign key (id_user) references usuario(id_user)
);
create table LocalEsp(
    id_local int primary key auto_increment,
    nome_local varchar(60) not null,
    endereco_local varchar(120) not null,
    tipo_local varchar(50) not null
);
