<div align="center">

<img src="-TCC-/public/imagem/LogooZ.png" alt="Logo do Zubbo" width="110" />

# Zubbo

**Encontre. Pratique. Conecte-se.**

Plataforma para encontrar locais esportivos, organizar partidas e conhecer pessoas para praticar esportes em Diadema (SP).

</div>

## Sobre o projeto

Desenvolvido como TCC de Desenvolvimento de Sistemas da ETEC Juscelino Kubitchek, em Diadema, em 2026, por Vitor Santos, Ycaro Jesus, Kaue Mota, Pedro Nathan, Vinicius Silvestre e Mayara Santos. O projeto está em demonstração web, com interface adaptada para celulares e versão Android planejada.

## Funcionalidades

* Pesquisa de locais e eventos esportivos, com visualização no mapa.
* Criação de encontros, solicitação de vagas e organização de equipes.
* Perfil, modalidades favoritas, amizades e conversas privadas ou em grupos.
* Notificações, avisos push opcionais e denúncias para moderação.

## Documentação técnica

A aplicação usa PHP, MySQL/MariaDB, HTML, CSS e JavaScript. O Composer gerencia as dependências, o Mapbox fornece os mapas, Resend ou PHPMailer realiza o envio de mensagens por correio eletrônico e Minishlink realiza o envio de Web Push.

O código fica em `-TCC-`: `app/` reúne páginas, serviços e autenticação; `config/` contém as configurações; `database/` guarda scripts e migrations; `public/` contém recursos visuais e uploads; `tests/` reúne verificações automatizadas.

### Requisitos e instalação local

Use PHP 8.4, Composer, Git, MySQL 8.4 e as extensões `pdo_mysql`, `curl`, `mbstring`, `openssl` e `fileinfo`, além das exigidas pelo Composer. MariaDB pode servir ao desenvolvimento local, mas não substitui a validação no MySQL 8.4 da hospedagem.

1. Faça um fork e clone o repositório no XAMPP, substituindo `SEU_USUARIO`:

   ```bat
   cd C:\xampp\htdocs
   git clone https://github.com/SEU_USUARIO/Zubbo-App.git
   cd Zubbo-App\-TCC-
   composer install
   composer validate --no-check-publish
   composer check-platform-reqs
   ```

2. Inicie o MySQL. No phpMyAdmin, crie e selecione um banco vazio chamado `app_zubbo`, com charset `utf8mb4`.
3. Importe primeiro [criacaotables.sql](./-TCC-/database/scripts/criacaotables.sql) e depois [insertstables.sql](./-TCC-/database/scripts/insertstables.sql). Eles não criam nem selecionam o banco automaticamente.
4. Configure o ambiente do Apache, inicie o serviço e abra [Zubbo local](http://localhost/Zubbo-App/-TCC-/).

Para desenvolver com o servidor embutido, execute na pasta `-TCC-`, no Prompt de Comando:

```bat
set ZUBBO_BASE_PATH=/
set ZUBBO_BASE_URL=http://127.0.0.1:8080
php -S 127.0.0.1:8080 router.php
```

### Configuração do ambiente

As configurações são lidas com `getenv()`. Criar um arquivo `.env` sozinho não carrega suas variáveis. No XAMPP, configure o Apache com `SetEnv` e reinicie o serviço; no servidor embutido, defina as variáveis no terminal que inicia o PHP.

* Banco: `ZUBBO_DB_HOST`, `ZUBBO_DB_PORT`, `ZUBBO_DB_NAME`, `ZUBBO_DB_USER` e `ZUBBO_DB_PASSWORD`. Os padrões locais são `localhost`, `3306`, `app_zubbo`, `root` e senha vazia; ajuste conforme sua instalação.
* Endereço no XAMPP: `ZUBBO_BASE_URL=http://localhost/Zubbo-App/-TCC-` e `ZUBBO_BASE_PATH=/Zubbo-App/-TCC-`.
* Mapa: `ZUBBO_MAPBOX_PUBLIC_TOKEN`, com um token público iniciado por `pk.`. Os estilos opcionais são `ZUBBO_MAPBOX_STYLE_LIGHT` e `ZUBBO_MAPBOX_STYLE_DARK`. Sem token aceito, o painel exibe “Mapa temporariamente indisponível”.
* Correio eletrônico: `ZUBBO_MAIL_PROVIDER=resend`, `ZUBBO_RESEND_API_KEY` e `ZUBBO_MAIL_FROM`. Para SMTP, use `ZUBBO_MAIL_PROVIDER=smtp` com `ZUBBO_SMTP_HOST`, `ZUBBO_SMTP_PORT`, `ZUBBO_SMTP_USER`, `ZUBBO_SMTP_PASSWORD` e `ZUBBO_SMTP_ENCRYPTION`.
* Demonstração: `ZUBBO_ENV=demo` junto com `ZUBBO_DEMO_MODE=true` permite mostrar o código de confirmação na tela. Em produção, mantenha o modo de demonstração desativado.

### Banco e administrador

Instalações novas usam somente os dois scripts indicados acima. Para atualizar uma base existente, faça backup e siga as cinco migrations consolidadas do [guia do banco](./-TCC-/database/README.md). Os scripts em `database/archive/` são históricos.

Para promover uma conta ativa a administrador, execute na pasta `-TCC-` e informe o endereço da conta:

```sh
php database/tools/create-admin.php
```

### Métodos de segurança utilizados

* Hash de senhas com `password_hash()` e validação com `password_verify()`.
* Sessões com renovação do identificador após login, cookies `HttpOnly`, `SameSite=Lax` e `Secure` quando HTTPS está ativo.
* Invalidação de sessões para contas indisponíveis ou com credenciais alteradas, com autorização própria para funções administrativas.
* Tokens CSRF e validação de origem em requisições sensíveis.
* Limitação de tentativas persistida no servidor para reduzir abusos.
* Consultas parametrizadas com PDO e preparação nativa.
* Escape de conteúdo HTML e cabeçalhos como Content Security Policy, proteção contra enquadramento e detecção indevida de tipos de arquivo.
* Validação de tipo e tamanho de uploads, com bloqueio de execução de PHP nessa pasta.
* Recuperação de senha com token de uso único e validade limitada.
* Configuração de credenciais pelo ambiente, bloqueio de arquivos internos pelo roteador e pelo Apache e logs estruturados com seleção de campos permitidos.
* Inscrições push autenticadas, proteção CSRF e validação dos destinos de envio para reduzir o risco de SSRF.

### Notificações push

Os avisos são opcionais e abrangem mensagens, amizades e aprovação em eventos. Após instalar as dependências, execute `php jobs/gerar-vapid.php` uma vez por ambiente e configure `ZUBBO_VAPID_PUBLIC_KEY`, `ZUBBO_VAPID_PRIVATE_KEY` e `ZUBBO_VAPID_SUBJECT`. Mantenha as chaves estáveis: sua troca exige nova inscrição dos dispositivos.

O recurso usa `sw.js`, a tabela `PushSubscription` e HTTPS ou localhost. O usuário ativa os avisos em Perfil → Configurações → Alertas de mensagens. A estrutura de uma instalação nova já inclui a tabela; para bases antigas, siga o guia do banco. O envio ocorre após a gravação do evento, sem fila persistente de reenvio.

### Verificação e logs

Execute os comandos na pasta `-TCC-`:

```sh
php tests/security-smoke.php
php tests/map-config-smoke.php
php tests/audit-migrations.php
php tests/audit-migrations.php --strict
```

A auditoria de migrations consulta o esquema sem modificar dados e usa a configuração do terminal. Os cinco domínios consolidados indicam compatibilidade observada, sem comprovar quais SQL foram executados. Com `--strict`, divergências retornam código `1`; falhas de execução retornam código diferente de zero. Sem a opção, o resultado permanece diagnóstico.

`tests/db-smoke.php` valida instalação nova e exige banco descartável vazio, nome `zubbo_ci` ou `zubbo_test_*` e `ZUBBO_TEST_ALLOW_SCHEMA=1`. A suíte separada `tests/db-upgrade.php` cobre bases legadas, parcialmente atualizadas, atuais e conflitos; consulte os requisitos e comandos no [guia do banco](./-TCC-/database/README.md). Fixtures nunca devem ser executadas no banco publicado. Complete a validação de catálogo de uma atualização com migrations seguidas de inserts; as modalidades por tipo de ginásio e quadra são aplicadas pelos inserts.

Os eventos estruturados são enviados por `config/logger.php` ao registro de erros do PHP. No XAMPP, consulte `C:/xampp/apache/logs/error.log`; na hospedagem, consulte os logs do serviço. `/health.php` confirma apenas a inicialização do PHP, sem testar o banco. Valide também autenticação, mapa, cadastro e notificações com contas de teste.

Novos logs locais devem ficar em `.local/logs/`. Dependências em `vendor/`, backups, segredos e dados locais não devem entrar no Git; use `composer install` a partir do lock para reconstruir as dependências.

### Hospedagem

No Railway, use PHP 8.4, `/-TCC-` como diretório raiz, `php -S 0.0.0.0:$PORT router.php` como comando de demonstração e `/health.php` como verificação de saúde. Configure `ZUBBO_BASE_PATH=/`, `ZUBBO_BASE_URL` com a origem HTTPS confirmada e as extensões exigidas pela aplicação e pelo Composer, incluindo `pdo_mysql` e `fileinfo`. Confira no runtime com `composer check-platform-reqs` e o verificador de ambiente.

O arquivo `Dockerfile.demo02` é uma opção de build que deve ser selecionada explicitamente apenas no novo serviço `demo.02`, com raiz `/-TCC-`. Ele define PHP 8.4, extensões e instalação pelo Composer; o nome específico evita ativação automática nos serviços existentes. A construção da imagem ainda depende de validação em ambiente com Docker.

Vincule as variáveis `ZUBBO_DB_HOST`, `ZUBBO_DB_PORT`, `ZUBBO_DB_NAME`, `ZUBBO_DB_USER` e `ZUBBO_DB_PASSWORD` às variáveis correspondentes do serviço MySQL. Configure também mapa, correio eletrônico e VAPID. Para disponibilização definitiva, use servidor web apropriado e armazenamento persistente para uploads e sessões.

A publicação de `demo.02` usa a branch `teste-alteracoes`, MySQL 8.4 e volume próprios, conforme o [plano](./-TCC-/docs/PLANO_DEMO_02.md) e a [matriz de ambiente](./-TCC-/docs/AMBIENTE_DEMO_02.md). Confira autodeploys existentes antes do push e exija workflows aprovados para o mesmo SHA implantado. O [relatório de validação e publicação](./-TCC-/docs/RELATORIO_TESTES_2026-10-25.md) distingue resultados históricos, alterações locais e pendências. Uploads continuam sujeitos ao armazenamento efêmero até receberem persistência própria.

## Dúvidas e soluções

Consulte [Dúvidas e soluções](./README_DUVIDAS_E_SOLUCOES.md) para Forbidden, falha de conexão com o banco e diagnóstico da instalação.
