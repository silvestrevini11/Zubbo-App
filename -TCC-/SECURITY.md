# Segurança do Zubbo

## Credenciais

Nunca salve senhas, tokens ou credenciais SMTP no Git.

Variáveis usadas pela aplicação:

- ZUBBO_SMTP_USER
- ZUBBO_SMTP_PASSWORD
- ZUBBO_SMTP_HOST (opcional)
- ZUBBO_SMTP_PORT (opcional)
- ZUBBO_SMTP_ENCRYPTION (opcional: auto, starttls, smtps ou none)
- ZUBBO_MAIL_FROM (opcional)
- ZUBBO_BASE_URL (opcional; URL absoluta usada em e-mails)
- ZUBBO_BASE_PATH (opcional; caminho web quando a detecção automática não for suficiente)
- ZUBBO_DB_HOST, ZUBBO_DB_USER, ZUBBO_DB_PASSWORD, ZUBBO_DB_PORT e ZUBBO_DB_NAME (opcionais)

Credenciais que já tenham aparecido no histórico do Git devem ser revogadas e substituídas.

## Administrador

O administrador usa a própria conta de Usuario. Para promover uma conta ativa:

php database/tools/create-admin.php

Depois execute a migration database/migrations/002_admin_user_relation.sql em bancos já existentes.

## Banco existente

Execute também database/migrations/003_deduplicate_locations.sql para consolidar locais repetidos e criar a restrição única de nome + endereço.

## Proteções

- cookies HttpOnly e SameSite=Lax;
- Secure quando HTTPS estiver ativo;
- headers de segurança;
- CSRF explícito nos fluxos sensíveis;
- bloqueio de POSTs cross-site;
- rate limit persistido no servidor, independente do cookie de sessão;
- prepared statements nativos com PDO;
- usuários suspensos/banidos têm a sessão invalidada;
- SMTP configurado fora do código;
- uploads com validação de tipo/tamanho e sem execução de PHP;
- recuperação de senha com token de uso único e relógio UTC do banco.
