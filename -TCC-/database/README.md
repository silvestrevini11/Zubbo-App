# Banco de dados

Execute os comandos a partir da raiz de `-TCC-`, com a conexão apontando explicitamente para o banco correto e `utf8mb4`. O alvo de hospedagem é MySQL 8.4. Faça backup e valide restauração antes de atualizar uma base existente. DDL do MySQL faz commit implícito: falhas exigem revisão antes de continuar.

## Instalação nova

No banco vazio, execute somente `scripts/criacaotables.sql` e depois `scripts/insertstables.sql`. O primeiro contém a estrutura final, o segundo 8 esportes, 18 locais e fontes de modalidades. Não aplique migrations após criar a estrutura final. Os inserts podem ser repetidos: preservam dados e fontes existentes. Contadores AUTO_INCREMENT podem avançar em conflitos sem criar registros duplicados.

`scripts/querytables.sql` contém consultas de leitura com campos explícitos e limites, sem selecionar hashes, códigos ou tokens. Não há `USE` fixo: o destino pertence à configuração da conexão.

## Atualização de bancos existentes

Execute as cinco migrations em ordem, interrompendo no primeiro erro (sem `--force`). Cada alteração consulta `information_schema`; pode ser repetida em banco atualizado ou parcialmente migrado. Depois execute os inserts se desejar completar os dados iniciais e associações. Migrations modificam estrutura e transformam solicitações e notificações legadas; não inserem locais ou modalidades de catálogo.

* Migration ativa: 001_usuarios_administracao; SQL anterior arquivado: 002_admin_user_relation, 007_bloqueio_usuario, add_recuperacao_senha
* Migration ativa: 002_locais_denuncias; SQL anterior arquivado: 003_deduplicate_locations, 004_denuncia_local, 006_foto_local, tipo campo de 014
* Migration ativa: 003_eventos_grupos; SQL anterior arquivado: add/upgrade_solicitacao_vaga_evento, 008_fila_espera, 009_editar_times, 011_duracao_eventos, Grupo_Evento de 015
* Migration ativa: 004_notificacoes_push; SQL anterior arquivado: 005_notificacao_leitura, 013_web_push, Notificacao.id_evento de 015
* Migration ativa: 005_modalidades_confirmacoes; SQL anterior arquivado: 010_esporte_moderacao, 012_confirmacao_esportes_locais, estrutura de 014
* Migration ativa: scripts/insertstables.sql; SQL anterior arquivado: inserts anteriores, dados/fontes de 014 e regra de Futsal de 016

O arquivo `archive/pre-consolidacao/` é histórico, não uma segunda sequência ativa. Duplicidades normalizadas de local, administradores ou vagas confirmadas interrompem a migration com erro identificável `ERRO_*_resolver_antes_de_migrar`. Resolva manualmente preservando todos os vínculos, a partir de backup. Nenhum local é apagado ou associado automaticamente. Administradores legados sem `id_user` permanecem sem privilégio por e-mail: a vinculação deve ser concedida explicitamente pela ferramenta administrativa. Valores de confirmação de modalidade já editados são preservados; o default para novos registros passa a FALSE.

## Verificação

`php tests/audit-migrations.php` executa apenas leitura e relata compatibilidade do estado atual com os cinco domínios. Não comprova histórico de execução. `php tests/db-smoke.php` usa apenas banco descartável vazio, autorizado por `ZUBBO_TEST_ALLOW_SCHEMA=1` e nome `zubbo_ci` ou `zubbo_test_*`. Os testes nunca devem apontar para a base publicada.
