-- Somente leitura. Selecione o banco na conexão. Não contém credenciais ou códigos.
SELECT id_user, nome_user, status_user, email_verificado FROM Usuario ORDER BY id_user LIMIT 100;
SELECT id_esporte, nome_esporte, jogadores_por_time, quantidade_times FROM Esporte ORDER BY id_esporte LIMIT 100;
SELECT id_local, nome_local, endereco_local, tipo_local, status_local FROM LocalEsp ORDER BY id_local LIMIT 100;
SELECT id_evento, nome_evento, data_evento, horario_evento, duracao_minutos, id_local, id_esporte, status_evento FROM Evento ORDER BY data_evento DESC LIMIT 100;
SELECT id_grupo, nome_grupo, id_criador, id_conversa FROM Grupo ORDER BY id_grupo LIMIT 100;
SELECT id_notificacao, id_destinatario, tipo, lida, data_leitura FROM Notificacao ORDER BY id_notificacao DESC LIMIT 100;
SELECT id_solicitacao, id_evento, id_user, time_num, numero_vaga, status_solicitacao FROM Solicitacao_Vaga_Evento ORDER BY id_solicitacao DESC LIMIT 100;
SELECT id_evento, id_user, data_entrada FROM Fila_Espera_Evento ORDER BY data_entrada LIMIT 100;
SELECT id_local, id_esporte, fonte FROM Local_Esporte_Permitido ORDER BY id_local, id_esporte LIMIT 100;
SELECT id_local, id_esporte, confirmado_em FROM Local_Esporte_Confirmacao ORDER BY confirmado_em DESC LIMIT 100;
SELECT id_grupo, id_evento, criado_em FROM Grupo_Evento ORDER BY criado_em DESC LIMIT 100;
SELECT 'Usuario' AS tabela, COUNT(*) AS total FROM Usuario UNION ALL SELECT 'Evento', COUNT(*) FROM Evento UNION ALL SELECT 'LocalEsp', COUNT(*) FROM LocalEsp;
SELECT nome_local, endereco_local, COUNT(*) AS duplicados FROM LocalEsp GROUP BY nome_local, endereco_local HAVING COUNT(*) > 1 LIMIT 100;
