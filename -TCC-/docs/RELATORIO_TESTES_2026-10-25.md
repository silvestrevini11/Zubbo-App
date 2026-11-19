# Zubbo — validação da consolidação e publicação de demo.02

## Estado da entrega

Retomada em **25/10/2026**, na branch **`teste-alteracoes`**, a partir do commit
**`3fd7a49`** (`fix: validate inputs and consolidate database setup`). A consolidação
SQL e a documentação técnica no README principal já estavam commitadas. Não é
necessário refazer essa consolidação nem recriar os quatro guias removidos.

A integração e a validação local foram concluídas na continuação de **26/10/2026**.

Este é o relatório único da entrega. A implementação local validada foi registrada no
commit **`3cbbf62b2f3a2b318741cdc4e943523465c3c898`**. Os resultados abaixo correspondem
ao código desse commit; este fechamento acrescenta somente documentação. O SHA final
da branch ainda precisa de aprovação no CI antes de ser implantado. A publicação
exige workflows aprovados para exatamente o SHA escolhido.

| Marco | Estado verificável |
|---|---|
| Base commitada da retomada | `3fd7a49`, branch `teste-alteracoes` |
| Arquivos locais no início | Três documentos de `docs/`, `tests/environment-readiness.php` e `tests/railway-readonly.ps1` ainda não versionados |
| Dependências locais | `vendor/` retirado do índice e ignorado; arquivos físicos locais preservados; instalação pelo lock conferida |
| Implementação local commitada e validada | `3cbbf62b2f3a2b318741cdc4e943523465c3c898`; fechamento posterior restrito a este relatório |
| SHA final aprovado em CI | Pendente: publicação depende de comprovar o isolamento dos autodeploys antes do push |
| SHA implantado / URL de `demo.02` | Não confirmados |
| Banco, volume e backup inicial de `demo.02` | Não confirmados; referências privadas devem ser registradas após provisionamento |
| Acesso Railway nesta retomada | CLI, conector e autenticação não comprovados; controle do navegador interrompido por impossibilidade de verificar a URL |
| Produção, `main` e demo anterior | Fora do escopo de alterações desta entrega |

## Alterações e validação da retomada

A entrega revisa as cinco migrations e a auditoria por domínio, acrescenta a suíte
separada de upgrades e alinha CI, dependências e verificadores ao alvo PHP 8.4 / MySQL
8.4. A instalação nova usa somente criação e inserts. A atualização de catálogo usa
migrations seguidas de inserts; a regra de Futsal nos ginásios continua nos inserts.

A documentação foi alinhada ao planejamento aprovado: README principal como fonte
técnica, exemplos executados em `-TCC-`, checkpoints sem guias removidos e distinção
entre implementação, evidência histórica, validação local e publicação.

| Verificação atual | Ambiente / revisão | Resultado |
|---|---|---|
| Branch e base da retomada | Git local, `3fd7a49` | Conferidos antes das alterações |
| Links Markdown locais da documentação | `3cbbf62`, README e três documentos | 22 links locais, zero destinos ausentes |
| Sintaxe PHP e PowerShell | `3cbbf62`, PHP 8.4.27 | 128 fontes PHP fora de vendor/.local e o verificador PowerShell aprovados |
| Composer install pelo lock | PHP 8.4.27 | Aprovado; consulta ao filtro Packagist teve aviso de falha de DNS |
| Composer validate --no-check-publish / check-platform-reqs | PHP 8.4.27 | Aprovados; aviso de licença ausente permanece |
| Segurança e mapa | `3cbbf62`, PHP 8.4.27 | Aprovados |
| Biblioteca push | PHP 8.4.27, OpenSSL configurado no Windows | Aprovada após configurar OPENSSL_CONF e RANDFILE locais |
| Instalação nova | MySQL 8.0.46 isolado em loopback:13386, `zubbo_test_fresh`, PHP 8.4.27 | 44 verificações aprovadas; evidência complementar, não MySQL 8.4 |
| Upgrades e reexecução | PHP 8.4.27 / MySQL 8.0.46, `zubbo_test_upgrade` isolado | 105 verificações aprovadas: legado, parcial, atual, catálogo 8/18, fontes, vínculos, conflitos e preservação |
| Auditoria estrita | Bancos descartáveis fresh, upgrade e restore, PHP 8.4.27 / MySQL 8.0.46 | Cinco domínios, 323 verificações compatíveis em cada banco, retorno 0 |
| Códigos da auditoria CLI | Suíte de upgrades, PHP 8.4.27 / MySQL 8.0.46 | Compatível 0; divergência no diagnóstico 0; divergência estrita 1; erro de execução 2 |
| Contrato gerado das migrations | `php database/tools/migration-contract.php` | Atualizado, retorno 0; verificação incluída no CI |
| Backup e restauração sintéticos | MySQL 8.0.46; fresh → `zubbo_test_restore` | Dados restaurados com hashes idênticos e auditoria estrita aprovada; não comprova backup remoto |
| Todas as suítes SQL no alvo de hospedagem | MySQL 8.4 / PHP 8.4 | MySQL 8.4 indisponível localmente; execução no CI pendente |
| tests/http-smoke.php | PHP 8.4.27 e banco sintético na instância MySQL 8.0.46 isolada | 46 verificações, zero falhas |
| tests/router-smoke.php | PHP 8.4.27, `3cbbf62` | 35 verificações, zero falhas; documentos reais bloqueados, Service Worker e manifesto 200 |
| `tests/environment-readiness-smoke.php`, cinco cenários sintéticos --skip-db | PHP 8.4.27, sem conexão de rede | Configuração válida retorna 0; DB público, autorização destrutiva, VAPID inconsistente e origem divergente retornam 1; saída sem segredos |
| Build Dockerfile.demo02 | Docker indisponível localmente | Arquivo preparado como opção explícita do serviço novo; construção pendente |
| Ambiente sem `--skip-db` | Runtime real de `demo.02` | Pendente |
| Workflows do SHA escolhido | GitHub Actions | Pendente |
| Homologação publicada | Nova origem confirmada | Pendente |

Não converter a inclusão de um teste no workflow em resultado aprovado. Atualizar a
tabela com comando, resultado, runtime e revisão efetivamente usados. Novos logs
locais ficam em `.local/logs/` e não são versionados.

Evidências locais privadas, relativas a `-TCC-` e ignoradas pelo Git:

- `.local/logs/db-fresh-php84-mysql80.log`, `db-upgrade-php84-mysql80.log`, `http-flows-php84-mysql80.log` e `http-router-php84.log`.
- `.local/logs/audit-fresh-php84-mysql80.json`, `audit-upgrade-php84-mysql80.json`, `audit-restore-php84-mysql80.json` e `environment-smoke.log`.
- Backup exclusivamente sintético: `.local/backups/fresh-synthetic-restore.sql`; restauração comprovada em `.local/logs/restore-synthetic.log`. Nenhum backup de `demo.02` foi criado ou confirmado.
O coordenador identificou PHP **8.4.27** em `.local/php84/php.exe` e MySQL **8.0.46**
instalado. MySQL 8.4 e Docker não estão disponíveis no ambiente local identificado.
Uma execução em MySQL 8.0 ou MariaDB é evidência complementar e não satisfaz o alvo
MySQL 8.4. Sem acesso autenticado ao Railway, os autodeploys existentes não puderam
ser conferidos; push e publicação permanecem pendentes conforme o plano aprovado.

No teste de biblioteca push, a falha inicial era de configuração do OpenSSL no
Windows. A execução passou com `OPENSSL_CONF` apontando para
`.local/php84/extras/ssl/openssl.cnf` e `RANDFILE` para `.local/tmp/openssl.rnd`.
Esses caminhos são locais e não devem ser copiados ao Railway. Geração de VAPID
não comprova recebimento de Web Push. O aviso de DNS do Composer também impede
tratar aquela consulta ao filtro remoto como verificação bem-sucedida.

## Reexecutar a validação

Execute a partir da pasta **`-TCC-`**, com PHP 8.4 e dependências instaladas pelo lock:

```sh
composer install --no-interaction --prefer-dist
composer validate --no-check-publish
composer check-platform-reqs
php tests/security-smoke.php
php tests/map-config-smoke.php
php tests/push-library-smoke.php
php tests/environment-readiness-smoke.php
php database/tools/migration-contract.php
php tests/audit-migrations.php --strict
```

A auditoria usa a conexão do ambiente e realiza somente leitura. Sem `--strict`,
o diagnóstico não transforma divergências de compatibilidade em falha de CI.
Nenhuma modalidade comprova o histórico de execução dos arquivos SQL.

As suítes de instalação e upgrade têm autorizações e nomes de banco descartável
próprios, descritos no [guia do banco](../database/README.md). Configure explicitamente
a conexão antes de executá-las. O teste de instalação exige banco vazio e não deve
receber migrations durante o provisionamento. A suíte de upgrades deve testar os
estados legado, parcial, atual, duplicidades e reexecução com dados sintéticos.

`tests/http-smoke.php` grava fixtures e deve apontar somente para um runtime local
conectado a banco descartável. A homologação publicada usa contas sintéticas pela
interface, sem executar essa suíte contra `demo.02`. Não exporte autorizações
`ZUBBO_TEST_*` para o ambiente publicado.

Os comandos de diagnóstico remoto, matriz de variáveis e verificações adicionais
estão em [Ambiente demo.02](AMBIENTE_DEMO_02.md). O endereço deve ser confirmado no
Railway antes de informar `ConfirmedDemo02Origin`; não deduza domínio, branch,
serviço ou SHA a partir de respostas HTTP.

## Condições pendentes de publicação

1. Confirmar acesso autenticado ao projeto Railway e identificar serviços,
   environments e autodeploys existentes antes do push. Isolar o novo environment
   sem alterar produção ou a demo anterior por consequência.
2. Preparar `demo.02`, branch `teste-alteracoes`, raiz `/-TCC-`, PHP 8.4, MySQL 8.4
   com credenciais e volume próprios; manter autodeploy desabilitado inicialmente.
3. Confirmar identidade do banco/volume, registrar backup inicial privado e validar
   o procedimento de restauração em banco descartável. Inicializar o banco novo
   somente com criação e inserts.
4. Confirmar origem HTTPS, rede privada, variáveis `ZUBBO_DB_*`, `fileinfo`, requisitos
   Composer, credenciais vigentes de e-mail/VAPID e restrição Mapbox para a nova origem.
5. Commitar a entrega revisada, fazer push após conferir os autodeploys e exigir
   sucesso de todos os workflows do SHA escolhido. Habilitar Wait for CI e publicar
   exatamente esse SHA.
6. Confirmar SHA implantado, logs, domínio e banco; obter 200 no Service Worker e no
   manifesto, 404 nos arquivos internos existentes e aprovação do verificador de
   ambiente sem `--skip-db`.
7. Homologar cadastro, sessões, grupos, eventos, fila, notificações e mapa com contas
   sintéticas; confirmar recebimento de e-mail e de Web Push, incluindo clique no aviso.
8. Conferir DBeaver na conexão “Zubbo — demo.02” e persistência do banco após reinício.

Se o Railway estiver inacessível, concluir as etapas locais independentes e manter
os itens remotos pendentes. Falhas impedem publicação. Rollback de código não reverte
migrations. Uploads permanecem sujeitos ao armazenamento efêmero até terem persistência
própria. O procedimento completo está no [plano aprovado](PLANO_DEMO_02.md).

## Evidência histórica anterior à consolidação — não certifica esta entrega

O relatório local anterior descrevia a atualização de `e52e81d` para
`669dc72ea0b8ac7eb145689887ebdbaf522b0b92` e correções ainda não commitadas naquele
momento. Não registrava um SHA final para o conjunto de arquivos testados. Os
resultados abaixo são preservados como histórico relatado, sem reexecução implícita
nem atribuição ao commit `3fd7a49` ou ao futuro SHA de publicação.

| Ambiente histórico relatado | Configuração |
|---|---|
| XAMPP | Apache 2.4.58, PHP 8.2.12 e MariaDB 10.4.32 |
| Runtime adicional | PHP 8.4.27 TS x64 em `.local/php84` |
| Servidor embutido | `http://127.0.0.1:8080/`, banco sintético |
| Apache isolado | `http://127.0.0.1:8088/-TCC-/`, banco sintético |
| Instância de banco isolada | `127.0.0.1:3307`, datadir `.local/mysql-snapshot` |
| Bancos descartáveis históricos | `zubbo_test_20261025`, `zubbo_test_php84`, `zubbo_test_restore_20261025`, `zubbo_test_backup_original` |

O histórico informa que o PHP e as configurações globais do XAMPP não foram
substituídos. As portas, processos e arquivos acima não foram novamente confirmados
por este registro e não devem ser assumidos ativos ao reexecutar testes.

| Verificação histórica relatada | Resultado e limite |
|---|---|
| Sintaxe PHP próprio | 120 arquivos, zero falhas em PHP 8.4 |
| Segurança | 29 verificações aprovadas |
| Biblioteca push / VAPID | Carregamento e geração local em PHP 8.2 e 8.4; sem comprovar entrega |
| Composer | Validate e requisitos aprovados; aviso de licença ausente |
| Integração SQL | 56 verificações em MariaDB 10.4, PHP 8.4; não certifica MySQL 8.4 |
| HTTP, servidor embutido | 46 verificações aprovadas |
| HTTP, Apache XAMPP | 46 verificações aprovadas |
| JavaScript push / Service Worker | `node --check` aprovado |
| Guardas dos testes | Recusa de banco real, banco preenchido e URL HTTP externa |
| Mapa visual | 18 locais carregados; pesquisa Ayrton e popup do Ginásio Ayrton Senna observados |

O histórico cobriu validação de nascimento, entradas escalares indevidas no cadastro
ou grupos, sessão e CSRF, criação de grupo, vagas e conflito de horários, notificações
e busca de locais. Não homologou carga, geolocalização real, APK, dispositivos móveis,
reservas oficiais nem recebimento/clique de Web Push. A tentativa de permissão push
no navegador de testes não concluiu a inscrição. Nenhuma configuração VAPID foi
relatada como aplicada à demo.

### Banco e backups históricos

A auditoria anterior usava a numeração arquivada 002–016. Ela relatava a ausência de
`Fila_Espera_Evento` (008) e quatro dos sete ginásios sem Futsal (016) no banco local
original. Os demais campos/índices consultados estavam presentes; isso não comprovava
a execução integral dos SQL. O ensaio dessas duas atualizações ocorreu somente em
`zubbo_test_restore_20261025`, após restauração. Nada nessa observação descreve o
schema de `demo.02`. O mapeamento dos números antigos para os cinco domínios está no
[guia do banco](../database/README.md).

Referências privadas locais relatadas anteriormente, relativas a `-TCC-`:

- Backup original: `.local/app_zubbo-xampp-backup.sql`, SHA-256
  `A546EE08965AFB9F0B435B290BDB35A751A6E30F6CE237F4FE0F3782BB087DC3`.
- Backup da cópia isolada: `.local/app_zubbo-before-tests.sql`.
- Auditorias: `.local/audit-xampp-original.json`, `.local/audit-before.json` e
  `.local/audit-after.json`.
- Evidência visual: `.local/mapa-validado.png`.
- Logs antigos: `.local/http-php84.log`, `.local/http-apache.log`, `.local/db-php84.log`,
  `.local/security-final.log` e `.local/railway-readonly.log`.

O relato anterior afirma restauração validada em `zubbo_test_backup_original` com
contagens de usuários, eventos e notificações preservadas. Esses arquivos privados
não são anexos públicos nem backups confirmados do novo environment. Não foram
revalidados nesta retomada por este registro. Novos logs usam `.local/logs/`.

### Observação histórica da demo anterior

A origem consultada foi `https://zubbo-app-demo.up.railway.app`, somente por GET.
Foram relatadas 9 de 11 respostas esperadas; `/sw.js` e `/public/manifest.php`
retornaram 404, enquanto `/health.php` retornou 200. O conector não permitiu consultar
branch, SHA, variáveis, logs ou schema. Essas respostas não confirmam configuração,
acesso autenticado, estado atual da demo nem qualquer publicação de `demo.02`.

A tentativa posterior de selecionar uma janela do navegador foi encerrada pela
ferramenta antes de acessar o Railway porque a URL não pôde ser identificada com
confiança. Não houve commit, push ou deploy atribuído àquela tentativa. A falha da
ferramenta não demonstra indisponibilidade ou falha de autenticação do Railway.
