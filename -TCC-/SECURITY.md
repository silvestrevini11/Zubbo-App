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

O Zubbo utiliza **login único**. A senha é validada somente pela conta da tabela `Usuario`.

A tabela `Administrador` funciona como registro de permissão: se o e-mail autenticado também estiver cadastrado como administrador ativo, o login comum redireciona diretamente para o painel administrativo.

Para promover uma conta existente:

`php database/tools/create-admin.php`

O script pede apenas o e-mail de uma conta já cadastrada no Zubbo. O campo legado `senha_adm` é preenchido com um hash aleatório inutilizável e não participa mais da autenticação.

As páginas administrativas revalidam a permissão no servidor a cada acesso, portanto conhecer a URL do painel não concede acesso.

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


## Estrutura do projeto

- app/middleware: autenticação e regras de acesso compartilhadas;
- app/services: regras reutilizáveis de domínio, como administradores e locais;
- app/views: somente páginas e componentes de interface;
- database/schema: estrutura final do banco para instalações novas;
- database/migrations: alterações incrementais para bancos já existentes;
- database/seeds: dados iniciais idempotentes;
- database/tools: utilitários de manutenção;
- database/queries: consultas auxiliares de desenvolvimento.

Para bancos existentes, aplique as migrations em ordem. A migration de locais consolida registros repetidos e cria uma restrição UNIQUE em nome + endereço.
