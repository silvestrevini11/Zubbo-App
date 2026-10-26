# Ambiente Railway demo.02

Execute os comandos abaixo a partir da raiz de `-TCC-`. Este documento descreve a configuração pretendida; a publicação e o domínio devem ser confirmados no painel e no relatório final.

Na retomada de 25/10/2026, a base commitada é `3fd7a49`, na branch `teste-alteracoes`. Este documento e os verificadores de ambiente existiam somente no checkout e ainda precisavam de revisão. A configuração desta tabela não constitui evidência de provisionamento. Consulte o [relatório único](RELATORIO_TESTES_2026-10-25.md) para resultados, SHA validado e pendências.

## Serviços e publicação

| Item | Configuração |
|---|---|
| Environment | `demo.02` no projeto da demo |
| Origem | `silvestrevini11/Zubbo-App`, branch `teste-alteracoes` |
| Root Directory | `/-TCC-` |
| Runtime | PHP 8.4; dependências instaladas pelo `composer.lock` |
| Build opcional | Selecionar explicitamente `Dockerfile.demo02` apenas no novo serviço, com raiz `/-TCC-`; imagem ainda não construída nesta retomada |
| Start command | `php -S 0.0.0.0:$PORT router.php` |
| Healthcheck | `/health.php` |
| Banco | Serviço MySQL 8.4 com volume próprio e credenciais novas |
| Rede da aplicação | Endereço privado do MySQL de `demo.02` |
| DBeaver | Conexão `Zubbo — demo.02`, endpoint externo/TCP Proxy do mesmo MySQL |
| Autodeploy | Desabilitado durante a preparação; habilitar com Wait for CI após conferir workflows do SHA escolhido e isolar os deploys existentes |

O DBeaver administra o servidor MySQL; ele não substitui o serviço de banco. Não reutilize o MySQL da demo anterior. Antes de inicializar, confirme projeto, environment, identidade do serviço, banco e volume novos; registre o backup inicial em armazenamento privado e valide o procedimento de restauração em banco descartável. Instale o banco novo somente com `database/scripts/criacaotables.sql` e `database/scripts/insertstables.sql`. As migrations atendem bancos existentes. Nunca execute testes de fixtures no serviço publicado.

## Matriz de variáveis

| Variável | Valor/origem em demo.02 | Validação |
|---|---|---|
| `PORT` | Gerenciada pelo Railway | Não fixar/copiar porta da demo |
| `ZUBBO_ENV` | `demo` | O código reconhece `demo`, não `demo.02` |
| `ZUBBO_DEMO_MODE` | `true` | Exibe códigos de confirmação na demonstração |
| `ZUBBO_BASE_PATH` | `/` | Rotas públicas a partir da raiz |
| `ZUBBO_BASE_URL` | Nova origem HTTPS confirmada | Sem subpasta, credenciais ou parâmetros |
| `ZUBBO_DB_HOST` | Referência `MYSQLHOST` do novo MySQL | Rede privada `*.railway.internal`; não localhost nem TCP Proxy público |
| `ZUBBO_DB_PORT` | Referência `MYSQLPORT` do novo MySQL | Porta numérica |
| `ZUBBO_DB_NAME` | Referência `MYSQLDATABASE` do novo MySQL | Nome do banco provisionado |
| `ZUBBO_DB_USER` | Referência `MYSQLUSER` do novo MySQL | Usuário próprio |
| `ZUBBO_DB_PASSWORD` | Referência `MYSQLPASSWORD` do novo MySQL | Segredo; não copiar para Git/relatório |
| `ZUBBO_MAIL_PROVIDER` | `resend` ou `smtp` | Confirmar provedor e credenciais vigentes |
| `ZUBBO_RESEND_API_KEY` | Importar segredo válido se Resend | Presença local; validade via envio autorizado |
| `ZUBBO_MAIL_FROM` | Remetente autorizado no provedor | E-mail ou `Zubbo <email>` |
| `ZUBBO_SMTP_HOST` | Importar se SMTP | Default no código: `smtp.gmail.com` |
| `ZUBBO_SMTP_PORT` | Importar se SMTP | Default: `587`; confirmar conectividade do provedor |
| `ZUBBO_SMTP_USER` | Importar se SMTP | Segredo/identificador; não imprimir |
| `ZUBBO_SMTP_PASSWORD` | Credencial vigente se SMTP | Revogar a credencial exposta anteriormente e usar substituta |
| `ZUBBO_SMTP_ENCRYPTION` | `auto`, `starttls` ou `smtps` | Default `auto`; não usar `none` na hospedagem |
| `ZUBBO_VAPID_SUBJECT` | Importar contato `mailto:` ou origem HTTPS | Identifica o responsável pelo push |
| `ZUBBO_VAPID_PUBLIC_KEY` | Importar somente par vigente e não exposto | Chave pública P-256, base64url |
| `ZUBBO_VAPID_PRIVATE_KEY` | Importar segredo do mesmo par | Nunca exibir ou versionar |
| `ZUBBO_MAPBOX_PUBLIC_TOKEN` | Token público vigente `pk.` | Configuração por ambiente; restringir ao novo domínio na conta Mapbox |
| `ZUBBO_MAPBOX_STYLE_LIGHT` | Opcional, importar estilo autorizado | Default `mapbox://styles/mapbox/streets-v12` |
| `ZUBBO_MAPBOX_STYLE_DARK` | Opcional, importar estilo autorizado | Default `mapbox://styles/mapbox/dark-v11` |
| `ZUBBO_TEST_ALLOW_SCHEMA` | Ausente | Nunca importar autorização de teste destrutivo |
| `ZUBBO_TEST_BASE_URL` | Ausente | Exclusiva dos testes locais descartáveis |

As referências Railway devem apontar para o nome real do serviço novo, por exemplo `${{MySQL.MYSQLHOST}}` se ele se chamar `MySQL`. Não cole valores literais em exemplos, comandos compartilhados ou relatórios. Use o painel para importar as variáveis e revise cada referência. O verificador não identifica sozinho o serviço/volume dono do endereço; confirme esse vínculo no painel.

Não copie automaticamente a configuração da demo antiga. Substitua qualquer credencial revogada ou exposta e importe apenas valores vigentes. Todas as variáveis `ZUBBO_TEST_*`, caminhos Windows e portas fixas de testes devem ficar ausentes no serviço publicado.

O token Mapbox já é lido de variável de ambiente em `config/map.php`; não há necessidade de inserir token no código. O verificador confere formato, mas as restrições de domínio e o funcionamento do mapa exigem validação na conta e no navegador.

Se VAPID estiver ausente, gere um par novo com `Minishlink\WebPush\VAPID::createVapidKeys()` em sessão privada e salve diretamente como segredo no Railway. Evite imprimir chaves em logs de CI ou no chat. Reutilizar um par existente mantém a validade das inscrições que pertencem àquela origem; uma origem nova requer novas inscrições no navegador.

## Verificação somente leitura

No runtime da nova aplicação, com as variáveis já carregadas:

```sh
php tests/environment-readiness.php --expected-base-url=https://DOMINIO-CONFIRMADO.up.railway.app
```

O comando exige PHP 8.4 e as extensões PDO MySQL, curl, JSON, mbstring, OpenSSL, ctype, filter, hash e fileinfo. Confere configuração de demo, origem, provedor de e-mail, chaves VAPID e correspondência criptográfica do par, token público Mapbox e destino de banco privado `*.railway.internal`. Sem `--skip-db`, executa `SELECT 1`, confere `SELECT DATABASE()` com `ZUBBO_DB_NAME` e exige MySQL 8.4 em `SELECT VERSION()`. Não carrega a conexão normal da aplicação, não cria logs/arquivos, não executa SQL de alteração e não envia e-mail/push. A saída contém nomes e resultados, sem valores ou mensagens do driver. Código de saída `0` indica aprovação das verificações executadas; `1` indica falha. Execute também `composer validate --no-check-publish`, `composer check-platform-reqs` e `php tests/audit-migrations.php --strict`. O aviso de licença ausente no Composer não é tratado como aprovação de uma licença nem como falha dos requisitos de runtime.

`--skip-db` permite conferir somente a configuração e marca conexão, identidade e versão do banco como pendentes. Isso não comprova conectividade, schema, credenciais externas de e-mail, recebimento push ou domínio autorizado no Mapbox. Rode novamente sem essa opção antes da publicação.

O build opcional `Dockerfile.demo02` instala dependências pelo lock, verifica os requisitos de plataforma e inicia o roteador PHP. A `.dockerignore` exclui arquivos locais, segredos, backups, dependências locais e uploads do contexto de build. Configure o caminho desse Dockerfile exclusivamente no serviço novo; não altere o builder dos serviços existentes. Como Docker não está disponível localmente, a imagem ainda precisa ser construída e validada antes de servir como evidência de runtime.

Após confirmar o domínio exato de `demo.02` no Railway, no PowerShell:

```powershell
./tests/railway-readonly.ps1 -BaseUrl 'https://DOMINIO-CONFIRMADO.up.railway.app' -ConfirmedDemo02Origin 'https://DOMINIO-CONFIRMADO.up.railway.app'
```

O parâmetro de confirmação adiciona somente a origem informada à lista permitida da execução. O script aceita origens HTTPS Railway, recusa caminhos/credenciais e faz GET sem seguir redirecionamentos. Verifica saúde, rotas públicas, Service Worker, manifesto e bloqueio de configuração, SQL, dependências, testes e documentação. A conexão real, schema, fluxos autenticados, e-mail, Web Push e persistência após reinício são verificações adicionais do relatório de publicação.

No DBeaver, use o host e a porta externos do novo MySQL, banco/usuário/senha próprios e o TLS fornecido pelo serviço. Confirme `SELECT DATABASE(), VERSION()` antes de qualquer alteração. Não use o TCP Proxy público na conexão privada da aplicação. Guarde backups fora do repositório, com acesso restrito; uploads da aplicação continuam sujeitos ao armazenamento efêmero até configurar persistência própria.

Após a publicação, registre o SHA implantado, domínio, logs, conectividade e persistência do banco após reinício. Homologue cadastro, sessões, grupos, eventos, fila, notificações, mapa, recebimento de e-mail e recebimento/clique de Web Push com contas sintéticas na nova origem. Sucesso do healthcheck ou da auditoria não substitui essas verificações. Se o acesso autenticado estiver indisponível, conclua o trabalho local e mantenha publicação, domínio e configuração remota como pendentes. Rollback de código não reverte migrations.
