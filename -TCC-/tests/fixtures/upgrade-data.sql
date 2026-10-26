-- Synthetic records only; ID 9001 makes identity preservation observable.
INSERT INTO Usuario (id_user,nome_user,email_user,tel_user,senha_user,date_user,email_verificado) VALUES
(9001,'Upgrade Um','upgrade1@localhost.test','11900000001','synthetic-not-a-login-hash','2000-01-01',1),
(9002,'Upgrade Dois','upgrade2@localhost.test','11900000002','synthetic-not-a-login-hash','2000-01-02',1);
INSERT INTO Administrador(id_adm,nome_adm,email_adm,senha_adm,ativo)
VALUES(9001,'Administrador sintético','admin-upgrade@localhost.test','synthetic-not-a-login-hash',1);
INSERT IGNORE INTO Esporte(id_esporte,nome_esporte) VALUES(9001,'Futebol');
INSERT IGNORE INTO LocalEsp(id_local,nome_local,endereco_local,tipo_local,status_local,id_criador)
VALUES(9001,'Ginásio Poliesportivo Ayrton Senna','Rua Oriente Monti, 115 - Centro, Diadema - SP','ginásio','aprovado',NULL);
SET @fixture_esporte=(SELECT id_esporte FROM Esporte WHERE nome_esporte='Futebol');
SET @fixture_local=(SELECT id_local FROM LocalEsp WHERE nome_local='Ginásio Poliesportivo Ayrton Senna');
INSERT INTO Usuario_Esporte(id_user,id_esporte) VALUES(9001,@fixture_esporte);
INSERT INTO Amizade(id_amizade,id_user_1,id_user_2) VALUES(9001,9001,9002);
INSERT INTO Conversa(id_conversa,tipo_conversa) VALUES(9001,'grupo');
INSERT INTO Grupo(id_grupo,id_conversa,id_criador,nome_grupo,descricao_grupo)
VALUES(9001,9001,9001,'Grupo sintético','Preservar esta descrição');
INSERT INTO Participantes_Conversa(id_user,id_conversa) VALUES(9001,9001),(9002,9001);
INSERT INTO Mensagem(id_mensagem,id_conversa,id_remetente,mensagem) VALUES(9001,9001,9001,'Mensagem sintética preservada');
INSERT INTO Evento(id_evento,nome_evento,data_evento,horario_evento,id_esporte,id_local,id_criador)
VALUES(9001,'Evento sintético','2030-01-01','19:00:00',@fixture_esporte,@fixture_local,9001);
INSERT INTO Lista_Evento(id_user,id_evento) VALUES(9001,9001);
INSERT INTO Solicitacao_Vaga_Evento(id_solicitacao,id_evento,id_user,time_num,numero_vaga,status_solicitacao)
VALUES(9001,9001,9001,1,1,'pendente');
INSERT INTO Notificacao(id_notificacao,id_destinatario,id_remetente,id_conversa,id_mensagem,tipo,lida)
VALUES(9001,9002,9001,9001,9001,'mensagem',1);
INSERT INTO Denuncia(id_denuncia,id_denunciante,id_evento,motivo,descricao)
VALUES(9001,9002,9001,'Outro','Denúncia sintética preservada');
INSERT INTO Recuperacao_Senha(id_recuperacao,id_user,token_hash,expiracao)
VALUES(9001,9001,REPEAT('a',64),'2030-01-01 00:00:00');
