# Zubbo App

Aplicação web desenvolvida como Trabalho de Conclusão de Curso (TCC) para aproximar pessoas por meio do esporte amador e comunitário.

O Zubbo permite criar contas, encontrar pessoas e locais esportivos, organizar eventos, solicitar vagas em times, conversar com outros usuários e utilizar recursos de administração e moderação.

> Este README é a documentação principal do projeto. Para informações específicas de segurança, consulte [-TCC-/SECURITY.md](-TCC-/SECURITY.md).

---

## Sumário

- [Sobre o projeto](#sobre-o-projeto)
- [Principais funcionalidades](#principais-funcionalidades)
- [Tecnologias utilizadas](#tecnologias-utilizadas)
- [Estrutura do repositório](#estrutura-do-repositório)
- [Pré-requisitos](#pré-requisitos)
- [Instalação local com XAMPP](#instalação-local-com-xampp)
- [Configuração do Apache e variáveis de ambiente](#configuração-do-apache-e-variáveis-de-ambiente)
- [Banco de dados](#banco-de-dados)
- [Instalação nova do banco](#instalação-nova-do-banco)
- [Atualização de um banco antigo](#atualização-de-um-banco-antigo)
- [Principais tabelas](#principais-tabelas)
- [Criação de administrador](#criação-de-administrador)
- [Fluxos principais da aplicação](#fluxos-principais-da-aplicação)
- [Segurança](#segurança)
- [Uploads](#uploads)
- [E-mail e SMTP](#e-mail-e-smtp)
- [Mapa e locais esportivos](#mapa-e-locais-esportivos)
- [GitHub Actions](#github-actions)
- [Comandos Git úteis](#comandos-git-úteis)
- [Solução de problemas](#solução-de-problemas)
- [Arquivos importantes](#arquivos-importantes)

---

## Sobre o projeto

O Zubbo foi criado com foco em encontros esportivos comunitários, inclusão social e facilidade para encontrar pessoas e locais para praticar esportes.

A aplicação utiliza uma arquitetura PHP tradicional, com páginas e endpoints PHP, banco de dados MySQL/MariaDB e interface em HTML, CSS e JavaScript.

O código principal da aplicação fica dentro da pasta:

~~~text
-TCC-/
~~~

A pasta .github/ fica na raiz do repositório porque é utilizada pelo GitHub Actions e não faz parte da URL da aplicação.

---

## Principais funcionalidades

Atualmente o projeto possui recursos para:

- cadastro de usuário;
- verificação de e-mail;
- login e logout;
- recuperação e redefinição de senha;
- seleção de esportes do usuário;
- perfil e foto de perfil;
- amizades e solicitações de amizade;
- pesquisa de perfis;
- conversas privadas;
- grupos e chat em grupo;
- notificações;
- cadastro e listagem de eventos;
- detalhes de eventos;
- solicitação de vaga em eventos;
- divisão de participantes entre Time 1 e Time 2;
- aprovação, recusa e remoção de participantes pelo organizador;
- locais esportivos;
- mapa de locais;
- denúncias;
- sugestões de novos esportes;
- painel administrativo;
- suspensão e banimento de usuários;
- moderação de eventos, locais, denúncias e sugestões;
- histórico de ações administrativas.

---

## Tecnologias utilizadas

### Backend

- PHP
- PDO
- MySQL / MariaDB
- PHPMailer 7

### Frontend

- HTML
- CSS
- JavaScript

### Ambiente local

- XAMPP
- Apache
- MySQL/MariaDB
- phpMyAdmin

### Serviços externos

- SMTP para envio de e-mails;
- Mapbox para mapa e geocodificação.

### Versionamento e automação

- Git
- GitHub
- GitHub Actions

A dependência PHP principal está registrada em -TCC-/composer.json:

~~~text
phpmailer/phpmailer ^7.1
~~~

---

## Estrutura do repositório

~~~text
Zubbo-App/
├── .github/
│   └── workflows/
│       └── php-lint.yml
│
├── README.md
│
└── -TCC-/
    ├── .gitignore
    ├── README.md
    ├── SECURITY.md
    ├── composer.json
    ├── composer.lock
    │
    ├── app/
    │   ├── middleware/
    │   ├── services/
    │   └── views/
    │       ├── admin/
    │       ├── auth/
    │       ├── chats/
    │       ├── eventos/
    │       ├── includes/
    │       ├── notificacoes/
    │       ├── painel/
    │       ├── perfil/
    │       └── pesquisa/
    │
    ├── config/
    │   ├── database.php
    │   ├── mail.php
    │   └── security.php
    │
    ├── database/
    │   ├── diagrams/
    │   ├── migrations/
    │   ├── scripts/
    │   └── tools/
    │
    ├── public/
    │   ├── css/
    │   ├── font/
    │   ├── imagem/
    │   ├── js/
    │   ├── uploads/
    │   ├── index.php
    │   └── reset-password.php
    │
    └── vendor/
~~~

| Pasta | Função |
|---|---|
| .github/workflows | automações do GitHub Actions |
| -TCC-/app/middleware | autenticação e validações compartilhadas |
| -TCC-/app/services | serviços reutilizáveis |
| -TCC-/app/views | páginas e endpoints PHP |
| -TCC-/config | banco, e-mail e segurança |
| -TCC-/database | schema, scripts, migrations e ferramentas |
| -TCC-/public | CSS, JS, imagens, uploads e páginas públicas |
| -TCC-/vendor | dependências instaladas pelo Composer |

---

## Pré-requisitos

Para executar localmente:

1. Windows com XAMPP instalado;
2. Apache;
3. MySQL/MariaDB;
4. PHP compatível com o projeto;
5. Composer recomendado para instalar dependências;
6. Git para clonar e atualizar o projeto.

O GitHub Actions atualmente verifica a aplicação com PHP 8.2.

---

# Instalação local com XAMPP

## 1. Clonar o repositório

Para manter a estrutura esperada, clone dentro de htdocs:

~~~bat
cd C:\xampp\htdocs
git clone https://github.com/silvestrevini11/Zubbo-App.git
~~~

A estrutura ficará:

~~~text
C:\xampp\htdocs\Zubbo-App\-TCC-
~~~

## 2. Iniciar o XAMPP

Inicie:

~~~text
Apache
MySQL
~~~

## 3. Instalar dependências PHP

Abra o terminal na pasta -TCC-:

~~~bat
cd C:\xampp\htdocs\Zubbo-App\-TCC-
composer install
~~~

## 4. Acessar a aplicação

Se o repositório inteiro estiver em htdocs:

~~~text
http://localhost/Zubbo-App/-TCC-/public/index.php
~~~

Se a pasta -TCC- estiver diretamente em htdocs:

~~~text
http://localhost/-TCC-/public/index.php
~~~

---

# Configuração do Apache e variáveis de ambiente

Credenciais e configurações locais não devem ser colocadas diretamente no código PHP nem enviadas ao GitHub.

No XAMPP, o projeto pode receber as variáveis pelo Apache.

Arquivo:

~~~text
C:\xampp\apache\conf\httpd.conf
~~~

Exemplo seguro, com placeholders:

~~~apache
SetEnv ZUBBO_SMTP_USER "seu-email@gmail.com"
SetEnv ZUBBO_SMTP_PASSWORD "SUA_SENHA_DE_APP"
SetEnv ZUBBO_SMTP_HOST "smtp.gmail.com"
SetEnv ZUBBO_SMTP_PORT "587"
SetEnv ZUBBO_SMTP_ENCRYPTION "starttls"
SetEnv ZUBBO_MAIL_FROM "seu-email@gmail.com"

SetEnv ZUBBO_BASE_URL "http://localhost/Zubbo-App/-TCC-"
~~~

Depois de alterar o httpd.conf:

1. salve;
2. pare o Apache;
3. inicie o Apache novamente.

> Nunca coloque a senha SMTP real neste README, em arquivos PHP ou em commits.

### Variáveis reconhecidas

| Variável | Uso | Observação |
|---|---|---|
| ZUBBO_SMTP_USER | usuário SMTP | necessária para e-mail |
| ZUBBO_SMTP_PASSWORD | senha SMTP | necessária para e-mail |
| ZUBBO_SMTP_HOST | servidor SMTP | padrão smtp.gmail.com |
| ZUBBO_SMTP_PORT | porta SMTP | padrão 587 |
| ZUBBO_SMTP_ENCRYPTION | auto, starttls, smtps ou none | opcional |
| ZUBBO_MAIL_FROM | remetente | opcional |
| ZUBBO_BASE_URL | URL absoluta usada nos e-mails | recomendado |
| ZUBBO_BASE_PATH | caminho web base | opcional |
| ZUBBO_DB_HOST | host do banco | opcional |
| ZUBBO_DB_USER | usuário do banco | opcional |
| ZUBBO_DB_PASSWORD | senha do banco | opcional |
| ZUBBO_DB_PORT | porta do banco | opcional |
| ZUBBO_DB_NAME | nome do banco | opcional |

### Padrões locais do banco

Se nenhuma variável de banco for definida, config/database.php usa:

~~~text
Host: localhost
Porta: 3306
Banco: app_zubbo
Usuário: root
Senha: vazia
~~~

Esses valores são pensados para um ambiente local padrão do XAMPP.

---

# Banco de dados

O banco principal se chama:

~~~text
app_zubbo
~~~

Os arquivos ficam em:

~~~text
-TCC-/database/
~~~

### database/scripts/

Scripts de criação, dados iniciais, consultas e compatibilidade com versões anteriores.

### database/migrations/

Atualizações estruturais para bancos que já existiam antes do schema atual.

### database/tools/

Ferramentas executadas pelo terminal.

---

# Instalação nova do banco

Se está configurando o projeto em um computador novo ou quer criar o banco do zero, use esta ordem.

## Passo 1 — iniciar MySQL

Inicie Apache e MySQL pelo XAMPP.

## Passo 2 — abrir phpMyAdmin

~~~text
http://localhost/phpmyadmin
~~~

## Passo 3 — executar o schema completo

Importe:

~~~text
-TCC-/database/scripts/criacaotables.sql
~~~

Esse arquivo:

- cria o banco app_zubbo;
- usa charset utf8mb4;
- cria todas as tabelas atuais;
- cria chaves estrangeiras;
- cria índices;
- já contém a estrutura atual de Administrador;
- já contém Recuperacao_Senha;
- já contém Solicitacao_Vaga_Evento;
- já contém a restrição de vagas confirmadas;
- já contém a restrição de locais duplicados.

## Passo 4 — inserir dados iniciais

Depois execute:

~~~text
-TCC-/database/scripts/insertstables.sql
~~~

Esse arquivo cadastra os esportes:

- Futebol
- Basquete
- Vôlei
- Futsal
- Corrida
- Handebol

Também cadastra locais esportivos aprovados utilizados pelo projeto.

## Ordem resumida para banco novo

~~~text
1. database/scripts/criacaotables.sql
2. database/scripts/insertstables.sql
~~~

### Importante

Em um banco criado pelo criacaotables.sql atual:

- não é necessário executar migration 002;
- não é necessário executar migration 003;
- não execute add_recuperacao_senha.sql;
- não execute add_solicitacao_vaga_evento.sql;
- não execute upgrade_solicitacao_vaga_evento.sql.

Esses arquivos existem para atualizar bancos antigos.

---

# Atualização de um banco antigo

Antes de qualquer alteração, faça backup.

## Backup pelo phpMyAdmin

~~~text
phpMyAdmin
→ app_zubbo
→ Exportar
→ Método rápido
→ SQL
→ Exportar
~~~

## 1. Recuperação de senha

Se não existe a tabela Recuperacao_Senha:

~~~text
database/scripts/add_recuperacao_senha.sql
~~~

Se a tabela já existe, não execute esse script.

## 2. Solicitações de vaga

### Se Solicitacao_Vaga_Evento não existe

Execute:

~~~text
database/scripts/add_solicitacao_vaga_evento.sql
~~~

### Se Solicitacao_Vaga_Evento existe no formato antigo

Execute:

~~~text
database/scripts/upgrade_solicitacao_vaga_evento.sql
~~~

Esse upgrade adiciona/adapta a lógica de:

- Time 1;
- Time 2;
- número da vaga;
- status da solicitação;
- trava de vaga confirmada.

> Não execute o upgrade em uma tabela que já possui a estrutura atual.

## 3. Administrador relacionado ao usuário

Execute:

~~~text
database/migrations/002_admin_user_relation.sql
~~~

Ela:

- adiciona id_user à tabela Administrador quando necessário;
- tenta vincular registros antigos pelo e-mail;
- adiciona índice único;
- adiciona a chave estrangeira.

## 4. Deduplicação de locais

Execute:

~~~text
database/migrations/003_deduplicate_locations.sql
~~~

Ela:

- identifica locais repetidos por nome + endereço;
- atualiza referências em Evento;
- atualiza referências em Acao_Administrativa;
- remove duplicatas;
- cria uq_local_nome_endereco.

## Ordem recomendada para banco antigo

~~~text
1. Fazer backup

2. add_recuperacao_senha.sql
   somente se Recuperacao_Senha não existir

3. add_solicitacao_vaga_evento.sql
   somente se Solicitacao_Vaga_Evento não existir

OU

3. upgrade_solicitacao_vaga_evento.sql
   somente se Solicitacao_Vaga_Evento existir no formato antigo

4. migrations/002_admin_user_relation.sql

5. migrations/003_deduplicate_locations.sql
~~~

---

# Principais tabelas

## Usuários e autenticação

| Tabela | Função |
|---|---|
| Usuario | contas |
| Administrador | permissão administrativa |
| Usuario_Esporte | esportes escolhidos |
| Verificacao_Email | estrutura de verificação |
| Recuperacao_Senha | tokens de recuperação |

## Amizades

| Tabela | Função |
|---|---|
| Solicitacao_Amizade | pedidos de amizade |
| Amizade | amizades aceitas |

## Conversas

| Tabela | Função |
|---|---|
| Conversa | conversa privada, grupo ou comunidade |
| Participantes_Conversa | usuários da conversa |
| Grupo | dados dos grupos |
| Comunidade | comunidades |
| Mensagem | mensagens |
| Notificacao | notificações |

## Esportes, equipes e eventos

| Tabela | Função |
|---|---|
| Esporte | modalidades |
| Equipe | equipes |
| ParticipantesEquipe | membros de equipes |
| LocalEsp | locais esportivos |
| Evento | eventos |
| EquipesEvento | relação equipe/evento |
| Lista_Evento | presença confirmada |
| Solicitacao_Vaga_Evento | pedido, time e vaga |

## Administração e comunidade

| Tabela | Função |
|---|---|
| Sugestao_Esporte | sugestões |
| Voto_Sugestao | votos |
| Denuncia | denúncias |
| Acao_Administrativa | histórico administrativo |

### Consultas de verificação

O arquivo:

~~~text
database/scripts/querytables.sql
~~~

possui consultas SELECT para várias tabelas durante testes e desenvolvimento.

---

# Criação de administrador

O Zubbo não depende de uma conta administrativa padrão com senha fixa.

O administrador utiliza uma conta normal de Usuario.

## 1. Crie a conta normalmente

Cadastre e ative a conta pelo próprio sistema.

## 2. Abra o terminal na pasta -TCC-

~~~bat
cd C:\xampp\htdocs\Zubbo-App\-TCC-
~~~

## 3. Execute

Se PHP estiver no PATH:

~~~bat
php database/tools/create-admin.php
~~~

Ou usando o PHP do XAMPP:

~~~bat
C:\xampp\php\php.exe database\tools\create-admin.php
~~~

Digite o e-mail da conta ativa solicitada.

Depois, o administrador entra pelo mesmo login dos usuários. A aplicação identifica a permissão administrativa e direciona/libera o painel administrativo.

---

# Fluxos principais da aplicação

## Cadastro

~~~text
Cadastro
  ↓
Dados ficam temporariamente na sessão
  ↓
Código de verificação é enviado por e-mail
  ↓
Usuário confirma o código
  ↓
Escolhe esportes
  ↓
Conta é gravada
  ↓
Login
~~~

## Login

~~~text
E-mail + senha
  ↓
Validação da senha
  ↓
Verificação do status da conta
  ↓
Regeneração da sessão
  ↓
Usuário comum → painel
Administrador → painel administrativo
~~~

## Recuperação de senha

~~~text
E-mail informado
  ↓
Token aleatório criado
  ↓
Hash do token salvo no banco
  ↓
Link enviado por SMTP
  ↓
Token e validade conferidos
  ↓
Senha alterada
  ↓
Token invalidado
~~~

## Eventos e vagas

~~~text
Usuário cria evento
  ↓
Escolhe esporte e local aprovado
  ↓
Outro usuário solicita vaga
  ↓
Organizador analisa
  ↓
Aprovação define time e número da vaga
  ↓
Participante aparece na escalação
~~~

A tabela Solicitacao_Vaga_Evento possui uma restrição para evitar duas confirmações na mesma combinação:

~~~text
evento + time + número da vaga
~~~

## Grupos

~~~text
Usuário cria grupo
  ↓
Seleciona participantes
  ↓
Conversa do tipo grupo é criada
  ↓
Usuários entram em Participantes_Conversa
  ↓
Mensagens usam essa conversa
~~~

---

# Segurança

Veja também [-TCC-/SECURITY.md](-TCC-/SECURITY.md).

Entre as proteções implementadas estão:

- password_hash para senhas;
- password_verify no login;
- PDO com prepared statements nativos;
- cookies de sessão HttpOnly;
- SameSite=Lax;
- Secure quando HTTPS está ativo;
- headers de segurança;
- CSRF nos fluxos protegidos;
- validação de origem em POST;
- rate limit em fluxos de autenticação;
- verificação de usuário suspenso ou banido;
- token aleatório na recuperação de senha;
- validação de MIME e tamanho em uploads;
- bloqueio de PHP na pasta de uploads;
- SMTP por variável de ambiente;
- autorização do painel administrativo;
- transações e travas de banco em vagas de eventos.

## Regra para segredos

Nunca commite:

- senha SMTP;
- senha de banco de produção;
- chaves privadas;
- tokens administrativos;
- arquivos locais com credenciais.

Se uma credencial já apareceu no histórico do Git, removê-la do arquivo atual não é suficiente: revogue e substitua a credencial.

---

# Uploads

Uploads de usuários ficam em áreas como:

~~~text
-TCC-/public/uploads/perfis/
-TCC-/public/uploads/grupos/
~~~

Eles não devem ser versionados.

O .gitignore ignora os arquivos de upload.

A proteção:

~~~text
-TCC-/public/uploads/.htaccess
~~~

bloqueia a execução de extensões PHP na pasta de uploads.

---

# E-mail e SMTP

A configuração está centralizada em:

~~~text
-TCC-/config/mail.php
~~~

O projeto usa PHPMailer.

Credenciais são lidas do ambiente:

~~~text
ZUBBO_SMTP_USER
ZUBBO_SMTP_PASSWORD
~~~

Recursos que dependem do SMTP:

- verificação do cadastro;
- recuperação de senha.

Para Gmail, utilize uma senha de aplicativo quando aplicável e mantenha essa senha apenas no ambiente do servidor/computador.

---

# Mapa e locais esportivos

O painel possui integração com Mapbox para mapa e geocodificação.

A tabela relacionada é LocalEsp.

Campos principais:

- nome;
- endereço;
- tipo;
- status;
- criador.

O schema atual possui restrição única por:

~~~text
nome_local + endereco_local
~~~

para reduzir locais duplicados.

---

# GitHub Actions

Workflow:

~~~text
.github/workflows/php-lint.yml
~~~

Executa em:

- push;
- pull request.

Usa PHP 8.2 e executa lint nos PHP dentro de -TCC-, ignorando vendor.

A pasta .github deve permanecer na raiz:

~~~text
Zubbo-App/
├── .github/
└── -TCC-/
~~~

---

# Comandos Git úteis

## Atualizar main

~~~bash
git checkout main
git pull origin main
~~~

## Ver alterações

~~~bash
git status
~~~

## Criar branch

~~~bash
git checkout -b nome-da-branch
~~~

## Adicionar alterações

~~~bash
git add .
~~~

## Criar commit

~~~bash
git commit -m "descrição da alteração"
~~~

## Enviar branch

~~~bash
git push -u origin nome-da-branch
~~~

---

# Solução de problemas

## CSS ou imagens não carregam

Confirme onde o projeto está dentro de htdocs.

### Repositório completo dentro de htdocs

~~~text
C:\xampp\htdocs\Zubbo-App\-TCC-
~~~

URL:

~~~text
http://localhost/Zubbo-App/-TCC-/
~~~

Configuração recomendada:

~~~apache
SetEnv ZUBBO_BASE_URL "http://localhost/Zubbo-App/-TCC-"
~~~

### Apenas -TCC- dentro de htdocs

~~~text
C:\xampp\htdocs\-TCC-
~~~

URL:

~~~text
http://localhost/-TCC-/
~~~

Configuração:

~~~apache
SetEnv ZUBBO_BASE_URL "http://localhost/-TCC-"
~~~

---

## Erro de conexão com MySQL

Confira:

1. MySQL está iniciado;
2. app_zubbo existe;
3. criacaotables.sql foi executado;
4. usuário e senha estão corretos;
5. a porta é normalmente 3306.

No XAMPP padrão:

~~~text
Usuário: root
Senha: vazia
~~~

---

## Cadastro funciona, mas e-mail não chega

Confira:

1. ZUBBO_SMTP_USER;
2. ZUBBO_SMTP_PASSWORD;
3. host;
4. porta;
5. criptografia;
6. se o Apache foi reiniciado.

Para Gmail com STARTTLS:

~~~text
Host: smtp.gmail.com
Porta: 587
Encryption: starttls
~~~

Para SMTPS, normalmente é usada a porta 465.

---

## Recuperação gera link errado

Defina ZUBBO_BASE_URL conforme o local real da aplicação e reinicie o Apache.

Exemplo:

~~~apache
SetEnv ZUBBO_BASE_URL "http://localhost/Zubbo-App/-TCC-"
~~~

---

## Tabela não existe

Instalação nova:

~~~text
database/scripts/criacaotables.sql
~~~

Banco antigo: consulte [Atualização de um banco antigo](#atualização-de-um-banco-antigo).

---

## Administrador não entra no painel

Confira:

1. a conta existe em Usuario;
2. status_user é ativo;
3. migration 002 foi aplicada se o banco é antigo;
4. a conta foi promovida com create-admin.php.

O administrador usa o mesmo login dos usuários comuns.

---

# Arquivos importantes

| Arquivo | Função |
|---|---|
| README.md | documentação principal |
| -TCC-/SECURITY.md | segurança |
| -TCC-/config/database.php | conexão PDO e status do usuário |
| -TCC-/config/security.php | sessão, CSRF, headers, URLs e rate limit |
| -TCC-/config/mail.php | SMTP |
| -TCC-/app/middleware/auth.php | autenticação |
| -TCC-/app/services/AdminService.php | administração |
| -TCC-/app/services/LocalService.php | locais aprovados |
| -TCC-/database/scripts/criacaotables.sql | schema completo |
| -TCC-/database/scripts/insertstables.sql | dados iniciais |
| -TCC-/database/scripts/querytables.sql | consultas de teste |
| -TCC-/database/migrations/002_admin_user_relation.sql | atualização de admin |
| -TCC-/database/migrations/003_deduplicate_locations.sql | locais duplicados |
| -TCC-/database/tools/create-admin.php | promover administrador |
| .github/workflows/php-lint.yml | lint automático |

---

## Boas práticas para desenvolvimento

- não colocar credenciais reais no Git;
- fazer backup antes de migrations;
- em instalação nova, usar o criacaotables.sql atual;
- não versionar fotos enviadas pelos usuários;
- testar cadastro, login, e-mail, senha, eventos, chats e admin após mudanças estruturais;
- conferir o GitHub Actions antes de mergear mudanças grandes;
- preferir branches para alterações de funcionalidade ou segurança.

---

## Projeto acadêmico

Este repositório faz parte de um Trabalho de Conclusão de Curso de Desenvolvimento de Sistemas.

O objetivo do Zubbo é utilizar tecnologia e esporte como ferramentas para facilitar encontros esportivos comunitários e fortalecer a participação social em ambientes mais acessíveis e organizados.
