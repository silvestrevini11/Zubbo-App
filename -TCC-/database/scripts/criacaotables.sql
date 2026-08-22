create database app_zubbo;
use app_zubbo;

create table Usuario(
id_user int primary key auto_increment,
nome_user varchar(50) not null,
email_user varchar(70) not null,
tel_user char(20) not null,
datanasc_user date not null
);

create table Conversa(
id_conversa int primary key auto_increment,
nome_conversa varchar(30) not null,
tipo_conversa enum('chat direto','grupo','comunidade') not null /*limita os valores possíveis.*/
);

create table Mensagem(
id_mensagem int primary key auto_increment,
texto_mensagem varchar(1000),
date_mensagem datetime,
id_user int,
id_conversa int,

constraint id_userFK foreign key (id_user) references Usuario(id_user)
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

create table Esporte(
id_esporte int primary key auto_increment,
nome_esporte varchar(35) not null
);
create table evento (
    id_evento int primary key auto_increment,
    data_evento date not null,
    nome_evento varchar(100) not null,
    horario_evento time not null,
    id_esporte int not null,
    id_criador int not null,

    constraint fk_evento_esporte foreign key (id_esporte) references esporte(id_esporte),
	constraint fklocal_evento foreign key (id_local) references LocalEsp(id_local),
    constraint fk_evento_usuario foreign key (id_criador) references usuario(id_user)
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

    constraint fk_voto_sugestao foreign key (id_sugestao)
    references sugestao_esporte(id_sugestao),

    constraint fk_voto_usuario foreign key (id_user)
    references usuario(id_user)
);

create table sugestao_esporte (
    cod_sugestao int primary key auto_increment,
    nome_esporte varchar(50) not null,
    id_user int not null,
    status_sugestao enum('pendente', 'aprovada', 'rejeitada') not null default 'pendente',

    constraint fk_sugestao_usuario foreign key (id_user)
    references usuario(id_user)
);
create table LocalEsp(
    id_local int primary key auto_increment,
    nome_local varchar(60) not null,
    endereco_local varchar(120) not null,
    tipo_local enum('quadra', 'poliesportivo', 'clube', 'ginásio') not null
 default 'poliesportivo'
);

alter table Mensagem
	add constraint id_conversaFK foreign key (id_conversa) references Conversa(id_conversa); 
alter table Usuario
	add password_user varchar(35);