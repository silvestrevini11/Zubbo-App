-- Migração única: quadras, campos e modalidades por local com fonte de pesquisa.
-- Fontes informam instalações e esportes, NÃO garantem reservas ou acesso público.
ALTER TABLE LocalEsp MODIFY COLUMN tipo_local ENUM('quadra','poliesportivo','clube','ginásio','campo') NOT NULL DEFAULT 'poliesportivo';

CREATE TABLE IF NOT EXISTS Local_Esporte_Permitido (
 id_local INT NOT NULL,
 id_esporte INT NOT NULL,
 fonte VARCHAR(500) NOT NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id_local,id_esporte),
 FOREIGN KEY (id_local) REFERENCES LocalEsp(id_local) ON DELETE CASCADE,
 FOREIGN KEY (id_esporte) REFERENCES Esporte(id_esporte) ON DELETE CASCADE
);

INSERT INTO Esporte(nome_esporte,jogadores_por_time,quantidade_times,exige_confirmacao_local)
 VALUES('Futebol Society',7,2,FALSE),('Tênis',1,2,FALSE)
 ON DUPLICATE KEY UPDATE jogadores_por_time=VALUES(jogadores_por_time), quantidade_times=VALUES(quantidade_times);

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Quadra de Futebol Society Paineiras','Avenida Luís Carlos Prestes, 352 - Taboão, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.waze.com/live-map/directions/br/sp/quadra-de-futebol-society-paineiras?to=place.ChIJU1h3ATVFzpQRZD5zV9ytNag'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Quadra de Futebol Society Paineiras' AND l.endereco_local='Avenida Luís Carlos Prestes, 352 - Taboão, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Marcelão Futebol Society','Avenida Dona Ruyce Ferraz Alvim, 699 - Vila Nogueira, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://naquadra.com.br/perfil-quadra-de-futebol-society-marcelao-society-sp-diadema-casa-grande-id-00110'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Marcelão Futebol Society' AND l.endereco_local='Avenida Dona Ruyce Ferraz Alvim, 699 - Vila Nogueira, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Signello Esportes Society','Avenida Dom Pedro I, 775 - Conceição, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.futliga.com.br/Local/261'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Signello Esportes Society' AND l.endereco_local='Avenida Dom Pedro I, 775 - Conceição, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Quadra Futebol Society Biro Biro','Avenida Doutor Ulysses Guimarães, 4009 - Canhema, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.locaisdobrasil.com.br/encontre/campo-de-futebol/diadema-sp'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='Quadra Futebol Society Biro Biro' AND l.endereco_local='Avenida Doutor Ulysses Guimarães, 4009 - Canhema, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Vila Alice','Rua Guaíra, 345 - Vila Oriental, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.futliga.com.br/Local/529'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Vila Alice' AND l.endereco_local='Rua Guaíra, 345 - Vila Oriental, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Casa Grande','Rua Jadeildo Pereira da Silva, 152 - Casa Grande, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.abcdoabc.com.br/oitavas-de-final-da-copa-diadema-de-futebol-ocorrem-neste-sabado-8-4-2/'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Casa Grande' AND l.endereco_local='Rua Jadeildo Pereira da Silva, 152 - Casa Grande, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Benfica','Rua Melro, 193 - Campanário, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.locaisdobrasil.com.br/encontre/campo-de-futebol/diadema-sp'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Benfica' AND l.endereco_local='Rua Melro, 193 - Campanário, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Quadra da 10','Rua Dona Divina Pereira Chaves, 16 - Serraria, Diadema - SP','quadra','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.futliga.com.br/Local/171'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futsal'
 WHERE l.nome_local='Quadra da 10' AND l.endereco_local='Rua Dona Divina Pereira Chaves, 16 - Serraria, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Ginásio Poliesportivo João do Pulo','Rua El Salvador, 41 - Canhema, Diadema - SP','ginásio','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.g7abc.com.br/diadema/prefeitura-de-diadema-reinaugura-o-ginasio-joao-do-pulo'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futsal'
 WHERE l.nome_local='Ginásio Poliesportivo João do Pulo' AND l.endereco_local='Rua El Salvador, 41 - Canhema, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Ginásio Poliesportivo Ayrton Senna','Rua Oriente Monti, 115 - Centro, Diadema - SP','ginásio','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://www.dgabc.com.br/Noticia/4211180/diadema-passa-a-ter-centro-de-treinamento-de-excelencia-no-basquete-3x3'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Basquete'
 WHERE l.nome_local='Ginásio Poliesportivo Ayrton Senna' AND l.endereco_local='Rua Oriente Monti, 115 - Centro, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('SESI Diadema','Avenida Paranapanema, 1500 - Taboão, Diadema - SP','clube','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://diadema.sesisp.org.br/noticia/sesi-diadema-inaugura-novo-campo-de-futebol-society'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol Society'
 WHERE l.nome_local='SESI Diadema' AND l.endereco_local='Avenida Paranapanema, 1500 - Taboão, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;

INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('City Sports Empreend','Rua São Luíz, 158 - Centro, Diadema - SP','quadra','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local, e.id_esporte, 'https://diadema.net.br/locacao-de-quadras-em-diadema-sp'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Tênis'
 WHERE l.nome_local='City Sports Empreend' AND l.endereco_local='Rua São Luíz, 158 - Centro, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;


-- Campo usado em campeonato municipal de futebol de campo.
INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local,status_local,id_criador)
 VALUES('Campo Piraporinha','Avenida Dona Ida Cerati Magrini, 721 - Piraporinha, Diadema - SP','campo','aprovado',NULL)
 ON DUPLICATE KEY UPDATE id_local=id_local;
INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte)
 SELECT l.id_local,e.id_esporte,'https://www.abcdoabc.com.br/oitavas-de-final-da-copa-diadema-de-futebol-ocorrem-neste-sabado-8-4-2/'
 FROM LocalEsp l JOIN Esporte e ON e.nome_esporte='Futebol'
 WHERE l.nome_local='Campo Piraporinha'
   AND l.endereco_local='Avenida Dona Ida Cerati Magrini, 721 - Piraporinha, Diadema - SP'
 ON DUPLICATE KEY UPDATE fonte=VALUES(fonte), atualizado_em=CURRENT_TIMESTAMP;
