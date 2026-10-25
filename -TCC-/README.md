# Zubbo — Instalação e configuração

Guia para executar e adaptar o projeto. Veja os recursos e a apresentação no [README principal](../README.md).

## Requisitos

- PHP 8.2 ou superior (PHP 8.4 usado nas verificações automatizadas).
- Extensões PDO MySQL, curl, mbstring, openssl e fileinfo, além das exigidas pelo Composer.
- MySQL/MariaDB, Composer e Git.
- XAMPP para execução local com Apache, ou servidor embutido do PHP para desenvolvimento.

## Instalação local

1. Faça um fork no GitHub e clone seu repositório. No terminal do Windows, substitua `SEU_USUARIO`:

   ```bat
   cd C:\xampp\htdocs
   git clone https://github.com/SEU_USUARIO/Zubbo-App.git
   cd Zubbo-App\-TCC-
   composer install
   ```

   Para reproduzir a versão de demonstração documentada, use a branch `teste-railway` antes de instalar as dependências.

2. Inicie o MySQL. No phpMyAdmin, crie e selecione um banco vazio chamado `app_zubbo`, com charset `utf8mb4`, e importe nesta ordem:

   - [database/scripts/criacaotables.sql](database/scripts/criacaotables.sql)
   - [database/scripts/insertstables.sql](database/scripts/insertstables.sql)

3. Configure as variáveis de ambiente abaixo no Apache ou no terminal que executará o PHP. O banco local usa por padrão `localhost:3306`, banco `app_zubbo`, usuário `root` e senha vazia.

4. Inicie o Apache e acesse [Zubbo local](http://localhost/Zubbo-App/-TCC-/public/index.php).

Para usar o servidor embutido, execute na pasta `-TCC-`, pelo Prompt de Comando do Windows:

```bat
set ZUBBO_BASE_PATH=/
set ZUBBO_BASE_URL=http://127.0.0.1:8080
php -S 127.0.0.1:8080 router.php
```

Acesse [http://127.0.0.1:8080/](http://127.0.0.1:8080/). Esse servidor é destinado ao desenvolvimento local.

## Configuração

Defina as credenciais no ambiente, sem adicioná-las ao Git.

| Variáveis | Uso |
|---|---|
| `ZUBBO_DB_HOST`, `ZUBBO_DB_PORT`, `ZUBBO_DB_NAME` | Servidor e nome do banco |
| `ZUBBO_DB_USER`, `ZUBBO_DB_PASSWORD` | Credenciais do MySQL |
| `ZUBBO_BASE_URL`, `ZUBBO_BASE_PATH` | URL da aplicação e prefixo das rotas |
| `ZUBBO_MAIL_PROVIDER` | Provedor de e-mail: `resend` ou `smtp` |
| `ZUBBO_RESEND_API_KEY`, `ZUBBO_MAIL_FROM` | Chave do Resend e remetente autorizado |
| `ZUBBO_SMTP_HOST`, `ZUBBO_SMTP_PORT`, `ZUBBO_SMTP_USER`, `ZUBBO_SMTP_PASSWORD`, `ZUBBO_SMTP_ENCRYPTION` | Configuração alternativa de SMTP |
| `ZUBBO_ENV`, `ZUBBO_DEMO_MODE` | Ambiente e modo de demonstração |

No XAMPP, use `ZUBBO_BASE_URL=http://localhost/Zubbo-App/-TCC-` e `ZUBBO_BASE_PATH=/Zubbo-App/-TCC-`.

Configure o envio de e-mail para confirmar cadastros e recuperar senhas. Em uma demonstração isolada, `ZUBBO_ENV=demo` junto com `ZUBBO_DEMO_MODE=true` permite exibir o código de confirmação na tela. Mantenha esse modo desativado em produção.

## Banco e administrador

Em instalações novas, os dois scripts de instalação são suficientes. Para atualizar um banco existente, faça backup e confira quais alterações de `database/migrations/` e `database/scripts/` ainda são necessárias antes de aplicá-las.

Para criar um administrador, cadastre e ative uma conta no aplicativo. Depois, execute na pasta `-TCC-` e informe o e-mail dessa conta:

```sh
php database/tools/create-admin.php
```

## Estrutura do código

| Pasta | Conteúdo |
|---|---|
| `app/` | Páginas, autenticação e regras da aplicação |
| `config/` | Banco, e-mail, segurança e logs |
| `database/` | Scripts SQL, migrações e modelagem |
| `public/` | CSS, JavaScript, imagens e uploads |
| `tests/` | Verificações de segurança e integração |
| `docs/` | Documentação complementar |

## Testes e deploy

Execute a verificação de segurança na pasta `-TCC-`:

```sh
php tests/security-smoke.php
```

O teste `tests/db-smoke.php` altera dados e deve ser executado somente em banco descartável. Consulte [logs e testes](docs/OBSERVABILIDADE_E_TESTES.md) para mais detalhes.

Para hospedar a aplicação, siga o [guia do Railway](RAILWAY_TEST.md). Antes de disponibilizá-la ao público, revise a [documentação de segurança](SECURITY.md), a política de privacidade e o armazenamento persistente de uploads, sessões e limites de requisições.

Para configurar notificações push, consulte [WEB_PUSH.md](WEB_PUSH.md).


O mapa requer `ZUBBO_MAPBOX_PUBLIC_TOKEN` configurado no servidor. Consulte [segurança e configuração do mapa](SECURITY.md).
