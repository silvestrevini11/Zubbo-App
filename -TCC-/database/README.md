# Banco de dados

Execute os comandos a partir da raiz de `-TCC-`, com a conexão apontando explicitamente para o banco correto e `utf8mb4`. O alvo de hospedagem é MySQL 8.4. Faça backup e valide restauração antes de atualizar uma base existente. DDL do MySQL faz commit implícito: falhas exigem revisão antes de continuar.

## Instalação nova

No banco vazio, execute somente `scripts/criacaotables.sql` e depois `scripts/insertstables.sql`. O primeiro contém a estrutura final, o segundo 8 esportes, 18 locais e fontes de modalidades. Não aplique migrations após criar a estrutura final. Os inserts podem ser repetidos: preservam dados e fontes existentes. Contadores AUTO_INCREMENT podem avançar em conflitos sem criar registros duplicados.

`scripts/querytables.sql` contém consultas de leitura com campos explícitos e limites, sem selecionar hashes, códigos ou tokens. Não há `USE` fixo: o destino pertence à configuração da conexão.

## Atualização de bancos existentes

Execute as cinco migrations em ordem, interrompendo no primeiro erro (sem `--force`). Cada alteração consulta `information_schema`; pode ser repetida em banco atualizado ou parcialmente migrado. Depois execute os inserts se desejar completar os dados iniciais e associações. Migrations modificam estrutura e transformam solicitações e notificações legadas; não inserem locais ou modalidades de catálogo.

Os inserts associam Futsal, Vôlei, Basquete e Handebol a todos os ginásios, e Handebol, Futsal e Vôlei a todas as quadras. As associações e fontes já cadastradas são preservadas.

* Migration ativa: 001_usuarios_administracao; SQL anterior arquivado: 002_admin_user_relation, 007_bloqueio_usuario, add_recuperacao_senha
* Migration ativa: 002_locais_denuncias; SQL anterior arquivado: 003_deduplicate_locations, 004_denuncia_local, 006_foto_local, tipo campo de 014
* Migration ativa: 003_eventos_grupos; SQL anterior arquivado: add/upgrade_solicitacao_vaga_evento, 008_fila_espera, 009_editar_times, 011_duracao_eventos, Grupo_Evento de 015
* Migration ativa: 004_notificacoes_push; SQL anterior arquivado: 005_notificacao_leitura, 013_web_push, Notificacao.id_evento de 015
* Migration ativa: 005_modalidades_confirmacoes; SQL anterior arquivado: 010_esporte_moderacao, 012_confirmacao_esportes_locais, estrutura de 014
* Migration ativa: scripts/insertstables.sql; SQL anterior arquivado: inserts anteriores, dados/fontes de 014 e regra de Futsal de 016

O arquivo `archive/pre-consolidacao/` é histórico, não uma segunda sequência ativa. Duplicidades normalizadas de local, administradores ou vagas confirmadas interrompem a migration com erro identificável `ERRO_*_resolver_antes_de_migrar`. Resolva manualmente preservando todos os vínculos, a partir de backup. Nenhum local é apagado ou associado automaticamente. Administradores legados sem `id_user` permanecem sem privilégio por e-mail: a vinculação deve ser concedida explicitamente pela ferramenta administrativa. Valores de confirmação de modalidade já editados são preservados; o default para novos registros passa a FALSE.

## Verificação

`php tests/audit-migrations.php` executa apenas leitura e relata compatibilidade do estado atual com os cinco domínios. Não comprova histórico de execução: não há ledger retroativo. Confere tipos, nulabilidade, defaults, geração de colunas, índices, restrições, ações de chaves estrangeiras, engine e collation. Nomes equivalentes de índices são aceitos. Com `--strict`, retorna `0` para compatibilidade, `1` para divergências e `2` para erro de execução; sem a opção, divergências continuam sendo diagnóstico com retorno `0`, e erros de execução retornam `2`.

As suítes abaixo exigem **bancos descartáveis inicialmente vazios**, autorização explícita `ZUBBO_TEST_ALLOW_SCHEMA=1` e nome `zubbo_ci` ou `zubbo_test_*`. A conexão deve ser configurada com `ZUBBO_DB_HOST`, `ZUBBO_DB_PORT`, `ZUBBO_DB_USER`, `ZUBBO_DB_PASSWORD` e `ZUBBO_DB_NAME`. Crie os bancos vazios separadamente; os verificadores não criam nem removem bancos. Nunca use a base publicada ou uma cópia com dados reais.

* `php tests/db-smoke.php`: instalação nova, somente criação e inserts, seguida de fixtures e regras de negócio.
* `php tests/db-upgrade.php`: suíte separada, em outro banco vazio, por exemplo `zubbo_test_upgrade`. Compara a instalação nova com uma base legada sintética, uma base parcial e uma base atual. Confere estrutura semântica, os oito esportes, os 18 locais, associações/fontes, preservação de registros e vínculos, transformações legadas, reexecução das cinco migrations e dos inserts após edição de campos e fontes. Exercita conflitos de administradores, locais normalizados e vagas confirmadas; exige interrupção identificável e preservação dos registros. Também rejeita coluna/chave estrangeira existente com definição incorreta e valida os códigos de saída da auditoria CLI.

A suíte de upgrades remove **somente as tabelas conhecidas de suas próprias fixtures**, entre cenários, dentro do banco explicitamente autorizado. Recusa uma base não vazia no início e qualquer tabela desconhecida antes da limpeza. Ao final, mantém o schema atual e dados sintéticos para `php tests/audit-migrations.php --strict`. Após falha, mantém as evidências; revise o diagnóstico e provisione outro banco descartável vazio para uma nova execução.

As cinco migrations completam colunas com default/NULL e índices/vínculos ausentes quando isso é seguro. Definições incompatíveis interrompem com marcadores `ERRO_def_*` ou `ERRO_fk_*`, sem adivinhar conversões. A existência de uma tabela ou coluna não basta para aprovação. Como DDL tem commits implícitos, uma interrupção pode deixar alterações aditivas anteriores aplicadas; rollback de código não reverte essas alterações.

`php database/tools/migration-contract.php` verifica se as pós-condições SQL geradas correspondem ao contrato do script de criação. Para regenerá-las após uma alteração deliberada na estrutura, use `php database/tools/migration-contract.php --write` e revise o diff. A auditoria e a comparação da suíte usam o mesmo contrato, sem consultar dados sensíveis em seus relatórios.

O alvo de aprovação continua PHP 8.4 com MySQL 8.4 no CI. Resultados em MySQL 8.0 são evidência complementar e não autorizam a publicação.
