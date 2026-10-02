# Segurança do Zubbo

## Credenciais e variáveis de ambiente

Nunca armazene senhas, tokens ou credenciais SMTP no Git.

Variáveis usadas pela aplicação:

- `ZUBBO_SMTP_USER`
- `ZUBBO_SMTP_PASSWORD`
- `ZUBBO_SMTP_HOST` (opcional)
- `ZUBBO_SMTP_PORT` (opcional)
- `ZUBBO_MAIL_FROM` (opcional)
- `ZUBBO_BASE_URL` (ex.: `http://localhost/-TCC-`)
- `ZUBBO_DB_HOST`, `ZUBBO_DB_USER`, `ZUBBO_DB_PASSWORD`, `ZUBBO_DB_PORT`, `ZUBBO_DB_NAME` (opcionais)

Qualquer credencial que já tenha sido versionada precisa ser revogada e substituída. Remover o valor do arquivo atual não remove o segredo do histórico do Git.

## Administrador

O seed SQL não cria mais uma conta com senha padrão. Crie a primeira conta administrativa pela linha de comando:

`php database/scripts/criar-admin.php`

A senha é armazenada com `password_hash()`.

## Proteções

- cookies de sessão `HttpOnly`, `SameSite=Lax` e `Secure` sob HTTPS;
- `session.use_strict_mode` e regeneração do ID após login;
- bloqueio de requisições POST cross-site e CSRF explícito nas ações administrativas já existentes;
- prepared statements nativos com PDO e `utf8mb4`;
- contas suspensas/banidas perdem acesso mesmo com sessão já aberta;
- limitação básica de tentativas de login e recuperação;
- uploads de imagem limitados e diretório sem execução de PHP;
- headers de segurança, incluindo CSP e proteção contra framing;
- tokens de recuperação de senha não são exibidos na interface.
