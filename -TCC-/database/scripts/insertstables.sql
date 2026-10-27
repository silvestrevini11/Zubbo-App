-- Dados iniciais reexecutáveis: registros existentes e fontes editadas são preservados.
-- Fontes indicam instalações, sem garantia de reserva ou acesso público.

-- Amplia o tipo em bancos existentes antes de inserir parques, sem migration separada.
SET @zubbo_tipo_parque_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE LocalEsp MODIFY COLUMN tipo_local ENUM(''quadra'',''poliesportivo'',''clube'',''ginásio'',''campo'',''parque'') NOT NULL DEFAULT ''poliesportivo''',
        'SELECT 1')
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'LocalEsp'
      AND column_name = 'tipo_local'
      AND column_type LIKE '%''parque''%'
);
PREPARE zubbo_tipo_parque_stmt FROM @zubbo_tipo_parque_sql;
EXECUTE zubbo_tipo_parque_stmt;
DEALLOCATE PREPARE zubbo_tipo_parque_stmt;

INSERT IGNORE INTO Esporte (nome_esporte) VALUES
('Futebol'),
('Basquete'),
('Vôlei'),
('Futsal'),
('Corrida'),
('Handebol');

UPDATE LocalEsp
SET nome_local = 'Ginásio Poliesportivo Ivanderlei Pereira (Vandão)'
WHERE nome_local = 'Ginásio Poliesportivo Ivanderlei Pereira - Vandão'
  AND endereco_local = 'Rua Prudente de Moraes, 302 - Promissão, Diadema - SP';

INSERT INTO LocalEsp (
    nome_local,
    endereco_local,
    tipo_local,
    status_local,
    id_criador
) VALUES
('Ginásio Poliesportivo Ayrton Senna', 'Rua Oriente Monti, 115 - Centro, Diadema - SP', 'ginásio', 'aprovado', NULL),
('Ginásio Poliesportivo Mané Garrincha', 'Rua dos Cariris, 195 - Piraporinha, Diadema - SP', 'ginásio', 'aprovado', NULL),
('Ginásio Poliesportivo João do Pulo', 'Rua El Salvador, 41 - Canhema, Diadema - SP', 'ginásio', 'aprovado', NULL),
('Ginásio Poliesportivo Rômulo Arantes', 'Avenida Casa Grande, 483 - Portinari, Diadema - SP', 'ginásio', 'aprovado', NULL),
('Ginásio Poliesportivo Ivanderlei Pereira (Vandão)', 'Rua Prudente de Moraes, 302 - Promissão, Diadema - SP', 'ginásio', 'aprovado', NULL),
('Ginásio Poliesportivo Vila Conceição', 'Rua Caramuru, 1230 - Vila Conceição, Diadema - SP', 'ginásio', 'aprovado', NULL),
('Ginásio Poliesportivo Eduardo de Jesus Souza', 'Avenida Curió, 94 - Jardim Campanário, Diadema - SP', 'ginásio', 'aprovado', NULL)
ON DUPLICATE KEY UPDATE id_local=id_local;

INSERT INTO Esporte(nome_esporte,jogadores_por_time,quantidade_times,exige_confirmacao_local)
 VALUES('Futebol Society',7,2,FALSE),('Tênis',1,2,FALSE)
 ON DUPLICATE KEY UPDATE id_esporte=id_esporte;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Quadra de Futebol Society Paineiras','Avenida Luís Carlos Prestes, 352 - Taboão, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.waze.com/live-map/directions/br/sp/quadra-de-futebol-society-paineiras?to=place.ChIJU1h3ATVFzpQRZD5zV9ytNag'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Quadra de Futebol Society Paineiras' AND l.endereco_local='Avenida Luís Carlos Prestes, 352 - Taboão, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Marcelão Futebol Society','Avenida Dona Ruyce Ferraz Alvim, 699 - Vila Nogueira, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://naquadra.com.br/perfil-quadra-de-futebol-society-marcelao-society-sp-diadema-casa-grande-id-00110'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Marcelão Futebol Society' AND l.endereco_local='Avenida Dona Ruyce Ferraz Alvim, 699 - Vila Nogueira, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Signello Esportes Society','Avenida Dom Pedro I, 775 - Conceição, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.futliga.com.br/Local/261'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Signello Esportes Society' AND l.endereco_local='Avenida Dom Pedro I, 775 - Conceição, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Quadra Futebol Society Biro Biro','Avenida Doutor Ulysses Guimarães, 4009 - Canhema, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.locaisdobrasil.com.br/encontre/campo-de-futebol/diadema-sp'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Quadra Futebol Society Biro Biro' AND l.endereco_local='Avenida Doutor Ulysses Guimarães, 4009 - Canhema, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Vila Alice','Rua Guaíra, 345 - Vila Oriental, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.futliga.com.br/Local/529'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Vila Alice' AND l.endereco_local='Rua Guaíra, 345 - Vila Oriental, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Casa Grande','Rua Jadeildo Pereira da Silva, 152 - Casa Grande, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.abcdoabc.com.br/oitavas-de-final-da-copa-diadema-de-futebol-ocorrem-neste-sabado-8-4-2/'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Casa Grande' AND l.endereco_local='Rua Jadeildo Pereira da Silva, 152 - Casa Grande, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Benfica','Rua Melro, 193 - Campanário, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.locaisdobrasil.com.br/encontre/campo-de-futebol/diadema-sp'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Benfica' AND l.endereco_local='Rua Melro, 193 - Campanário, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Quadra da 10','Rua Dona Divina Pereira Chaves, 16 - Serraria, Diadema - SP','quadra','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.futliga.com.br/Local/171'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futsal'
 WHERE l.nome_local='Quadra da 10' AND l.endereco_local='Rua Dona Divina Pereira Chaves, 16 - Serraria, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Ginásio Poliesportivo João do Pulo','Rua El Salvador, 41 - Canhema, Diadema - SP','ginásio','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.g7abc.com.br/diadema/prefeitura-de-diadema-reinaugura-o-ginasio-joao-do-pulo'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futsal'
 WHERE l.nome_local='Ginásio Poliesportivo João do Pulo' AND l.endereco_local='Rua El Salvador, 41 - Canhema, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Ginásio Poliesportivo Ayrton Senna','Rua Oriente Monti, 115 - Centro, Diadema - SP','ginásio','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.dgabc.com.br/Noticia/4211180/diadema-passa-a-ter-centro-de-treinamento-de-excelencia-no-basquete-3x3'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Basquete'
 WHERE l.nome_local='Ginásio Poliesportivo Ayrton Senna' AND l.endereco_local='Rua Oriente Monti, 115 - Centro, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('SESI Diadema','Avenida Paranapanema, 1500 - Taboão, Diadema - SP','clube','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://diadema.sesisp.org.br/noticia/sesi-diadema-inaugura-novo-campo-de-futebol-society'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='SESI Diadema' AND l.endereco_local='Avenida Paranapanema, 1500 - Taboão, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('City Sports Empreend','Rua São Luíz, 158 - Centro, Diadema - SP','quadra','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://diadema.net.br/locacao-de-quadras-em-diadema-sp'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Tênis'
 WHERE l.nome_local='City Sports Empreend' AND l.endereco_local='Rua São Luíz, 158 - Centro, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;


-- Campo usado em campeonato municipal de futebol de campo.
INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Piraporinha','Avenida Dona Ida Cerati Magrini, 721 - Piraporinha, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://www.abcdoabc.com.br/oitavas-de-final-da-copa-diadema-de-futebol-ocorrem-neste-sabado-8-4-2/'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Piraporinha'
   AND l.endereco_local='Avenida Dona Ida Cerati Magrini, 721 - Piraporinha, Diadema - SP'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

-- Novos locais de Diadema. Fontes:
-- Campos: https://www.abcdoabc.com.br/inscricoes-para-escolinhas-gratuitas-de-futebol-em-diadema-comecam-dia-14-1/
-- Quadras comunitárias: https://www.acer.diadema.com.br/esportes.html
-- Parques: https://abcdjornal.com.br/diadema/noticia/2026/10/11/saiba-como-sera-o-funcionamento-de-servicos-em-diadema-no-feriado/amp/
-- Os endereços sem número devem ser confirmados antes de orientar deslocamento ou reserva.
-- Corrige os dois endereços publicados inicialmente sem número, preservando os registros.
UPDATE LocalEsp SET endereco_local='Rua Milton Patrício de Oliveira, 195 - Serraria, Diadema - SP'
WHERE nome_local='Campo Serraria' AND endereco_local='Travessa Monteiro da Cruz, s/n - Serraria, Diadema - SP';
UPDATE LocalEsp SET endereco_local='Rua Barão de Uruguaiana, 139 - Jardim Ruyce, Diadema - SP'
WHERE nome_local='Campo Jardim Ruyce' AND endereco_local='Rua Barão de Uruguaiana, s/n - Jardim Ruyce, Diadema - SP';
INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador) VALUES
('Campo da Associação dos Funcionários Públicos', 'Rua São Marcos, 183 - Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Serraria', 'Rua Milton Patrício de Oliveira, 195 - Serraria, Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Eldorado', 'Avenida Nossa Senhora dos Navegantes, 145 - Eldorado, Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Jardim ABC', 'Rua Cerejeiras, 240 - Taboão, Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Jardim Ruyce', 'Rua Barão de Uruguaiana, 139 - Jardim Ruyce, Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Taperinha', 'Avenida Dona Ruyce Ferraz Alvim, 83 - Vila Nogueira, Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Albatroz', 'Rua Albatroz, 100 - Campanário, Diadema - SP', 'campo', 'aprovado', NULL),
('Campo Paineiras (Ouro Verde)', 'Rua Purus, 532 - Paineiras, Diadema - SP', 'campo', 'aprovado', NULL),
('Quadra do Centro Público Eldorado', 'Rua Bituva, 40 - Eldorado, Diadema - SP', 'quadra', 'aprovado', NULL),
('Quadra Amarela do Sapopema', 'Rua das Palmeiras, s/n - Sapopema, Diadema - SP', 'quadra', 'aprovado', NULL),
('Quadra da Rua Três (Inamar)', 'Rua das Margaridas, s/n - Inamar, Diadema - SP', 'quadra', 'aprovado', NULL),
('Quadra do Jardim Sapopema', 'Rua das Seringueiras, 382 - Jardim Sapopema, Diadema - SP', 'quadra', 'aprovado', NULL),
('Praça CEU das Artes', 'Avenida Afonso Monteiro da Cruz, 254 - Jardim União, Diadema - SP', 'quadra', 'aprovado', NULL),
('Quadra da Rua São Vicente', 'Rua São Vicente, s/n - Centro, Diadema - SP', 'quadra', 'aprovado', NULL),
('Quadra da Rua Inglaterra', 'Rua Inglaterra, s/n - Taboão, Diadema - SP', 'quadra', 'aprovado', NULL),
('Complexo de Esportes e Lazer Vereador Silvio Ferreira Leite', 'Avenida Vereador Juarez Rios de Vasconcelos, 280-488 - Centro, Diadema - SP', 'quadra', 'aprovado', NULL),
('Quadras do Conjunto Habitacional Júpiter', 'Rua Netuno, blocos 5 e 7 - Serraria, Diadema - SP', 'quadra', 'aprovado', NULL),
('Parque do Paço', 'Avenida Antônio Piranga, 1380 - Centro, Diadema - SP', 'parque', 'aprovado', NULL),
('Parque Takebe', 'Rua Yokohama, s/n - Jardim Takebe, Diadema - SP', 'parque', 'aprovado', NULL),
('Parque Ecológico do Eldorado', 'Avenida Nossa Senhora dos Navegantes, 145 - Eldorado, Diadema - SP', 'parque', 'aprovado', NULL),
('Parque Saned', 'Rua Santa Cruz, s/n - Jardim Canhema, Diadema - SP', 'parque', 'aprovado', NULL),
('Parque Regional Oeste', 'Rua Érico Veríssimo, 311 - Parque Real, Diadema - SP', 'parque', 'aprovado', NULL)
ON DUPLICATE KEY UPDATE id_local=id_local;

INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
SELECT l.id_local,e.id_esporte,
       'https://www.abcdoabc.com.br/inscricoes-para-escolinhas-gratuitas-de-futebol-em-diadema-comecam-dia-14-1/'
FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
WHERE l.nome_local IN ('Campo da Associação dos Funcionários Públicos','Campo Serraria','Campo Eldorado',
    'Campo Jardim ABC','Campo Jardim Ruyce','Campo Taperinha','Campo Albatroz','Campo Paineiras (Ouro Verde)')
ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
SELECT l.id_local,e.id_esporte,
       'https://abcdjornal.com.br/diadema/noticia/2026/10/11/saiba-como-sera-o-funcionamento-de-servicos-em-diadema-no-feriado/amp/'
FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Corrida'
WHERE l.nome_local IN ('Parque do Paço','Parque Takebe','Parque Ecológico do Eldorado',
    'Parque Saned','Parque Regional Oeste')
ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
SELECT l.id_local,e.id_esporte,
       'https://abcreporter.com.br/2025/12/09/diadema-entrega-quadras-de-basquete-3x3-em-area-que-era-utilizada-para-pancadao/'
FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Basquete'
WHERE l.nome_local='Quadras do Conjunto Habitacional Júpiter'
ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
SELECT l.id_local,e.id_esporte,
       'https://portalabc.com.br/cidades/diadema-entrega-entrega-reforma-do-complexo-esportivo-silvio-ferreira-leite/'
FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Basquete'
WHERE l.nome_local='Complexo de Esportes e Lazer Vereador Silvio Ferreira Leite'
ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

-- Modalidades divulgadas na Virada Esportiva de Diadema em junho de 2026.
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://americatv.com.br/ver_noticia?id=50'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Vôlei'
 WHERE l.nome_local='Ginásio Poliesportivo Ayrton Senna'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://americatv.com.br/ver_noticia?id=50'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futsal'
 WHERE l.nome_local='Ginásio Poliesportivo Mané Garrincha'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://americatv.com.br/ver_noticia?id=50'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Handebol'
 WHERE l.nome_local='Ginásio Poliesportivo Mané Garrincha'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://americatv.com.br/ver_noticia?id=50'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Vôlei'
 WHERE l.nome_local='Ginásio Poliesportivo Rômulo Arantes'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://americatv.com.br/ver_noticia?id=50'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futsal'
 WHERE l.nome_local='Ginásio Poliesportivo Ivanderlei Pereira (Vandão)'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

-- Modalidades por tipo de local informadas pela administração do Zubbo.
-- Inclusão reexecutável: preserva modalidades e fontes já cadastradas.
INSERT INTO Local_Esporte_Permitido (id_local, id_esporte, fonte)
SELECT l.id_local, e.id_esporte,
       'Modalidades por tipo de local informadas pela administração do Zubbo'
FROM LocalEsp l
CROSS JOIN Esporte e
WHERE (l.tipo_local = 'ginásio' AND e.nome_esporte IN ('Futsal', 'Vôlei', 'Basquete', 'Handebol'))
   OR (l.tipo_local = 'quadra' AND e.nome_esporte IN ('Handebol', 'Futsal', 'Vôlei'))
ON DUPLICATE KEY UPDATE id_local = Local_Esporte_Permitido.id_local;
