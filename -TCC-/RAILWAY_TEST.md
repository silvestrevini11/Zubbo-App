# Teste de deploy no Railway

Esta branch foi preparada para um teste isolado do Zubbo no Railway.

## Serviço PHP

Configure o serviço conectado ao repositório com:

- Branch: `teste-railway`
- Root Directory: `/-TCC-`
- Build Command: automático
- Start Command: `php -S 0.0.0.0:$PORT router.php`
- Healthcheck Path: `/health.php`

## Variáveis da aplicação

Defina:

```text
ZUBBO_BASE_PATH=/
ZUBBO_BASE_URL=https://SEU-DOMINIO.up.railway.app
RAILPACK_PHP_EXTENSIONS=pdo_mysql
```

Para o MySQL do Railway, crie referências:

```text
ZUBBO_DB_HOST=${{MySQL.MYSQLHOST}}
ZUBBO_DB_PORT=${{MySQL.MYSQLPORT}}
ZUBBO_DB_USER=${{MySQL.MYSQLUSER}}
ZUBBO_DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
ZUBBO_DB_NAME=${{MySQL.MYSQLDATABASE}}
```

## Banco novo

Os scripts desta branch não criam nem selecionam um banco fixo. Execute no banco fornecido pelo Railway, nesta ordem:

1. `database/scripts/criacaotables.sql`
2. `database/scripts/insertstables.sql`

## URLs de teste

- `/` redireciona para a página inicial.
- `/health.php` confirma que o runtime PHP iniciou.
- `/public/index.php` abre a entrada do Zubbo.

## Observação sobre uploads

Fotos em `public/uploads` servem para teste, mas o filesystem do container não deve ser tratado como armazenamento persistente. Para produção, use um volume ou armazenamento externo.


## E-mail via Resend (recomendado no Railway)

Esta branch usa a API HTTPS do Resend por padrão para cadastro e recuperação de senha.

Adicione no serviço `Zubbo-App`:

```text
ZUBBO_MAIL_PROVIDER=resend
ZUBBO_RESEND_API_KEY=SUA_CHAVE_DA_RESEND
ZUBBO_MAIL_FROM=Zubbo <onboarding@resend.dev>
```

Para um domínio próprio verificado no Resend, substitua `ZUBBO_MAIL_FROM` pelo remetente validado.

O SMTP continua disponível apenas como fallback usando `ZUBBO_MAIL_PROVIDER=smtp`. O código agora possui timeout para evitar requisições presas.


## Modo de demonstração

Para apresentações e testes sem domínio verificado no Resend:

```text
ZUBBO_DEMO_MODE=true
```

Nesse modo o código de verificação aparece na própria tela e nenhum e-mail de cadastro é enviado.

Em produção real, mantenha:

```text
ZUBBO_DEMO_MODE=false
```

Nunca deixe o modo de demonstração ativado em produção.


## Proteção de arquivos e denúncias de locais

**Obrigatório na demo:** altere no Railway o Start Command para:

```sh
php -S 0.0.0.0:$PORT router.php
```

O arquivo `router.php` impede acesso direto à pasta `database`, `config`, `vendor`, e arquivos internos.
O Start Command antigo **não ativa essa proteção**.

Após o deploy, execute **uma vez no MySQL da demo** a migração
`database/migrations/004_denuncia_local.sql` para habilitar denúncias vinculadas a locais
e a exibição do local no painel administrativo. Não execute a migração em produção congelada.

Confirme na demo:
- `/health.php` responde 200 com `{"status":"ok"}`.
- `/database/scripts/criacaotables.sql` responde 404.
- `/config/database.php` responde 404.
- Cadastro, login, amizade, chat, mapa e denúncias são testados manualmente com usuários de teste.

O servidor embutido do PHP serve como adaptação temporária para demonstração;
em produção pública definitiva, considere servidor web apropriado e armazenamento
persistente para fotos e sessões.
