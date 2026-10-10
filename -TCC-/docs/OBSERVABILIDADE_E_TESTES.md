# Observabilidade e testes de seguranca - Zubbo

Escopo: branch `teste-railway`, nao substitui verificacao com contas reais.

## Logs de aplicacao
`config/logger.php` expoe:
- `zubbo_log(level, event, context)`: envia uma linha JSON ao `error_log`, capturada no Railway.
- `zubbo_log_encode(level, event, context)`: gera o JSON sem publicar a linha, para testes.

Campos padrao: `timestamp` (UTC), `level`, `event`, `request_id`, `environment`, `route` sem query-string, `context`.
`context` usa whitelist para aceitar apenas IDs numericos, codigos seguros de erro e acao.
Nunca inserir ou tentar capturar senhas, e-mails, tokens, cookies, telefone,
texto de mensagens, descricao de denuncia, nome ou data de nascimento.
O request-id é criado uma vez por requisicao que registrar log.
Recomenda-se `ZUBBO_ENV=demo` na demo e `ZUBBO_ENV=production` futuramente.

Eventos atualmente integrados:
`auth.login_success`, `auth.login_denied`, `auth.login_rate_limited`,
`auth.session_revoked`, `account.email_change_requested`,
`account.email_confirmation_denied`, `account.email_changed`,
`account.password_changed`, `account.deactivated`, `account.*_failed`,
`report.created`, `report.create_failed`, `admin.access_denied`,
`admin.action_logged`, `db.connection_failed`.
O código ainda contém `error_log` legado em pontos não migrados:
antes de publicação, auditar esses logs para dados pessoais.

## Como acompanhar
No Railway, abrir serviço Zubbo App > Logs e pesquisar `"event":"auth.login_denied"`
ou `"level":"error"`. Associar por `request_id`.
Não compartilhar prints com credenciais nem dados privados.
As ações administrativas persistem na tabela `Acao_Administrativa`.
Logs de aplicação não substituem este histórico.

## Alertas futuros
- HTTP 5xx aumentado por 5 minutos;
- falhas de conexão ou migração no MySQL;
- repetição de `auth.login_denied` ou `auth.login_rate_limited`;
- latência p95 das páginas ou endpoints superior ao objetivo definido;
- falhas de confirmação de e-mail ou recuperação de senha;
- taxa de falha de operações de vagas e mensagens.

A emissão de alertas ainda exige configurar uma ferramenta de monitoramento
e capturar métricas. Não há alertas automáticos implantados pelo logger.

## CI
1. PHP Syntax Check: `find ... php -l`.
2. Railway Readiness: Composer, rotas públicas/privadas, respostas HTTP,
   CSRF e teste isolado `php tests/security-smoke.php`.
3. Demo MySQL Integration: MySQL 8.4 efêmero, importação do esquema e seed,
   AUTO_INCREMENT, regras de amizade/evento, chat sob `ONLY_FULL_GROUP_BY`,
   privilégios administrativos por ID de usuário.

Verificar em: https://github.com/silvestrevini11/Zubbo-App/actions

## Testes manuais ainda necessários
- Conta antiga: nova versão exige novo login após deploy para criar a
  impressão digital da credencial; a alteração de senha encerra sessões antigas.
- Senha incorreta para alterar e-mail e senha; alteração de e-mail com código expirado,
  e-mail duplicado e modo demo.
- Acesso administrativo com registro `Administrador.id_user` vinculado:
  contas legadas com id_user nulo precisam de vinculação administrativa auditada.
- Desativação: dados cadastrais anonimizados e histórico de denúncia preservado.
- Teste de retenção, backup e restauração em MySQL de testes.
- Chat e eventos com 2 contas simultâneas, mapa em celular e retorno offline.
- Testes de carga autenticados, fora da production congelada.

## Antes de produzir APK público
- `ZUBBO_DEMO_MODE` **false** e emissor de e-mail verificado em domínio próprio.
- Domínio HTTPS fixo e privado, sem chaves nem senhas no aplicativo Android.
- Banco sem Public Networking quando não utilizado por DBeaver.
- Volume/armazenamento persistente para fotos e backup periódico com restauração testada.
- Política de privacidade, controles de retenção e canal de solicitações de titulares,
  revisados e aplicáveis.
- Trocar servidor PHP de desenvolvimento por execução adequada à produção.
- Para Android WebView: permitir apenas seu host HTTPS, tratar cookies, navegação,
  permissões e downloads, e bloquear `file://` e conteúdo misto.
- Testar sessões, login, chamadas ao mapa, upload e navegação no aparelho.
