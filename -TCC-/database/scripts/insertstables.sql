use app_zubbo;
INSERT INTO Esporte (nome_esporte) VALUES
('Futebol'),
('Basquete'),
('Vôlei'),
('Futsal'),
('Corrida'),
('Handebol');

INSERT INTO Administrador (
    nome_adm,
    email_adm,
    senha_adm
) VALUES (
    'Administrador Zubbo',
    'zubbosupport@gmail.com',
    'admin'
);

-- Local para testar a criação de eventos.
INSERT INTO LocalEsp
    (nome_local, endereco_local, tipo_local, status_local)
VALUES
    (
        'Ginásio Poliesportivo Vila Conceição',
        'Rua Caramuru, 1230 - Conceição, Diadema',
        'ginásio',
        'aprovado'
    );
