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
USE app_zubbo;

INSERT INTO LocalEsp (
    nome_local,
    endereco_local,
    tipo_local,
    status_local,
    id_criador
) VALUES
(
    'Ginásio Poliesportivo Ayrton Senna',
    'Rua Oriente Monti, 115 - Centro, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
),
(
    'Ginásio Poliesportivo Mané Garrincha',
    'Rua dos Cariris, 195 - Piraporinha, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
),
(
    'Ginásio Poliesportivo João do Pulo',
    'Rua El Salvador, 41 - Canhema, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
),
(
    'Ginásio Poliesportivo Rômulo Arantes',
    'Avenida Casa Grande, 483 - Portinari, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
),
(
    'Ginásio Poliesportivo Ivanderlei Pereira - Vandão',
    'Rua Prudente de Moraes, 302 - Promissão, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
),
(
    'Ginásio Poliesportivo Vila Conceição',
    'Rua Caramuru, 1230 - Vila Conceição, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
),
(
    'Ginásio Poliesportivo Eduardo de Jesus Souza',
    'Avenida Curió, 94 - Jardim Campanário, Diadema - SP',
    'ginásio',
    'aprovado',
    NULL
);
