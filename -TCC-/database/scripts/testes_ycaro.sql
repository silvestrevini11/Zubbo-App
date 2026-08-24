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

INSERT INTO Esporte (nome_esporte) VALUES
('Futebol'),
('Basquete'),
('Vôlei'),
('Tênis'),
('Futesal'),
('Handebol');

ALTER TABLE Usuario
ADD foto_user VARCHAR(255) NULL;

select * from Usuario;
select * from Esporte;
select * from Usuario_Esporte
