# Desenvolvimento da branch `teste-alteracoes`

Branch isolada criada a partir da `main`. **Não foi feito merge nem deploy.**
**Não executar estas migrations diretamente no banco de produção.**

## Preparação da demo

1. Crie um backup do banco de demonstração no Railway/MySQL.
2. Gere uma cópia de teste restaurando esse backup. Garanta que a instância de código dessa branch aponta somente para o banco de teste.
3. Execute **uma única vez, em ordem**, usando DBeaver ou cliente MySQL, os arquivos:
   - `database/migrations/005_notificacao_leitura.sql`
   - `database/migrations/006_foto_local.sql`
   - `database/migrations/007_bloqueio_usuario.sql`
   - `database/migrations/008_fila_espera.sql`
   - `database/migrations/009_editar_times.sql`
   - `database/migrations/010_esporte_moderacao.sql`
4. Confirme tabelas e colunas novas. Não reaplique as migrations com `ALTER TABLE`, pois não são idempotentes.
5. Para a demo faça upload da branch `teste-alteracoes` em ambiente isolado.
6. Teste login, criação de grupo, sair do grupo, amizade/bloqueio, lista de eventos, fila, edição de times, mapa, fotos e modo escuro.
7. Não copie dados fictícios nem fotos de usuários reais entre ambientes sem autorização.

**Observação:** o script de criação inicial `database/scripts/criacaotables.sql` permanece no formato anterior. Para instalar a versão desta branch em um banco novo, rode primeiro criação + inserts e depois as migrations 005 a 010. As migrations anteriores 002 a 004 seguem as necessidades da instalação existente.

## Checklist técnico

- [x] Sair de grupo: ação autenticada, CSRF, confirmação, impede saída do criador e remove somente vínculo/avisos desse usuário.
- [x] Desfazer amizade: ação autenticada, CSRF, confirmação, remove registros de amizade e solicitações associadas.
- [x] Bloquear/desbloquear: tabela dedicada, botões no perfil, bloqueia solicitações de amizade, mensagens e conversas privadas. Não bloqueia participação compartilhada em grupos/eventos.
- [x] Filtro por local: listagem de eventos filtrável por poliesportivo ou quadra, link pelo popup do mapa.
- [x] Criar quadras e locais: formulário no ADM com nome/endereço/tipo e validação.
- [x] Fotos dos locais: upload controlado no ADM e visualização do popup com foto quando existir.
- [x] Editar informações dos times: nome dos dois times e descrição por evento, autorizado ao criador.
- [x] Indicador vermelho no Início quando houver notificações não lidas.
- [x] Renovação do ícone: usa o arquivo existente ZubboSemFundo.png em favicon e apple-touch-icon.
- [x] Cache no workflow de lint PHP; GitHub Actions continua validando syntax.
- [~] Fila de espera: inscrito pode entrar/sair e, ao liberar vaga, primeiro elegível vira pedido pendente para o organizador aprovar; testes de concorrência e vagas legadas ainda necessários.
- [~] Horários dos locais: agenda de eventos registrados no Zubbo; **não consulta nem confirma reservas oficiais do estabelecimento/prefeitura**. Conflito de horário igual é checado, mas precisa de controle transacional de concorrência.
- [~] Esportes sugeridos: ADM pode aprovar/rejeitar, incluir foto, regras, jogadores/time e quantidade de times; limite de jogadores configurável usado na escalação. Checkbox de confirmação local ainda **não verifica aceite real** de cada poliesportivo.
- [~] Notificação após leitura: grava data_leitura, exclui após 20 min na próxima visita/consulta de notificações; **não há limpeza por cron exato**.
- [~] Notificações do navegador: permissão por ação do usuário, alertas quando houver incremento no número de não lidas com Zubbo ainda aberto; **não são push em segundo plano**.
- [~] Tema escuro: ajustes pontuais no CSS de eventos e componentes novos; triângulo relatado e toda a interface requerem teste visual.
- [~] Inativos: login já recusava contas suspensas, middleware verifica também sessões antigas; listagem de amigos e perfil público escondem suspensos. Auditar outras rotas antigas para cobertura completa.
- [~] Permissões: mapa passou a solicitar geolocalização somente quando o usuário aciona o controle. Upload usa seletor padrão do navegador, que não pede uma permissão ampla de armazenamento.
- [ ] Limpar dados de teste: **não realizado** por ser operação destrutiva sem lista de IDs/ambiente e backup confirmados.

Monetização: propositalmente fora do escopo.

## Pontos de atenção

- Fotos são armazenadas em `public/uploads`: armazenamento local pode ser **temporário no Railway**. Para persistência após redeploy, substituir por armazenamento de objetos externo.
- Validar no MySQL da demo antes de qualquer release; GitHub Actions "Demo MySQL Integration" executa com banco temporário quando ocorre push nesta branch.
- O cache do PHP lint acelera a verificação de código idêntico, não é cache da aplicação.
- Se o navegador não oferecer a API Notification ou seu suporte for limitado, as mensagens ainda podem ser consultadas no aplicativo.
- Somente uma solicitação em fila por usuário/evento, e a aprovação final continua a cargo do criador do evento.
- Todas as rotas PHP com edição de banco devem receber smoke test de segurança/concorrência antes de ir para a main.
