# Teste de deploy no Railway

Esta branch foi preparada para um teste isolado do Zubbo no Railway.

## Serviço PHP

Configure o serviço conectado ao repositório com:

- Branch: `teste-railway`
- Root Directory: `/-TCC-`
- Build Command: automático
- Start Command: `php -S 0.0.0.0:$PORT`
- Healthcheck Path: `/health.php`

## Variáveis da aplicação

Defina:

```text
ZUBBO_BASE_PATH=/
ZUBBO_BASE_URL=https://SEU-DOMINIO.up.railway.app
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
