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



INSERT INTO Esporte (nome_esporte) VALUES
('Futebol'),
('Basquete'),
('Vôlei'),
('Futsal'),
('Corrida'),
('Handebol');

ALTER TABLE Usuario
ADD foto_user VARCHAR(255) NULL;

select * from Usuario;
select * from Esporte;
select * from Usuario_Esporte;
select * from Mensagem;
select * from Conversa;