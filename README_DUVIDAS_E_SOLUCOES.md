# Zubbo: erros de execução e soluções

Guia de diagnóstico da instalação, da aplicação e da hospedagem. Revisado em 25/10/2026 a partir dos arquivos do projeto. Complementa o [README principal](README.md) e o [guia do banco](./-TCC-/database/README.md).

O guia cobre as falhas identificáveis no código atual e problemas de ambiente que podem impedir sua execução. Problemas novos, dependências externas e alterações futuras podem exigir investigação adicional. As causas possíveis descritas não significam que todos esses problemas estejam acontecendo na instalação atual.

Os caminhos de arquivos partem de `-TCC-`, salvo indicação diferente. Os comandos com `bat` são para o Prompt de Comando do Windows; os comandos com `powershell` são para o PowerShell. Hífens necessários em comandos, nomes e endereços foram preservados.

## Índice

1. [Como começar o diagnóstico](#diagnostico)
2. [Instalação, Apache e PHP](#instalacao)
3. [Rotas, aparência e navegador](#rotas)
4. [Conexão, estrutura e erros do banco](#banco)
5. [Login, sessão, cadastro e recuperação](#autenticacao)
6. [Envio de mensagens por correio eletrônico](#email)
7. [Mapa e pesquisa](#mapa)
8. [Eventos, equipes e amizades](#eventos)
9. [Conversas e notificações internas](#conversas)
10. [Fotos e arquivos](#uploads)
11. [Notificações push](#push)
12. [Hospedagem e deploy](#hospedagem)
13. [Testes e divergências entre versões](#testes)
14. [Coleta de evidências e confirmação da solução](#evidencias)

<a id="diagnostico"></a>

## 1. Como começar o diagnóstico

### 1.1 Registre o sintoma antes de alterar a instalação

Anote o endereço acessado, o horário, o texto exato da mensagem e a ação anterior ao erro. Abra as ferramentas de desenvolvimento com F12. Em Rede, repita a ação e identifique a requisição que falhou; registre seu status e a resposta. Em Console, observe erros de JavaScript.

Use o código de resposta para orientar a investigação:

* `200`: houve uma resposta, mas ela ainda pode conter uma tela de login ou mensagem de erro. Confira o conteúdo e os redirecionamentos.
* `302` ou `303`: redirecionamento. É normal após diversas ações; investigue quando o destino estiver incorreto ou houver repetição.
* `400`: dados inválidos ou incompletos.
* `401`: sessão ausente ou expirada em endpoints que usam esse status.
* `403`: acesso ou solicitação recusados. Diferencie a página do Apache de mensagens produzidas pelo PHP.
* `404`: arquivo inexistente, rota incorreta ou acesso intencionalmente bloqueado.
* `405`: endpoint chamado com método diferente do permitido.
* `429`: limite de tentativas ou intervalo mínimo entre ações.
* `500`: falha interna; consulte o log para identificar a causa.
* `502`, `503` ou `504`: investigue o estado do serviço e o intermediário da hospedagem; endpoints da aplicação também podem retornar `503`, como o push sem configuração.

### 1.2 Ordem de verificação

1. Confirme que Apache e MySQL estão iniciados no XAMPP.
2. Abra `http://localhost/Zubbo-App/-TCC-/`.
3. Confira o PHP e as dependências.
4. Verifique a conexão usada pelo Apache.
5. Confira o esquema do banco.
6. Faça login com uma conta de teste e repita apenas a ação com falha.
7. Confira integrações externas, como mapas, correio eletrônico e push.

A página inicial, a tela de login e `health.php` podem responder normalmente sem comprovar que o banco funciona. `health.php` apenas retorna `{"status":"ok"}`.

### 1.3 Locais para consultar

* Apache/PHP no XAMPP: `C:/xampp/apache/logs/error.log`.
* Requisições do Apache: `C:/xampp/apache/logs/access.log`.
* MySQL: use o acesso aos logs pelo painel do XAMPP e confira o arquivo indicado pela configuração do serviço.
* Servidor embutido: consulte a janela que executa `php -S`.
* Hospedagem: consulte separadamente os logs de construção e os logs de execução.
* Navegador: Console, Rede e a área de armazenamento/Service Workers nas ferramentas de desenvolvimento.

Antes de importar SQL, restaurar backups ou alterar vínculos de usuários, confirme o banco de destino e tenha uma cópia recuperável. Os procedimentos de consulta deste guia não exigem apagar dados.

<a id="instalacao"></a>

## 2. Instalação, Apache e PHP

### 2.1 O navegador informa que não foi possível acessar localhost

**Causas possíveis:** Apache parado, porta diferente, outro serviço respondendo ou acesso por uma máquina diferente.

**Resolução:**

1. Confira se Apache aparece em execução no XAMPP.
2. Observe a porta informada no painel e a diretiva `Listen` da configuração.
3. Se a porta for `8081`, por exemplo, abra `http://localhost:8081/Zubbo-App/-TCC-/` e ajuste a URL da aplicação.
4. Lembre que `localhost` identifica o computador onde o navegador está aberto. No celular, ele não identifica automaticamente o computador com XAMPP.
5. Se o processo não iniciar, siga o próximo item.

**Confirmação:** a página de entrada responde no endereço e na porta configurados.

### 2.2 Apache não inicia ou encerra imediatamente

**Causas possíveis:** porta ocupada, erro de sintaxe, módulo ausente ou arquivo de configuração inválido.

**Resolução:**

1. Leia as últimas mensagens do painel e do log.
2. Valide a configuração:

   ```bat
   C:\xampp\apache\bin\httpd.exe -t
   ```

3. Se houver erro, corrija o arquivo e a linha apontados antes de reiniciar.
4. Para conferir processos escutando nas portas mais comuns, use PowerShell:

   ```powershell
   Get-NetTCPConnection -State Listen |
       Where-Object { $_.LocalPort -in 80,443,3306,8080 } |
       Select-Object LocalAddress,LocalPort,OwningProcess
   ```

5. Identifique o processo pelo número retornado, por exemplo `Get-Process -Id 1234`, substituindo o número. Resolva o conflito parando o serviço conhecido ou escolhendo outra porta. Não encerre processos sem identificar sua função.

**Confirmação:** a validação informa `Syntax OK`, Apache permanece em execução e o endereço abre.

### 2.3 Forbidden ao abrir a pasta Zubbo-App

**Sintoma:** HTTP 403 com a assinatura do Apache e registro `AH01276: No matching DirectoryIndex`.

**Causa identificada neste projeto:** a pasta externa `Zubbo-App` não possui arquivo inicial. O `.htaccess` contém `Options -Indexes`, que impede a listagem dessa pasta.

**Resolução:** abra `http://localhost/Zubbo-App/-TCC-/` ou `http://localhost/Zubbo-App/-TCC-/public/index.php`. Se o clone estiver em outra pasta, adapte o endereço.

Não é necessário habilitar a listagem de diretórios. Se o log indicar `client denied by server configuration`, em vez de ausência de índice, confira o `DocumentRoot` e as regras de acesso do diretório específico.

**Confirmação:** a entrada da aplicação abre; o endereço externo pode continuar retornando 403.

### 2.4 HTTP 500 após copiar ou editar o .htaccess

**Causas possíveis:** `mod_rewrite` indisponível, diretiva não permitida ou regra com erro de sintaxe.

**Resolução:** leia o log imediatamente após uma requisição. Mensagens como `Invalid command 'RewriteEngine'` apontam para o módulo; mensagens com `not allowed here` apontam para a permissão da diretiva. Confira a configuração do diretório da aplicação e o carregamento do módulo. Valide e reinicie o Apache após a correção.

O comando `httpd.exe -t` valida a configuração principal; erros de `.htaccess` também podem aparecer somente quando a rota é solicitada. Não remova todas as regras para encobrir o erro.

### 2.5 PHP ou Composer não é reconhecido no terminal

**Causa provável:** programa ausente do PATH ou terminal usando uma instalação diferente.

**Resolução:**

```bat
where php
where composer
C:\xampp\php\php.exe -v
C:\xampp\php\php.exe --ini
```

Use o caminho completo do PHP enquanto ajusta o PATH. Se Composer não estiver instalado, instale sua versão oficial e selecione o PHP correto. Abra uma nova janela após alterar o PATH.

**Confirmação:** a versão e o `php.ini` exibidos correspondem ao ambiente pretendido.

### 2.6 Falta vendor/autoload.php ou uma classe não é encontrada

**Sintomas:** `Failed opening required`, `Class not found`, biblioteca de correio eletrônico ou push ausente.

**Resolução:** execute na pasta que contém `composer.json` e `composer.lock`:

```bat
cd C:\xampp\htdocs\Zubbo-App\-TCC-
composer install
composer check-platform-reqs
```

O comando de instalação usa as versões do arquivo de dependências travadas quando ele existe. Não substitua automaticamente por `composer update`, que pode mudar essas versões. Se os arquivos estiverem presentes, mas o carregamento continuar falhando, confira a mensagem exata, o namespace e se a cópia de `vendor` pertence ao mesmo projeto.

**Confirmação:** a instalação termina sem erro e o fluxo que dependia da classe funciona. Referência: [instalação pelo Composer](https://getcomposer.org/doc/03-cli.md#install-i).

### 2.7 Extensão PHP ausente ou dependência incompatível

**Sintomas:** `could not find driver`, função `mb_*` ausente, classe `finfo` indisponível ou recusa do Composer por requisito de plataforma.

**Resolução:**

1. Execute `C:\xampp\php\php.exe -m` e confira `PDO`, `pdo_mysql`, `curl`, `mbstring`, `openssl` e `fileinfo`.
2. Confira `php --ini` antes de editar o arquivo de configuração.
3. Habilite a extensão disponível para a instalação correspondente e reinicie o Apache.
4. Se o Composer exigir PHP superior ao instalado, confira o requisito da dependência travada. Use uma versão compatível em vez de ignorar requisitos.
5. Compare os ambientes web e terminal: o resultado do terminal sozinho não comprova a configuração do Apache.

### 2.8 Tela branca, erro de sintaxe ou HTTP 500 sem explicação

**Causas possíveis:** erro fatal do PHP, arquivo incompleto, versão incompatível, exceção não tratada ou saída interrompida.

**Resolução:** consulte o log no horário da requisição. Se ele apontar sintaxe em um arquivo, valide apenas esse arquivo:

```bat
C:\xampp\php\php.exe -l app\views\painel\Painel-inicial.php
```

Execute a partir de `-TCC-`. Corrija a linha apontada e valide novamente. Se não houver erro de sintaxe, siga a exceção real, como falha de banco ou dependência. Não é necessário habilitar exibição pública de detalhes para ler os logs.

### 2.9 Headers already sent

**Causas possíveis:** espaços ou BOM antes de `<?php`, impressão de conteúdo antes de `header()` ou aviso de PHP emitido antes do redirecionamento.

**Resolução:** use o primeiro arquivo e a linha indicados no aviso para localizar a saída inicial. Salve o arquivo em UTF 8 sem BOM, remova saídas de depuração e corrija avisos anteriores. Nos fluxos de redirecionamento, a resposta deve ser preparada antes de imprimir HTML.

**Confirmação:** o redirecionamento acontece sem aviso e a sessão permanece válida.

<a id="rotas"></a>

## 3. Rotas, aparência e navegador

### 3.1 Página não encontrada ou redirecionamento para uma pasta errada

**Causas possíveis:** nome do clone diferente, prefixo incompleto, arquivo ausente ou configuração copiada de outra hospedagem.

**Resolução:** no XAMPP com o caminho padrão, use:

```apache
SetEnv ZUBBO_BASE_URL "http://localhost/Zubbo-App/-TCC-"
SetEnv ZUBBO_BASE_PATH "/Zubbo-App/-TCC-"
```

No servidor embutido iniciado em `-TCC-`, use `ZUBBO_BASE_PATH=/` e `ZUBBO_BASE_URL=http://127.0.0.1:8080`. Reinicie o processo afetado e compare o destino do redirecionamento em Rede. Evite prefixo duplicado e lembre da porta quando ela não for 80.

### 3.2 Arquivos internos retornam 404

**Sintoma:** abrir `config/database.php`, scripts SQL, arquivos iniciados por `_` ou arquivos de `includes` retorna 404.

**Explicação:** essas rotas são bloqueadas pelo roteador e pelas regras da aplicação. Não é uma prova de que o arquivo foi apagado.

**Resolução:** execute ferramentas internas pelo terminal quando previsto e use as páginas públicas para operar o sistema. Não libere diretórios internos para testar a conexão.

### 3.3 Página sem estilo, imagem quebrada ou botão sem ação

**Resolução:**

1. Em Rede, localize o CSS, JavaScript ou imagem com erro.
2. Confira o endereço completo e se o arquivo existe.
3. Se houver 404, revise o prefixo, o nome e as diferenças de maiúsculas/minúsculas. Um caminho tolerado no Windows pode falhar na hospedagem Linux.
4. Se houver bloqueio no Console, confira o domínio solicitado e a política aplicada. Não remova todos os cabeçalhos para liberar um recurso específico.
5. Recarregue com Ctrl + F5. Se persistir, verifique o primeiro erro de JavaScript, que pode impedir o restante da inicialização.

**Confirmação:** os recursos respondem com conteúdo do tipo correto; uma resposta HTML no endereço de um script pode indicar redirecionamento para login ou rota incorreta.

### 3.4 Unexpected token, resposta JSON inválida ou erro em fetch

**Causa possível:** o JavaScript esperava JSON, mas recebeu HTML, tela de login, aviso do PHP ou página de erro.

**Resolução:** abra a resposta da requisição em Rede. Se for login, renove a sessão. Se houver erro PHP, resolva a causa no servidor. Se for 404, corrija o caminho. Compare também o tipo de conteúdo. Não tente corrigir apenas o interpretador JSON sem examinar a resposta recebida.

### 3.5 Método não permitido

**Causa:** abertura por GET de um endpoint destinado a POST, ou integração enviando método errado.

**Resolução:** execute a ação pelo formulário ou botão correspondente. Confira em Rede o método e os campos enviados. Não transforme o endpoint de alteração em GET para fazer o erro desaparecer.

### 3.6 Alteração não aparece, versão antiga ou navegação inconsistente

**Resolução:** confirme que está acessando a mesma cópia editada, a mesma porta e o mesmo domínio. Recarregue sem cache. Se houver Service Worker antigo, inspecione seu caminho e escopo antes de atualizar ou cancelar o registro. Limpar dados do site pode encerrar a sessão e exigir nova inscrição push; use essa ação somente para o site afetado quando o diagnóstico apontar armazenamento antigo.

<a id="banco"></a>

## 4. Conexão, estrutura e erros do banco

### 4.1 Não foi possível conectar ao banco de dados

**Origem:** `config/database.php` retorna HTTP 500 quando a conexão PDO ou o comando `SET time_zone` lança uma `PDOException`.

**Resolução em ordem:**

1. Inicie o MySQL e confirme que ele permanece em execução.
2. Compare host e porta com `ZUBBO_DB_HOST` e `ZUBBO_DB_PORT`.
3. Confira se `ZUBBO_DB_NAME` corresponde a um banco existente.
4. Confira o usuário, a senha e os privilégios desse usuário no mesmo servidor.
5. Confirme `pdo_mysql` no PHP que serve o site.
6. Confira se as variáveis foram aplicadas ao Apache, e não somente a outro terminal.
7. Teste com o cliente MySQL, conforme o próximo item.

Sem variáveis definidas, o código usa `localhost:3306`, banco `app_zubbo`, usuário `root` e senha vazia. Esses padrões não criam o banco nem garantem que sua instalação aceite essas credenciais.

O log `db.connection_failed`, com `reason_code=pdo_unavailable`, é genérico e não armazena a mensagem original da exceção. Não conclua que a senha está errada apenas com esse registro.

### 4.2 Como verificar sem gravar dados

Para testar o caminho de conexão utilizado pelo Apache, abra por GET:

```text
http://localhost/Zubbo-App/-TCC-/app/views/auth/processa-login.php
```

O arquivo carrega a conexão antes de verificar o método e deve redirecionar para o login. Isso não valida credenciais de uma conta nem todas as tabelas.

Para separar problemas de conexão e aplicação, use o cliente com os dados reais do seu ambiente:

```bat
C:\xampp\mysql\bin\mysql.exe --host=localhost --port=3306 --user=root --password app_zubbo
```

O cliente solicita a senha; pressione Enter somente se ela realmente estiver vazia. Após conectar, execute:

```sql
SELECT 1;
SELECT DATABASE();
SELECT VERSION();
SHOW TABLES;
SELECT id_user, nome_user, email_user, status_user, senha_user
FROM Usuario LIMIT 0;
```

A última consulta valida as colunas sem retornar registros ou hashes. O cliente e o Apache podem usar configurações diferentes; o sucesso de um não garante o sucesso do outro.

### 4.3 MySQL não inicia

**Causas possíveis:** porta ocupada, outra instância ativa, falta de permissão, falta de espaço ou problema nos arquivos de dados.

**Resolução:** leia primeiro o log do MySQL. Identifique quem usa a porta 3306. Se escolher outra porta, atualize a aplicação. Em erros de armazenamento, preserve a pasta de dados e obtenha backup antes de reparar. Não apague `ibdata`, arquivos de log ou pastas de bancos como tentativa genérica de correção.

**Confirmação:** o serviço permanece ativo e o cliente consegue conectar ao banco correto.

### 4.4 Erros 2002 ou 2003: servidor inacessível

**Resolução:** confirme serviço, host e porta; teste a acessibilidade com `Test-NetConnection localhost -Port 3306` no PowerShell, substituindo os valores conforme o ambiente. Se `localhost` resolver para um endereço que o serviço não atende, compare com `127.0.0.1` e confira em qual endereço o MySQL escuta. Em banco remoto, investigue rede e regras de acesso a partir da máquina que executa o PHP.

O sucesso do teste de porta comprova apenas acesso ao serviço, não autenticação nem existência do banco.

### 4.5 Erro 1045: autenticação recusada

**Resolução:** confira o usuário e a senha reais, sem espaços inseridos por cópia. Teste a mesma conta no mesmo host e porta. Um acesso automático pelo phpMyAdmin pode usar outra conta. Confira também se a conta é permitida para a origem da conexão. Ajuste a configuração da aplicação ou a conta específica; não remova senhas indiscriminadamente.

### 4.6 Erro 1044: acesso ao banco recusado

**Resolução:** peça ao responsável pelo banco para conferir os privilégios da conta sobre o banco selecionado. A conta pode autenticar e ainda assim não ter acesso à base. Conceda somente o necessário ao banco correto e repita a consulta de leitura.

### 4.7 Erro 1049: banco desconhecido

**Resolução:** compare o nome com `ZUBBO_DB_NAME`. Para instalação nova, crie um banco vazio `app_zubbo` com `utf8mb4`, selecione esse banco e importe a estrutura e os dados iniciais. Para base existente, localize a base correta antes de criar outra. Um banco novo vazio pode ocultar o erro de conexão e continuar sem os dados esperados.

### 4.8 Erro 1046: nenhum banco selecionado durante importação

**Causa:** os scripts não contêm um `USE` fixo.

**Resolução:** no phpMyAdmin, selecione o banco antes de importar. No cliente SQL, confira `SELECT DATABASE()` e selecione explicitamente o destino. Execute a estrutura antes dos dados iniciais. Não altere um script compartilhado para fixar um banco de produção.

### 4.9 Arquivo .env não produz efeito ou terminal funciona e Apache falha

**Causa:** o fluxo atual usa `getenv()` e não carrega `.env` automaticamente. Cada processo tem seu próprio ambiente.

**Resolução:** no XAMPP, configure as variáveis no arquivo local do Apache, por exemplo `C:/xampp/apache/conf/extra/httpd-xampp.conf`:

```apache
SetEnv ZUBBO_DB_HOST "localhost"
SetEnv ZUBBO_DB_PORT "3306"
SetEnv ZUBBO_DB_NAME "app_zubbo"
SetEnv ZUBBO_DB_USER "root"
SetEnv ZUBBO_DB_PASSWORD ""
```

Substitua os exemplos pelas credenciais reais. Valide a configuração e reinicie o Apache. Para ferramentas CLI, defina as variáveis na janela que executará o PHP. Não publique esses valores no repositório ou em capturas de tela.

### 4.10 Erro 1146: tabela inexistente; erro 1054: coluna desconhecida

**Causas possíveis:** importação incompleta, banco errado, versões de código e esquema diferentes ou nomes com capitalização diferente.

**Resolução:**

1. Confira `SELECT DATABASE()` na conexão usada para investigar.
2. Use `SHOW TABLES` e o nome exato indicado no erro.
3. Execute `php tests/audit-migrations.php` a partir de `-TCC-`, com as variáveis do terminal apontando para o destino correto.
4. Em instalação nova, os scripts ativos são `database/scripts/criacaotables.sql` e `database/scripts/insertstables.sql`, nessa ordem.
5. Em base existente, siga as cinco migrations consolidadas descritas no guia do banco. Não reaplique a criação integral sobre dados existentes.
6. Na migração entre Windows e Linux, confira os nomes das tabelas e a configuração de capitalização do servidor antes de renomear qualquer estrutura.

O modo de exceção do PDO pode interromper consultas posteriores à conexão; isso é diferente da mensagem genérica de falha de conexão. Referência: [tratamento de erros do PDO](https://www.php.net/pdo.error-handling.php).

### 4.11 Erros 1050 ou 1060: tabela ou coluna já existe

**Causa possível:** script de instalação aplicado novamente ou mistura de estrutura final com migrações antigas.

**Resolução:** interrompa a sequência e confira o estado do esquema. Em instalação nova, use um banco vazio. Em atualização, use a sequência consolidada apropriada, sem misturar arquivos históricos. Não apague uma tabela para fazer o importador continuar. Confira quais instruções já foram aplicadas antes da falha, pois operações de estrutura podem permanecer mesmo quando etapas seguintes falham.

### 4.12 Erros 1062, 1451 ou 1452: duplicidade e vínculos inválidos

**Resolução:** em duplicidade, identifique a restrição indicada e verifique se a ação já foi concluída, por exemplo cadastro de um endereço já usado. Em falha de chave estrangeira, confira se o registro relacionado ainda existe e se a ordem da importação é a correta. Preserve vínculos de contas, eventos e mensagens. Não desative globalmente as restrições para esconder inconsistências.

Se uma migration emitir `ERRO_*_resolver_antes_de_migrar`, siga o diagnóstico específico e resolva o conflito com backup antes de continuar.

### 4.13 Erro 1213: deadlock; erro 1205: espera por bloqueio

**Sintoma:** operação falha durante acessos simultâneos. Um deadlock já apareceu no histórico local do chat.

**Resolução para operação:** confira se a ação foi gravada antes de repeti-la, especialmente no envio de mensagens. Aguarde brevemente, atualize e tente uma vez. Se o erro se repetir, registre o endpoint e encaminhe a revisão ao desenvolvimento.

**Resolução técnica:** examine as transações concorrentes, índices e ordem de acesso às tabelas. Mantenha transações curtas e implemente repetição controlada da transação completa quando a operação permitir, evitando duplicidade. No diagnóstico autorizado, `SHOW ENGINE INNODB STATUS` ajuda a localizar o deadlock. Aumentar o tempo limite sozinho não elimina a causa. Referência: [tratamento de deadlocks no MySQL](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlocks-handling.html).

### 4.14 Erro 1055 ou incompatibilidade com ONLY_FULL_GROUP_BY

**Resolução:** identifique a consulta no log e corrija seus agrupamentos ou agregações conforme o resultado pretendido. Compare a versão do esquema e do código. Evite desativar o modo global apenas para fazer uma consulta antiga passar, pois isso pode esconder resultados ambíguos.

### 4.15 Caracteres incorretos, emoji não salvo ou erro de tamanho

**Resolução:** confira que banco, tabelas e conexão utilizam `utf8mb4`; o DSN atual já solicita esse charset. Se o erro indicar coluna pequena, confira o limite do formulário e do esquema. Repare conversões de dados em cópia antes de alterar a base existente. Salvar o arquivo PHP em UTF 8 não converte automaticamente os dados já armazenados.

### 4.16 Horários incorretos ou códigos expirando antes do esperado

**Resolução:** confira os relógios do servidor e do computador. `config/security.php` usa `America/Sao_Paulo`, e a conexão aplica o deslocamento do PHP ao MySQL. A recuperação de senha usa `UTC_TIMESTAMP()`. Compare com consultas de leitura:

```sql
SELECT NOW(), UTC_TIMESTAMP(), @@session.time_zone;
```

Não remova a expiração para contornar diferenças de relógio. Se a conexão funciona isoladamente e falha ao preparar a sessão, investigue especificamente a resposta a `SET time_zone`.

### 4.17 Too many connections, conexão perdida ou servidor encerrado

**Resolução:** confira reinícios e recursos do serviço, atividade simultânea e duração das consultas. Investigue se o problema acontece após inatividade ou em uma operação específica. Antes de aumentar limites, confirme que não há consultas bloqueadas ou criação excessiva de conexões. Se o serviço reiniciou durante uma gravação, confira o estado da ação antes de repetir.

<a id="autenticacao"></a>

## 5. Login, sessão, cadastro e recuperação

### 5.1 Endereço ou senha incorretos

**Mensagem:** “E-mail ou senha incorretos.”

**Resolução:** confira o endereço cadastrado e a senha, inclusive espaços copiados. Acesse o banco esperado: a mesma instalação pode estar apontando para uma base de demonstração diferente. Se não lembrar a senha, use a recuperação. Não substitua o hash por uma senha em texto puro no banco.

Se o acesso sempre falhar, confira o estado da conta e a estrutura de `Usuario`; a mensagem de autenticação pode ser intencionalmente genérica.

### 5.2 Login volta para a entrada, sessão expira ou conta fica indisponível

**Causas possíveis:** sessão antiga, cookie não mantido, alteração de senha, conta indisponível ou dados de sessão perdidos.

**Resolução:** saia e entre novamente pelo mesmo domínio e protocolo. Não alterne `localhost`, `127.0.0.1` e um domínio externo durante o fluxo. Confira cookies e o armazenamento de sessões do PHP. O código compara o estado da conta e uma impressão das credenciais; uma sessão anterior a mudanças pode ser invalidada deliberadamente.

Se houver `auth.session_revoked`, confira o estado da conta e se a senha mudou. Uma conta suspensa ou banida precisa de análise administrativa; não remova essa validação para liberar o acesso.

### 5.3 Muitas tentativas, mesmo depois de esperar

**Comportamento atual:** login limita 5 tentativas na janela de 900 segundos; cadastro usa 5 em 3600 segundos; verificação do código usa 8 em 600 segundos; recuperação de senha usa 4 em 3600 segundos.

**Resolução:** pare as repetições e aguarde a janela aplicável. Se continuar bloqueado sem novas tentativas, procure `Rate limit indisponível` no log. O código bloqueia a tentativa quando não consegue acessar o limitador.

Confira permissão, espaço e bloqueio de arquivos no diretório `zubbo-rate-limits`, criado dentro da pasta temporária do PHP. Limpar cookies não resolve necessariamente, pois a limitação persiste no servidor. Não apague todos os limites como procedimento habitual.

### 5.4 Solicitação inválida ou sessão do formulário expirada

**Causa provável:** token CSRF ausente, diferente da sessão atual ou formulário aberto antes de novo login.

**Resolução:** atualize a página do formulário, confirme que continua autenticado e reenvie uma vez. Evite reaproveitar uma aba antiga após trocar de conta. Em alterações de frontend, confira se o campo `csrf_token` está sendo enviado e se os cookies da sessão acompanham a requisição.

**Confirmação:** o envio pela interface atual é aceito. Não desative a validação CSRF como correção.

### 5.5 Solicitação entre sites bloqueada ou origem inválida

**Resolução:** acesse a aplicação pelo mesmo host, porta e protocolo do formulário. Confira os cabeçalhos Origin/Referer e os redirecionamentos em Rede. Ajuste `ZUBBO_BASE_URL`, o caminho e a configuração do intermediário HTTPS quando houver divergência. Um formulário copiado para outro domínio não deve funcionar automaticamente.

### 5.6 Cadastro retorna erro=dados ou erro=email

**Resolução:** confira os limites validados em `app/views/auth/insert-user.php`: nome preenchido com até 50 caracteres, endereço válido com até 70 bytes, telefone normalizado com 11 dígitos, senha entre 8 e 255 bytes, confirmação igual e data de nascimento válida.

`erro=email` indica que o endereço já está cadastrado. Use o login ou a recuperação em vez de tentar criar outra conta com o mesmo endereço. Se os dados parecem corretos, confira os nomes dos campos enviados pelo formulário.

### 5.7 Código inválido, expirado ou cadastro volta ao início

**Mensagens possíveis:** código de 6 números inválido, código incorreto, código expirado ou perda de `cadastro_pendente` na sessão.

**Resolução:** mantenha o cadastro na mesma sessão, use o código correspondente à tentativa atual e siga o reenvio disponibilizado pela interface. Se a aplicação informar que expirou e pedir novo cadastro, reinicie o fluxo. Não pule diretamente para a seleção de esportes.

Se a página perde o progresso a cada acesso, investigue persistência de cookies e gravação de sessões. No modo de demonstração, o código só aparece quando `ZUBBO_DEMO_MODE=true` e `ZUBBO_ENV` corresponde a um dos ambientes não produtivos aceitos pelo código.

### 5.8 Escolher esportes não conclui o cadastro

**Resolução:** confira se o endereço foi verificado na sessão atual e se o cadastro ainda não expirou. Selecione modalidades existentes; `erro=nenhum` indica seleção ausente ou inválida. Se houver `erro=salvar`, consulte o log e confira esquema, restrições e possíveis cadastros simultâneos do mesmo endereço. Verifique se a conta já foi criada antes de repetir a operação.

### 5.9 Recuperação informa sucesso, mas a mensagem não chega

**Explicação:** o sistema responde de forma genérica para não revelar quais endereços possuem conta. Em alguns erros de envio, a resposta também permanece genérica e o erro é registrado no log.

**Resolução:** confira a caixa de spam, o endereço usado e se a conta está ativa. Procure `Falha no e-mail de recuperação` e `Erro na recuperação de senha` no log. Depois siga a seção de correio eletrônico. A frase de confirmação sozinha não comprova entrega.

### 5.10 Link de recuperação inválido ou expirado

**Resolução:** solicite um novo link e use a mensagem mais recente. O token precisa ter o formato esperado, não ter sido usado e estar dentro da validade; a emissão atual usa uma hora. Uma nova emissão bem-sucedida remove links anteriores da conta. Confira o domínio do link e o relógio do servidor. Não copie o token para logs públicos.

Para a nova senha, cumpra o limite de 8 a 255 caracteres apresentado pela página e confirme o mesmo valor nos dois campos. Se aparecer falha genérica ao redefinir, consulte o log e a tabela `Recuperacao_Senha` por meio da auditoria de estrutura.

### 5.11 Administrador sem acesso

**Causa possível:** a conta comum não está vinculada a um administrador ou a sessão administrativa não foi renovada.

**Resolução:** confirme que a conta está ativa. Quando a promoção for autorizada, execute na pasta `-TCC-`:

```bat
php database/tools/create-admin.php
```

Informe a conta correta e entre novamente. O privilégio usa vínculo de usuário; coincidência de endereço em um registro antigo não garante acesso. Em “Administrador já associado a outro usuário”, confira o vínculo existente antes de alterar dados.

### 5.12 Avisos de session_start ou perda contínua de sessão

**Resolução:** confira o diretório `session.save_path` do PHP efetivo, sua existência, espaço e permissão para o processo. Verifique se o navegador aceita o cookie e se o HTTPS está sendo identificado corretamente. Em múltiplas instâncias, sessões locais podem não acompanhar a requisição seguinte; use uma estratégia de sessão compartilhada adequada à hospedagem.

A remoção de cookies do site encerra a sessão e exige novo login. Referência: [configuração de sessões do PHP](https://www.php.net/manual/en/session.configuration.php).

<a id="email"></a>

## 6. Envio de mensagens por correio eletrônico

### 6.1 SMTP não configurado ou Resend sem chave

**Resolução:** escolha explicitamente um provedor. Para Resend, configure `ZUBBO_MAIL_PROVIDER=resend`, `ZUBBO_RESEND_API_KEY` e `ZUBBO_MAIL_FROM`. Para SMTP, configure `ZUBBO_MAIL_PROVIDER=smtp`, `ZUBBO_SMTP_USER`, `ZUBBO_SMTP_PASSWORD`, host, porta, criptografia e remetente.

No fluxo atual, provedor vazio sem chave Resend gera erro; não ativa SMTP automaticamente. Após alterar o ambiente do Apache, reinicie o serviço.

**Confirmação:** faça uma única tentativa com uma conta de teste e confira a entrega e os registros do provedor, sem publicar credenciais.

### 6.2 Provedor, remetente ou criptografia inválidos

**Mensagens:** `ZUBBO_MAIL_PROVIDER inválido`, `ZUBBO_MAIL_FROM contém um endereço inválido` ou `ZUBBO_SMTP_ENCRYPTION inválido`.

**Resolução:** o provedor aceita `resend` ou `smtp`. O remetente precisa conter um endereço válido, com nome opcional. A criptografia SMTP aceita `auto`, `starttls`, `smtps` ou `none`; use a opção exigida pelo provedor. O modo automático do código escolhe SMTPS para porta 465 e STARTTLS para as demais portas. Não use ausência de criptografia para contornar uma falha de certificado.

### 6.3 Falha de autenticação SMTP, timeout ou conexão recusada

**Resolução:** confira host, porta, usuário, credencial específica de aplicativo quando exigida pelo provedor e permissões da conta para envio. Teste a acessibilidade da porta a partir do servidor. Verifique se a hospedagem permite SMTP de saída. O código define tempos limites para evitar espera indefinida; repetição de timeout exige investigar rede e provedor, não apenas aumentar os limites.

### 6.4 Certificado SSL inválido ou cURL error 60

**Resolução:** confira data e hora do sistema e a configuração dos certificados CA usados pelo PHP. Atualize o conjunto confiável e verifique os caminhos `curl.cainfo` e `openssl.cafile` quando necessários. Confira o erro no PHP efetivamente usado pelo Apache. Não desative a verificação TLS como solução.

### 6.5 Resend responde com erro ou mensagem é rejeitada

**Resolução:** consulte o status e a rejeição no serviço sem registrar a chave. Confira chave válida, remetente autorizado, domínio verificado quando aplicável e limites da conta. Corrija o campo recusado antes de repetir. Uma resposta de aceitação do provedor ainda não comprova entrega na caixa principal; confira o estado da entrega e spam.

### 6.6 Links da mensagem apontam para domínio errado

**Resolução:** defina `ZUBBO_BASE_URL` com o endereço completo da instalação, incluindo porta e prefixo quando existentes. Em processos sem host HTTP, essa variável é necessária para gerar URLs absolutas. Reinicie o processo e solicite uma nova mensagem, pois os links já enviados não são reescritos.

<a id="mapa"></a>

## 7. Mapa e pesquisa

### 7.1 Mapa temporariamente indisponível

**Condições exatas no painel:** `mapConfig.public_token` vazio ou `mapboxgl` não carregado.

**Resolução:**

1. Confira se `ZUBBO_MAPBOX_PUBLIC_TOKEN` chega ao Apache.
2. O valor precisa ser um token público real com formato `pk.segmento.segmento`. Um token com formato não aceito é convertido em vazio por `config/map.php`.
3. Configure o token no ambiente, não apenas em uma cópia antiga do JavaScript ou em um `.env` sem carregador.
4. Em Rede, confira o carregamento de `https://api.mapbox.com/mapbox-gl-js/v3.29.0/mapbox-gl.js`.
5. Se esse recurso falhar, investigue internet, DNS, bloqueio de extensão e o erro no Console.
6. Reinicie o Apache após mudar o ambiente e recarregue a página.

Exemplo de configuração, sem valor real:

```apache
SetEnv ZUBBO_MAPBOX_PUBLIC_TOKEN "SEU_TOKEN_PUBLICO_PK"
```

**Confirmação:** a mensagem inicial deixa de aparecer e as requisições de estilo e mapa são aceitas. Ter um token configurado não garante que ele continue válido no provedor.

### 7.2 Token existe, mas a API retorna 401 ou 403

**Resolução:** confira no serviço se o token continua válido, possui as permissões necessárias e aceita a origem usada. Tokens com restrição de URL precisam autorizar o endereço local também; não suponha que `localhost` está liberado. Confira a requisição do navegador e sua resposta antes de substituir o token.

Não use um token secreto `sk.` no frontend. Uma chamada em terminal pode ser rejeitada por restrição de URL mesmo quando a chamada autorizada no navegador funciona. Referências: [gestão de tokens](https://docs.mapbox.com/accounts/guides/tokens/) e [erros de tokens Mapbox](https://docs.mapbox.com/help/troubleshooting/token-errors/).

### 7.3 Área vazia mesmo com token e biblioteca presentes

**Causas possíveis:** erro ao carregar o estilo, falha de recurso externo, indisponibilidade de WebGL ou erro JavaScript posterior.

**Resolução:** examine o primeiro erro no Console e as respostas de estilos e recursos em Rede. Teste em outro navegador atualizado para separar a configuração da aplicação de um problema gráfico do dispositivo. Se usar estilos próprios, confira acesso e formato. O projeto usa por padrão `streets-v12` e `dark-v11`, com variáveis opcionais para substituí-los.

### 7.4 Nenhum local aprovado de Diadema cadastrado

**Explicação:** o mapa consulta locais com `status_local='aprovado'` e endereço contendo `Diadema`. Uma tabela preenchida com outros registros pode produzir zero resultados.

**Resolução:** confira o banco selecionado, a importação dos dados iniciais e os estados dos locais. Para leitura:

```sql
SELECT COUNT(*) AS locais_exibiveis
FROM LocalEsp
WHERE status_local = 'aprovado'
  AND endereco_local LIKE '%Diadema%';
```

Cadastre ou aprove apenas locais válidos pela interface administrativa. Não altere todos os estados em massa para preencher o mapa.

### 7.5 Não foi possível carregar os locais cadastrados

**Causa:** a consulta dos locais lançou erro, tratado pelo painel.

**Resolução:** procure `Erro ao carregar locais do mapa` no log. Confira a tabela `LocalEsp`, especialmente as colunas usadas na consulta, como `foto_local`. Compare com a auditoria de migrations. Esse erro não é resolvido trocando um token Mapbox.

### 7.6 Alguns locais não aparecem ou endereço não localizado

**Explicação:** cada endereço é geocodificado. O código recusa resultados fora dos limites definidos para Diadema e registra falhas de localização no Console.

**Resolução:** confira o endereço completo do local e as respostas da API de geocodificação. Corrija endereços incompletos, duplicados ou em cidade diferente pela administração. Verifique token, acesso ao serviço e eventual limite de requisições. Compare o número de locais retornados pelo banco com o número de marcadores; a falha de um endereço não comprova que todos os outros falharam.

### 7.7 Mapa não permite navegar ou localização não funciona

**Resolução:** se o status informar mapa fixado em um local, use “Desbloquear mapa”. A navegação também é limitada à região configurada de Diadema. A localização do dispositivo só é solicitada após clicar no controle; confira permissão do navegador e disponibilidade da localização. Um mapa pode funcionar mesmo sem autorização para usar a posição do usuário.

### 7.8 Pesquisa sem sugestões ou lista vazia

**Resolução:** digite pelo menos dois caracteres para sugestões. Confira a requisição a `app/views/painel/sugestoes-busca.php`, seu status e se a sessão permanece válida. Na lista do painel, confira filtro e termo: ela seleciona eventos ativos com data e horário futuros. “Nenhum evento encontrado” pode ser um resultado válido. Uma mensagem de falha ao carregar eventos exige consulta ao log e revisão do esquema.

<a id="eventos"></a>

## 8. Eventos, equipes e amizades

### 8.1 Não é possível criar evento com os dados informados

**Resolução:** atualize o formulário para renovar a sessão. Confira nome entre 3 e 100 caracteres, esporte e local existentes e data/horário futuros. O servidor valida novamente as opções, independentemente do filtro visual. Se o nome do local mudou ou foi desativado em outra sessão, escolha novamente a opção atual.

### 8.2 Modalidade indisponível ou ainda não confirmada para o local

**Mensagens:** local sem confirmação para o esporte ou modalidade ainda não confirmada pelo administrador.

**Resolução:** confira o vínculo em `Local_Esporte_Permitido`. Para esportes com `exige_confirmacao_local`, também é necessário o registro em `Local_Esporte_Confirmacao`. A confirmação deve refletir a validação administrativa real. Se uma instalação nova não possui os vínculos esperados, confira a importação dos dados iniciais.

### 8.3 Horário ocupado ou conflito de agenda

**Explicação:** a criação usa duração e intervalo de eventos no mesmo local; o código também bloqueia a linha do local durante a transação para coordenar solicitações simultâneas.

**Resolução:** consulte a agenda, escolha outro intervalo ou outro local. Atualize a página se outra pessoa acabou de criar um evento. A agenda do aplicativo representa registros internos do Zubbo e não substitui uma reserva oficial do espaço.

### 8.4 Evento lotado, vaga inválida ou vaga ocupada

**Resolução:** atualize a escalação e selecione uma vaga disponível no time correto. Se o evento estiver lotado, use a fila de espera oferecida pela interface. Não reutilize identificadores antigos de uma vaga. Antes de repetir após erro, confira se a solicitação ou participação já aparece.

### 8.5 Não é possível aprovar vaga, editar time ou excluir evento

**Causas possíveis:** usuário sem autorização para aquela ação, evento indisponível, solicitação já processada ou sessão expirada.

**Resolução:** entre com a conta organizadora ou administrativa apropriada, atualize os detalhes e confira o estado atual. Se houver HTTP 500 ou “Não foi possível salvar a alteração”, consulte `Erro de banco na escalação do evento` e faça a auditoria do esquema. Uma recusa de permissão não deve ser corrigida removendo a checagem do responsável.

### 8.6 Solicitação de amizade indisponível ou falha ao aceitar

**Resolução:** confirme que a solicitação ainda existe, pertence ao usuário atual e não foi aceita ou removida em outra aba. Confira se as contas continuam disponíveis e se há bloqueios. Atualize a página antes de repetir. Em falha interna, procure `Não foi possível aceitar a amizade` e o erro correspondente no log; confira o esquema antes de recriar vínculos.

### 8.7 Denúncia, sugestão ou alteração administrativa não é concluída

**Resolução:** confira os campos obrigatórios, a sessão, o estado do item e a permissão da conta. Inspecione a resposta do envio para distinguir dados recusados de falha do servidor. Em erro de banco, use a tabela ou coluna indicada no log para consultar a auditoria. Verifique o estado atual do registro antes de repetir uma ação administrativa.

<a id="conversas"></a>

## 9. Conversas e notificações internas

### 9.1 É necessário ter uma amizade aceita para enviar mensagens privadas

**Resolução:** conclua a aceitação da amizade entre as contas corretas. Uma solicitação pendente não atende ao requisito. Confira também se a conversa pertence ao usuário e se o destinatário está ativo. Atualize a lista após aceitar.

### 9.2 Não é possível enviar mensagens para este contato

**Causa prevista:** existência de bloqueio entre os usuários, em qualquer direção.

**Resolução:** verifique o estado na interface de bloqueios. Só o responsável pela decisão deve desfazê-la quando apropriado. Não remova o bloqueio diretamente como ajuste de infraestrutura.

### 9.3 Você não participa desta conversa

**Resolução:** confira se a conta atual participa do grupo e se o link corresponde à conversa certa. Após sair ou ser removido de um grupo, um endereço salvo pode continuar existindo sem conceder acesso. Entre novamente pelo fluxo autorizado do grupo.

### 9.4 Mensagem não aparece ou envio retorna para a lista

**Resolução:** verifique o conteúdo: o envio atual rejeita mensagens vazias ou com mais de 2000 caracteres e identificadores de conversa inválidos. Confira a resposta POST e depois a consulta de atualização. Se houver erro 500, confira banco, esquema e log. Para grupos, a gravação da mensagem e das notificações usa transação e pode ser revertida.

Antes de reenviar após interrupção de rede, procure a mensagem na conversa para evitar duplicidade. Uma falha de push posterior não implica, por si só, que o texto não foi salvo.

### 9.5 Mensagens ou contador não atualizam imediatamente

**Resolução:** confira as requisições periódicas em Rede e a sessão da conta. Resposta 401 ou uma tela de login indica necessidade de autenticação. O contador do painel consulta notificações em intervalos de 15 segundos e evita a consulta enquanto a página está oculta; não representa atualização instantânea por conexão contínua.

Se a busca de mensagens tiver erro de deadlock ou bloqueio, siga a seção do banco. Não reduza o intervalo de consulta como tentativa de corrigir um erro no servidor.

<a id="uploads"></a>

## 10. Fotos e arquivos

### 10.1 Imagem inválida ou foto de perfil não altera

**Regras confirmadas para foto de perfil:** arquivo maior que zero e até 4 MiB, tipos reais JPEG, PNG ou WebP e conteúdo reconhecido como imagem. Outros tipos de upload podem ter regras próprias.

**Resolução:** exporte uma imagem válida em um desses formatos e reduza o tamanho. Renomear a extensão não converte o conteúdo. Confira se o formulário envia o arquivo e se o PHP informa erro de upload. No perfil, alguns erros anteriores à validação retornam para a página sem mensagem detalhada.

### 10.2 Arquivo dentro do limite da aplicação ainda é recusado

**Causa possível:** limite do PHP ou do servidor abaixo do tamanho enviado.

**Resolução:** confira `file_uploads`, `upload_max_filesize`, `post_max_size`, diretório temporário e eventuais limites do servidor. O corpo POST inclui campos e metadados, por isso `post_max_size` precisa comportar mais que apenas os bytes da imagem. Reinicie o PHP/Apache após mudar o arquivo correto. Aumentar um limite do PHP não muda o limite de 4 MiB imposto pelo código de perfil.

Os códigos do PHP ajudam no diagnóstico: `1`, limite do PHP; `2`, limite informado pelo formulário; `3`, envio parcial; `4`, arquivo não enviado; `6`, diretório temporário ausente; `7`, falha de escrita; `8`, interrupção por extensão. Referência: [erros de upload do PHP](https://www.php.net/manual/en/features.file-upload.errors.php).

### 10.3 Não foi possível preparar o diretório ou salvar a imagem

**Resolução:** confira existência, permissão e espaço em `public/uploads/perfis/`, além da pasta temporária do PHP. A conta que executa Apache precisa criar e mover arquivos nesse destino. Em ambiente com armazenamento somente de leitura, use um destino persistente compatível com a aplicação. Ajuste a pasta necessária, sem liberar escrita indiscriminadamente em toda a instalação.

### 10.4 Não foi possível atualizar a foto

**Explicação:** no perfil, a imagem pode ser movida e a atualização do banco falhar; o código tenta remover a nova imagem nesse caso.

**Resolução:** procure `Falha ao atualizar foto de perfil` no log. Confira a conexão, o registro do usuário e a coluna `foto_user`. Resolva a causa SQL antes de reenviar. Uma permissão correta na pasta não resolve uma coluna ausente.

### 10.5 Foto some após reiniciar ou publicar nova versão

**Causas possíveis:** upload gravado em armazenamento temporário da hospedagem ou banco apontando para arquivo que não foi preservado.

**Resolução:** confira se o caminho salvo ainda existe no serviço que atende a requisição. Configure armazenamento persistente e sua associação ao caminho da aplicação. Em múltiplas instâncias, todas precisam acessar os arquivos necessários. Recoloque arquivos perdidos a partir de backup ou novo envio autorizado; alterar somente a URL não recupera o conteúdo.

<a id="push"></a>

## 11. Notificações push

### 11.1 Web Push indisponível: use HTTPS ou localhost

**Condições verificadas no frontend:** presença de Service Worker, PushManager, Notification e contexto seguro.

**Resolução:** teste em navegador compatível e contexto aceito. Um endereço HTTP por IP da rede local não é automaticamente equivalente a `localhost`. No iPhone, a interface do projeto também orienta a abrir o aplicativo instalado pela Tela de Início para ativar avisos. Confira o erro do navegador, pois o suporte pode variar por plataforma e versão.

### 11.2 O servidor ainda precisa configurar as chaves VAPID

**Resolução:** gere um par uma vez por ambiente, na pasta `-TCC-`:

```bat
php jobs/gerar-vapid.php
```

Armazene os valores em `ZUBBO_VAPID_PUBLIC_KEY`, `ZUBBO_VAPID_PRIVATE_KEY` e `ZUBBO_VAPID_SUBJECT`. Use o par correspondente e um assunto de contato apropriado. Reinicie o processo após configurar. A presença da chave pública na interface não comprova que o servidor possui a privada e o assunto.

Não gere outro par a cada inicialização. Depois de trocar o par deliberadamente, os dispositivos precisam renovar suas inscrições.

### 11.3 Notificações bloqueadas ou permissão recusada

**Resolução:** nas permissões do site no navegador, altere a permissão somente se desejar receber os avisos. Retorne às configurações do Zubbo e clique em Ativar. Confira também bloqueios do sistema operacional, modos de foco e permissões do aplicativo navegador. A aplicação não deve solicitar autorização automaticamente sem interação.

### 11.4 Falha ao consultar a inscrição ou registrar o Service Worker

**Resolução:** abra o endereço `sw.js` da instalação e confira se retorna JavaScript, sem redirecionar para login ou página HTML. Confira o escopo, a origem e o caminho do registro. Inspecione o erro na área de Service Workers. Em cópias em subpastas, um worker de outra instalação pode ter caminho diferente; renove apenas o registro afetado quando necessário.

### 11.5 Inscrição inválida ou servidor não configurado

**Códigos previstos:** `metodo`, `acao`, `inscricao`, `inscricao_invalida`, `servidor_nao_configurado` e `falha_servidor`.

**Resolução:** faça a ativação pela interface, em uma sessão atual. A inscrição precisa vir do navegador e conter endpoint HTTPS de fornecedor aceito e chaves válidas. Não monte valores fictícios. Se retornar `503`, confira VAPID. Se retornar `500`, confira o log, Composer e a tabela `PushSubscription`. Se retornar `403`, confira sessão e CSRF.

### 11.6 Aguarde 30 segundos ou teste não pode ser enviado

**Resolução:** o endpoint de teste limita solicitações a uma a cada 30 segundos. Aguarde antes de repetir. Se continuar, confira o estado do limitador, as chaves, a inscrição da conta atual e a resposta da requisição.

O endpoint pode aceitar a solicitação sem comprovar a exibição pelo sistema operacional. Confira o dispositivo e os logs de transporte.

### 11.7 Dependências Composer ausentes ou biblioteca não instalada

**Mensagens de log:** `Zubbo Web Push: dependencias Composer ausentes` e `biblioteca nao instalada`.

**Resolução:** execute `composer install` a partir de `-TCC-` e confira os requisitos de plataforma. A classe esperada é `Minishlink\WebPush\WebPush`. Se o build usa outra pasta raiz, corrija o diretório para instalar as dependências da aplicação correta.

### 11.8 Avisos internos funcionam, mas o push não chega

**Resolução:**

1. Confirme que o destinatário ativou avisos nesse dispositivo e está inscrito na conta correta.
2. Confira as três variáveis VAPID e as dependências do servidor.
3. Confira a tabela de inscrições e os registros `Zubbo Web Push`.
4. Investigue conectividade HTTPS, certificados e resposta do fornecedor.
5. Se uma inscrição expirou, o serviço pode removê-la; ative novamente no dispositivo.
6. Confira permissões do navegador e do sistema.

Não há fila persistente de reenvio nesta implementação. O evento principal pode permanecer salvo mesmo quando o envio push falha. Não repita mensagens ou aprovações para forçar uma notificação.

<a id="hospedagem"></a>

## 12. Hospedagem e deploy

### 12.1 Build não encontra composer.json, router.php ou arquivos da aplicação

**Resolução:** confirme a versão publicada e configure `/-TCC-` como diretório raiz do serviço quando usar a organização atual. Confira o log de build e a pasta em que o comando executa. O README na raiz externa não significa que essa raiz contém o código executável.

### 12.2 Aplicação não escuta a porta atribuída ou serviço retorna 502/503

**Resolução:** para a demonstração documentada, confira o comando:

```sh
php -S 0.0.0.0:$PORT router.php
```

O comando é para o ambiente de hospedagem que fornece `PORT`; não copie a sintaxe de variável diretamente para qualquer terminal Windows. Confira se o processo iniciou e permaneceu ativo. Se houver falha fatal antes de abrir a porta, resolva a mensagem correspondente no log. O servidor embutido é usado para demonstração; uma publicação definitiva exige servidor apropriado ao ambiente.

### 12.3 Healthcheck passa, mas login falha

**Explicação:** `/health.php` não testa banco, correio eletrônico ou conta de usuário.

**Resolução:** confira separadamente as variáveis do banco e a existência do esquema, depois faça o fluxo de login com conta de teste. Em hospedagem, `localhost` aponta para o próprio ambiente PHP, não automaticamente para um serviço MySQL separado. Use o host e a porta fornecidos para a conexão entre serviços.

### 12.4 Redirecionamento incorreto, sessão perdida ou erro de origem em HTTPS

**Resolução:** confira `ZUBBO_BASE_URL` com a origem pública HTTPS e `ZUBBO_BASE_PATH=/` quando a aplicação estiver na raiz. Confira os cabeçalhos encaminhados pelo intermediário e como o servidor identifica HTTPS. Não alterne domínio temporário e domínio definitivo no mesmo formulário. Após corrigir, faça novo login e abra um formulário novo.

### 12.5 Funciona no Windows e falha na hospedagem

**Resolução:** compare versão do PHP, extensões, dependências, variáveis e esquema. Confira capitalização de nomes, caminhos com barras, permissões e armazenamento persistente. Use os logs do ambiente com falha; a configuração local não é automaticamente transferida pelo Git.

### 12.6 Lentidão, estouro de memória ou tempo limite

**Resolução:** localize a requisição lenta em Rede e confira sua duração no servidor. Separe consulta SQL lenta, espera por bloqueio, envio SMTP, chamadas push e carregamento de recursos do mapa. Confira limites e recursos do processo. Ajuste consultas ou processamento antes de simplesmente elevar memória ou tempo limite. Se a resposta foi interrompida durante uma gravação, verifique o estado da ação antes de repetir.

<a id="testes"></a>

## 13. Testes e divergências entre versões

### 13.1 Auditoria informa incompatibilidade de esquema

**Resolução:** execute `php tests/audit-migrations.php` no terminal com as variáveis corretas. Relacione cada item recusado à migration consolidada correspondente no guia do banco. A ferramenta consulta o estado atual; ela não comprova o histórico de importações. Preserve a base existente antes de aplicar alterações.

### 13.2 Teste de banco recusa executar ou informa banco não vazio

**Explicação:** `tests/db-smoke.php` exige CLI, `ZUBBO_TEST_ALLOW_SCHEMA=1`, nome `zubbo_ci` ou `zubbo_test_*` e banco vazio. O teste cria estrutura e dados.

**Resolução:** use uma base descartável separada e autorizada. Não aponte para `app_zubbo` nem remova a proteção do teste. Não esvazie o banco real para atender ao requisito. Para verificar a base existente, use a auditoria de leitura.

### 13.3 Teste procura migrations antigas que não existem

**Falha corrigida nesta revisão:** o teste anterior ainda listava nomes como `005_notificacao_leitura.sql` e `013_web_push.sql`, substituídos por cinco migrations consolidadas. O teste atual instala a estrutura final sem reaplicar esses arquivos históricos.

**Resolução se a falha reaparecer em outra cópia:** atualize o teste e os scripts SQL para a mesma versão. Não copie migrations antigas de volta nem as aplique sobre a estrutura final apenas para satisfazer uma lista desatualizada. Valide em base descartável.

### 13.4 Comando documentado não encontra o script

**Exemplo identificado em documentação anterior:** `tests/db-upgrade-smoke.php` era citado, mas não existe na versão examinada. As instruções atuais de instalação e do banco não dependem desse arquivo.

**Resolução:** confira a versão do repositório e a existência do script. Atualize referências antigas e não interprete a ausência de uma ferramenta como falha do banco da aplicação. Para consultar o estado do esquema existente, use `tests/audit-migrations.php`.

### 13.5 environment-readiness falha no XAMPP

**Explicação:** o script examinado exige requisitos da demonstração remota, como PHP 8.4, endereço HTTPS na raiz e banco remoto com variáveis preenchidas. Esses critérios diferem dos padrões de desenvolvimento local.

**Resolução:** leia cada requisito antes de alterar a instalação. Não mude uma base local funcional para um host remoto só para fazer o teste passar. Use esse diagnóstico no ambiente para o qual foi escrito e escolha verificações locais compatíveis.

### 13.6 Teste de configuração do mapa passa, mas mapa não abre

**Explicação:** o teste de configuração verifica o tratamento de valores pelo código. Um token fictício de teste, como `pk.test.signature`, pode servir a esse teste e não autentica chamadas reais ao Mapbox.

**Resolução:** confira separadamente token real no Apache, recursos do navegador e respostas da API. Um teste unitário de configuração não comprova conexão com o provedor.

<a id="evidencias"></a>

## 14. Coleta de evidências e confirmação da solução

### 14.1 O que registrar quando o problema persistir

* Ação realizada e texto exato da mensagem.
* Data, horário e ambiente afetado.
* Caminho da página, método HTTP, status e sequência de redirecionamentos.
* Arquivo e linha citados no log, além do evento e do `request_id`, quando presentes.
* Versões de PHP e banco, resultado das extensões e nome do teste executado.
* Mudança recente relevante: importação, troca de pasta, senha, domínio, token ou deploy.

Remova senhas, cookies, códigos, links de recuperação, chaves privadas e dados pessoais antes de compartilhar os registros. Não envie capturas completas das variáveis do servidor.

Para ler apenas o final do registro local:

```powershell
Get-Content -LiteralPath C:\xampp\apache\logs\error.log -Tail 60
```

### 14.2 Critérios para considerar a falha resolvida

1. A ação original funciona com uma conta de teste apropriada.
2. A resposta HTTP e o conteúdo correspondem ao esperado, sem redirecionamento indevido.
3. O log não apresenta nova ocorrência da mesma falha naquele teste.
4. A gravação, quando necessária, aparece uma única vez no estado esperado.
5. A correção persiste após recarregar a página e reiniciar somente o serviço que recebeu mudança de configuração.
6. Recursos relacionados continuam funcionando: login, navegação e a função diretamente afetada.

### 14.3 Limites das verificações já feitas nesta instalação

Em verificações anteriores de 25/10/2026, a pasta externa retornou 403, a entrada correta e a tela de login retornaram 200, e a conexão PHP conseguiu executar `SELECT 1` e uma consulta sem registros às colunas do login. Também foi identificada a ausência da configuração de token Mapbox nos locais consultados naquele momento.

Esses resultados são registros pontuais. Não equivalem a uma reinstalação completa, não confirmam a validade atual de um token e não garantem todos os fluxos de cadastro, mensagens, eventos ou hospedagem. Este documento não executou cenários de falha nem modificou dados para reproduzi-los.

### 14.4 Arquivos usados como base desta documentação

As regras específicas foram conferidas em `config/database.php`, `config/security.php`, `config/map.php`, `config/mail.php`, `router.php`, `app/middleware/auth.php`, páginas de autenticação, painel, eventos, conversas, upload de perfil, endpoints push, `app/services/PushService.php`, `public/js/push.js` e ferramentas de teste. As referências externas estão junto aos assuntos correspondentes.
