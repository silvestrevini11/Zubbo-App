-- Dados iniciais reexecutáveis: registros existentes e fontes editadas são preservados.
-- Fontes indicam instalações, sem garantia de reserva ou acesso público.

INSERT IGNORE INTO Esporte (nome_esporte) VALUES
('Futebol'),
('Basquete'),
('Vôlei'),
('Futsal'),
('Corrida'),
('Handebol');

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
('Ginásio Poliesportivo Ivanderlei Pereira - Vandão', 'Rua Prudente de Moraes, 302 - Promissão, Diadema - SP', 'ginásio', 'aprovado', NULL),
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
 WHERE l.nome_local='Ginásio Poliesportivo Ivanderlei Pereira - Vandão'
 ON DUPLICATE KEY UPDATE id_local=Local_Esporte_Permitido.id_local;

-- 016 - Regra interna do Zubbo: habilitar Futsal em todos os locais do tipo ginásio.
-- A inclusão é idempotente e preserva a fonte de associações já existentes.
-- É preciso confirmar a disponibilidade e autorização do responsável pelo local.
INSERT IGNORE INTO Local_Esporte_Permitido (id_local, id_esporte, fonte)
SELECT l.id_local, e.id_esporte,
       'Habilitado por regra de ginásio do Zubbo - confirmar uso com o responsável'
FROM LocalEsp l
INNER JOIN Esporte e ON e.nome_esporte = 'Futsal'
WHERE l.tipo_local = 'ginásio';
