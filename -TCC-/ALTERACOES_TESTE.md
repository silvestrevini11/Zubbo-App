# Revisão e desenvolvimento — branch `teste-alteracoes`

**Situação:** código atualizado apenas nesta branch. Sem merge, sem deploy, sem alteração do banco Railway, sem acesso aos e-mails de usuários reais. Testes de PHP e MySQL são executados no GitHub Actions em ambientes efêmeros.

## Preparação segura do ambiente de teste

1. Salve um backup restaurável do banco **de testes** (nunca execute migrações de desenvolvimento diretamente no banco de produção).
2. Prepare um MySQL isolado, criando as tabelas pelo `database/scripts/criacaotables.sql` e os dados iniciais via `database/scripts/insertstables.sql`, ou restaure uma cópia apropriada.
3. **Somente se ainda não executadas**, aplique as migrações seguintes em ordem (a maioria utiliza `ALTER TABLE` e **não é idempotente**):
   - `database/migrations/005_notificacao_leitura.sql`
   - `database/migrations/006_foto_local.sql`
   - `database/migrations/007_bloqueio_usuario.sql`
   - `database/migrations/008_fila_espera.sql`
   - `database/migrations/009_editar_times.sql`
   - `database/migrations/010_esporte_moderacao.sql`
   - `database/migrations/011_duracao_eventos.sql`
   - `database/migrations/012_confirmacao_esportes_locais.sql`
4. No XAMPP, configure variáveis de conexão `ZUBBO_DB_*`, rode `composer install` e habilite PHP com extensões `pdo_mysql`, `mbstring`, `fileinfo` e `openssl`.
5. Para **cadastro sem e-mail real apenas em ambiente isolado**, configure `ZUBBO_ENV=local` ou `ZUBBO_ENV=demo` **e** `ZUBBO_DEMO_MODE=true`. Um serviço marcado `production`, `prod` ou sem identificação de ambiente não revela códigos, mesmo que a flag demo seja definida.
6. Para testar o envio real, mantenha `ZUBBO_DEMO_MODE=false`, configure `ZUBBO_MAIL_PROVIDER=resend`, `ZUBBO_RESEND_API_KEY` e `ZUBBO_MAIL_FROM` com remetente verificado. No modo de teste padrão do Resend, destinatários externos não autorizados podem receber HTTP 403; só configurar o PHP não remove restrições do provedor. SMTP é alternativa.
7. Verifique o ciclo completo cadastro → receber ou visualizar código autorizado → digitar código → escolher esportes → login; tente código incorreto, expirado, reenvio antes/depois de 60 s, conta suspensa, troca de senha e e-mail.
8. Teste em smartphone e desktop os dois temas, bloqueio, grupos, fotos, mapa e ações administrativas antes de considerar merge.

## Checklist técnico após a revisão

- [x] **Sessões:** middleware comum nas rotas antigas de chat, pesquisa, painel, perfil e configurações; validação centralizada em `config/database.php` de conta ativa e assinatura das credenciais; APIs retornam HTTP 401 ao revogar sessão.
- [x] **Código de e-mail:** cadastro usa tamanho consistente com banco; tela tem reenvio com 60 s de intervalo, limite, expiração de 10 min e tratamento de erros; troca de e-mail tem reenvio com controle equivalente; modo demo depende também do ambiente.
- [x] **Notificações de grupo:** abrir notificação de mensagem redireciona para conversa de grupo, em vez de criar uma conversa privada.
- [x] **JavaScript cadastro:** removidos listeners de dropdown antigo com variáveis inexistentes.
- [x] **Conversa privada:** criação transacional com bloqueio ordenado de usuários reduz duplicações concorrentes.
- [x] **Usuários inativos:** login/sessões antigas são bloqueados; perfis privados e envio de mensagens para conta inativa são restringidos; administrador ainda pode consultar registros para moderação.
- [x] **Fila de espera:** contagem inclui integrantes legados, controle de lotação e preferência da fila, promoção quando a vaga é liberada para solicitação pendente; evita promover fila em evento encerrado. Possui teste de promoção no MySQL do CI.
- [x] **Agenda interna de locais:** transação bloqueia registro do local e verifica sobreposição entre eventos de 60/90/120 minutos. Teste automatizado usa duas conexões MySQL para validar bloqueio da linha. **Não são reservas oficiais do local.**
- [x] **Esportes sugeridos:** administrador registra foto/regras/jogadores/quantidade de times; pode selecionar locais cuja aceitação foi verificada manualmente, com referência do contato; para modalidades que exigem confirmação, novos eventos só podem usar locais aprovados no cadastro administrativo. **Não há consulta externa automática**.
- [x] **Notificações lidas:** data de leitura gravada e limpeza oportunista após 20 minutos; job independente `jobs/limpar-notificacoes.php` criado para execução CLI periódica. **Precisa agendar cron/Task Scheduler** para cumprir a cadência mesmo sem usuários abrirem o app.
- [~] **Alertas no navegador:** opt-in e alerta de novos itens enquanto a página do Zubbo continua aberta. **Push verdadeiro após fechar o navegador NÃO está implementado**: exige infraestrutura de Web Push, service worker e configuração segura das chaves VAPID.
- [~] **Tema escuro:** revisados contraste nos eventos e fundo diagonal da faixa de placar; **precisa de inspeção real de todas as telas em navegadores desktop/mobile**, inclusive triângulo relatado pela equipe.
- [x] **Permissões:** mapa solicita localização quando o usuário clica no controle nativo. Upload abre seletor de arquivos do navegador sem exigir permissão irrestrita de armazenamento.
- [x] **Timezone:** PHP e sessão MySQL configurados para horário de São Paulo.
- [ ] **Limpeza de registros de teste:** não executar sem confirmação dos IDs e backup; operação deliberadamente mantida pendente.
- [ ] **Monetização:** deliberadamente fora do escopo desta revisão.

## Agendamento de notificações vencidas

Para limpeza que independe da abertura do aplicativo, execute no servidor onde o banco é acessível, por exemplo a cada minuto via cron (ajuste caminho para seu ambiente):
```cron
* * * * * /usr/bin/php /caminho/para/-TCC-/jobs/limpar-notificacoes.php
```
O script recusa chamadas HTTP e depende de `ZUBBO_DB_*` no ambiente do processo. **Não foi agendado ou executado no Railway.**

## Pendências de integração externa e limitações

- **Web Push real:** precisa de serviço VAPID/HTTPS e assinaturas do navegador, não é equivalente a notificações de página aberta.
- **Confirmação oficial de poliesportivos:** cadastrada manualmente por administrador com referência; exige procedimento de contato real com o responsável pelo espaço.
- **Mapa:** exige conexão e token Mapbox válido, e geocodificação de endereços pode falhar.
- **Fotos no Railway:** `public/uploads` usa disco local, que pode ser efêmero. Para persistência depois de redeploy, contratar armazenamento persistente e adaptar uploads.
- **Envio de e-mail:** sem domínio/remetente verificado no provedor, o erro 403 continuará. Não guardar tokens SMTP/Resend no Git.
- **XAMPP:** acesso direto pelo Apache não utiliza o `router.php` do Railway; revisar bloqueio de diretórios internos ao hospedar fora do localhost.
- **Testes CI** não reproduzem rede externa, navegadores, notificações push nem XAMPP/MariaDB. Revisar manualmente antes de atualizar a produção.
