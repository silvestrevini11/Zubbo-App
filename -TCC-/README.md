# Zubbo — Documentação técnica e funcionamento

Este README documenta **como o aplicativo funciona, como executar o projeto e como manter os ambientes**. Ele fica dentro de **-TCC-/**, junto ao código da aplicação.

Para conhecer o tema, a proposta, os objetivos, a instituição e a equipe, consulte o [README de apresentação do repositório](../README.md).

> **Situação:** aplicação web em PHP/MySQL, com ambiente de demonstração no Railway. O APK Android é uma etapa futura, não um artefato já produzido. A demonstração funcional não substitui auditoria independente de segurança.

## Índice

1. [Arquitetura e tecnologias](#1-arquitetura-e-tecnologias)
2. [Estrutura das pastas](#2-estrutura-das-pastas)
3. [Principais funcionalidades e fluxos](#3-principais-funcionalidades-e-fluxos)
4. [Requisitos e execução local](#4-requisitos-e-execução-local)
5. [Variáveis e serviços externos](#5-variáveis-e-serviços-externos)
6. [Banco de dados: ordem de criação](#6-banco-de-dados-ordem-de-criação)
7. [Atualização de banco existente](#7-atualização-de-banco-existente)
8. [Administrador e moderação](#8-administrador-e-moderação)
9. [Railway e modo de demonstração](#9-railway-e-modo-de-demonstração)
10. [Segurança e privacidade](#10-segurança-e-privacidade)
11. [Logs e testes automatizados](#11-logs-e-testes-automatizados)
12. [APK Android: integração prevista](#12-apk-android-integração-prevista)
13. [Problemas comuns e referências](#13-problemas-comuns-e-referências)

## 1. Arquitetura e tecnologias

O Zubbo utiliza arquitetura web tradicional:

~~~text
Navegador / futuro aplicativo Android com WebView
                 |
               HTTPS
                 |
         PHP (páginas e endpoints)
                 |
           PDO / MySQL
~~~

- **Frontend:** HTML, CSS e JavaScript, com adaptação para telas móveis.
- **Backend:** PHP, sessões de autenticação e consultas preparadas via PDO.
- **Banco:** MySQL; no desenvolvimento local, MySQL/MariaDB via XAMPP.
- **E-mails:** Resend por API HTTPS ou SMTP com PHPMailer, configurados no ambiente.
- **Mapa:** Mapbox, integrado à listagem e à pesquisa de locais.
- **Ferramentas:** Git, GitHub, Composer, GitHub Actions, XAMPP, Railway e DBeaver.
- **Versão de validação automatizada:** PHP **8.4** e MySQL **8.4**.

A aplicação em PHP **não roda dentro do APK**: futuramente, o Android abrirá a interface hospedada no servidor, que continuará processando o PHP e acessando o MySQL.

## 2. Estrutura das pastas

~~~text
Zubbo-App/
├── README.md                      # Apresentação acadêmica
├── .github/workflows/             # Testes automatizados
└── -TCC-/
    ├── README.md                  # Este guia técnico
    ├── RAILWAY_TEST.md            # Deploy e configuração da demo
    ├── SECURITY.md                # Segurança
    ├── router.php                 # Bloqueia arquivos internos no Railway
    ├── index.php / health.php     # Entrada e verificação de runtime
    ├── app/
    │   ├── middleware/           # Validação de autenticação
    │   ├── services/             # Regras compartilhadas
    │   └── views/
    │       ├── auth/             # Cadastro, login, e-mail e senha
    │       ├── perfil/           # Perfis, amizades e denúncias
    │       ├── painel/           # Painel inicial, mapa e sugestões
    │       ├── pesquisa/         # Pesquisa de perfis, locais, eventos
    │       ├── chats/            # Mensagens e grupos
    │       ├── notificacoes/     # Notificações
    │       ├── eventos/          # Eventos, equipes e vagas
    │       └── admin/            # Painel administrativo
    ├── config/                    # database.php, security.php,
    │                              # mail.php e logger.php
    ├── database/
    │   ├── scripts/              # Criação do schema e dados iniciais
    │   ├── migrations/           # Atualizações de bancos antigos
    │   ├── tools/                # Criação controlada de admin
    │   └── diagrams/             # Modelagem do banco
    ├── public/                    # CSS, JavaScript, imagens e uploads
    ├── tests/                     # Segurança e integração MySQL
    ├── docs/                      # Materiais e observabilidade
    └── composer.json              # Dependências PHP
~~~

A pasta **.github** deve permanecer na **raiz do repositório** para os workflows serem encontrados pelo GitHub.

## 3. Principais funcionalidades e fluxos

| Módulo | O que o usuário consegue fazer | Arquivos principais |
|---|---|---|
| Autenticação | Criar conta, confirmar e-mail, entrar, sair e recuperar senha | app/views/auth/ |
| Perfil | Editar dados, selecionar esportes e adicionar imagem | app/views/perfil/ |
| Privacidade | Confirmar alteração de e-mail, trocar senha e desativar conta | app/views/perfil/confirmar-email.php e atualizar-dados.php |
| Amizades | Solicitar, aceitar e visualizar amizades | app/views/notificacoes/ e app/views/perfil/ |
| Chat | Conversas privadas, grupos, envio e leitura de mensagens | app/views/chats/ |
| Pesquisa | Encontrar pessoas, locais e eventos | app/views/pesquisa/ |
| Mapa | Visualizar locais esportivos e centralizar em um local escolhido | app/views/painel/Painel-inicial.php |
| Eventos | Criar encontros, solicitar vaga, aprovar participantes e montar times | app/views/eventos/ |
| Denúncias | Denunciar usuários, eventos, locais ou relatar situação geral | app/views/perfil/fazer-denuncia.php |
| Administração | Moderar usuários, locais, eventos, denúncias e sugestões | app/views/admin/ |

### Cadastro e login

~~~text
Cadastro com dados básicos
    → código de confirmação de e-mail
    → escolha de esportes
    → criação da conta no MySQL
    → login com e-mail e senha
    → painel de usuário ou administrador
~~~

As senhas são armazenadas como hashes; o administrador deve estar associado ao usuário pelo campo **Administrador.id_user**, e não somente pelo e-mail.

### Alteração de e-mail e senha

- Mudança de **e-mail**: exige senha atual e confirmação por código temporário no endereço novo.
- Mudança de **senha**: exige senha atual e pelo menos oito caracteres; versões antigas da sessão são invalidadas.
- **Desativação de conta**: anonimiza dados cadastrais básicos e impede login; registros vinculados à moderação podem permanecer. O projeto ainda precisa formalizar a política definitiva de retenção.

### Eventos e vagas

~~~text
Organizador cria evento
    → define modalidade, local aprovado e data
    → participante solicita vaga em um time
    → organizador aprova/recusa
    → tabela de presença é atualizada
~~~

A estrutura de **Solicitacao_Vaga_Evento** possui travas/índices para evitar duas confirmações para a mesma combinação evento + time + vaga. Em operações de mudança, há transações e verificações de permissão.

### Conversas e notificações

Mensagens privadas e de grupos ficam em **Conversa**, **Mensagem**, **Participantes_Conversa** e **Notificacao**. Há indicação de mensagens não lidas. O chat usa consulta periódica; essa estratégia é suficiente para o protótipo, mas precisa de paginação e consulta incremental em uma versão com muitos usuários.

### Mapa e pesquisa

O Mapbox carrega locais aprovados, com busca de endereço e apresentação de marcadores. O usuário pode pesquisar local, selecionar uma sugestão, centralizar e fixar o mapa; eventos pesquisados abrem os detalhes. **Evolução prevista:** persistir latitude/longitude no banco para evitar geocodificação repetida.

## 4. Requisitos e execução local

Pré-requisitos:

- Git e XAMPP com Apache e MySQL/MariaDB;
- PHP compatível com o projeto, preferencialmente **8.4**, com extensões necessárias (PDO MySQL, mbstring e fileinfo);
- Composer para instalar dependências;
- navegador atualizado.

### 4.1 Clonar

No Windows:

~~~bat
cd C:\xampp\htdocs
git clone -b teste-railway https://github.com/silvestrevini11/Zubbo-App.git
cd Zubbo-App\-TCC-
composer install
~~~

A branch padrão do GitHub pode conter uma versão diferente da demo. Por isso, para reproduzir esta documentação, **utilize teste-railway**.

### 4.2 Preparar o banco

No phpMyAdmin, crie um banco local, por exemplo **app_zubbo**, com charset utf8mb4; selecione esse banco e execute os arquivos indicados na seção 6. O script de criação **não cria nem seleciona um banco por nome**: ele cria as tabelas dentro do banco já selecionado.

### 4.3 Iniciar e abrir

No XAMPP, inicie Apache e MySQL. Se todo o repositório está dentro de htdocs, abra:

~~~text
http://localhost/Zubbo-App/-TCC-/public/index.php
~~~

Alternativamente, para testar o roteador que protege arquivos internos (ajuste as variáveis de banco antes):

~~~bat
cd C:\xampp\htdocs\Zubbo-App\-TCC-
set ZUBBO_BASE_PATH=/
C:\xampp\php\php.exe -S 127.0.0.1:8080 router.php
~~~

Abra **http://127.0.0.1:8080/**. Não exponha o servidor embutido do PHP diretamente à internet como uma produção definitiva.

## 5. Variáveis e serviços externos

As configurações sensíveis ficam **fora do Git**, nas variáveis de ambiente.

| Variável | Função |
|---|---|
| ZUBBO_DB_HOST / PORT / NAME | Endereço, porta e banco MySQL |
| ZUBBO_DB_USER / PASSWORD | Acesso ao banco |
| ZUBBO_BASE_URL / BASE_PATH | Endereço externo e prefixo de URLs |
| ZUBBO_MAIL_PROVIDER | **resend** ou **smtp** |
| ZUBBO_RESEND_API_KEY | Credencial do Resend, exclusivamente no servidor |
| ZUBBO_MAIL_FROM | Remetente autorizado no provedor |
| ZUBBO_SMTP_USER / PASSWORD / HOST / PORT | Configuração SMTP alternativa |
| ZUBBO_SMTP_ENCRYPTION | Tipo de conexão SMTP |
| ZUBBO_DEMO_MODE | Mostrar código de verificação na demo |
| ZUBBO_ENV | Identificação do ambiente nos logs (ex.: demo) |
| RAILPACK_PHP_EXTENSIONS | Extensões PHP no Railway; incluir pdo_mysql |

**Nunca coloque senhas, tokens de serviço ou segredos no README, em arquivos PHP, no APK ou no repositório.** Credenciais já expostas devem ser revogadas.

No XAMPP, as variáveis podem ser configuradas no Apache ou no ambiente em que o PHP é executado. A conexão está em **config/database.php**. Em ambiente local não configurado, o código possui os padrões localhost:3306, usuário root, senha vazia e banco app_zubbo — **somente para desenvolvimento local**.

## 6. Banco de dados: ordem de criação

**Para instalação nova**, primeiro crie/selecione um banco vazio no MySQL. Execute:

~~~text
1. database/scripts/criacaotables.sql
2. database/scripts/insertstables.sql
~~~

O primeiro script cria a estrutura: tabelas, relacionamentos, índices, defaults e regras de vagas. O segundo popula modalidades e locais iniciais.

**Tabelas importantes:**

- **Usuario, Administrador, Usuario_Esporte**: contas, permissões e modalidades;
- **Verificacao_Email, Recuperacao_Senha**: confirmação e redefinição de senha;
- **Solicitacao_Amizade, Amizade**: relacionamentos;
- **Conversa, Grupo, Comunidade, Mensagem, Notificacao, Participantes_Conversa**: comunicação;
- **Esporte, LocalEsp, Evento, Lista_Evento, Solicitacao_Vaga_Evento**: encontros;
- **Denuncia, Sugestao_Esporte, Acao_Administrativa**: moderação e rastreabilidade.

A modelagem está em **database/diagrams/** e existem consultas auxiliares em **database/scripts/querytables.sql**.

Não execute migrações de tabelas antigas em um banco já criado pelo **criacaotables.sql** mais recente.

## 7. Atualização de banco existente

Antes de alterar o schema, **faça backup e teste a restauração em um banco separado**. Não execute scripts de atualização sem verificar se a tabela/coluna já existe.

| Arquivo | Usar somente quando |
|---|---|
| database/scripts/add_recuperacao_senha.sql | Falta a tabela de recuperação |
| database/scripts/add_solicitacao_vaga_evento.sql | Falta a tabela de solicitação de vagas |
| database/scripts/upgrade_solicitacao_vaga_evento.sql | A tabela existe em formato antigo |
| database/migrations/002_admin_user_relation.sql | Banco antigo sem vínculo correto entre administrador e usuário |
| database/migrations/003_deduplicate_locations.sql | Banco antigo com duplicidade de locais |
| database/migrations/004_denuncia_local.sql | A tabela Denuncia ainda não tem id_local |

A **migration 002** pode deixar algum administrador sem id_user quando não consegue associá-lo com segurança. Nesses casos, revise a conta manualmente; a versão atual não concede privilégios apenas porque os e-mails são iguais.

Para cópia de banco entre production e demo, mantenha **schema e dados** separados: importe primeiro o schema correto e depois os dados nas tabelas existentes; não recrie tabelas automaticamente com a ferramenta de transferência. Nunca altere a production congelada durante testes.

## 8. Administrador e moderação

Não existe necessidade de senha padrão de administrador. O administrador é uma conta normal do Zubbo, associada ao registro em **Administrador**.

1. Cadastre uma conta normal e confirme seu endereço.
2. Pelo terminal da aplicação, execute:

~~~sh
php database/tools/create-admin.php
~~~

3. Informe o e-mail de uma conta existente, em ambiente autorizado. O provisionamento associa a permissão ao **id_user**. Não rode essa ferramenta em produção sem autorização da equipe responsável.

Os módulos de administração incluem usuários, eventos, locais, denúncias, sugestões e o histórico de ações. As alterações de status usam POST com CSRF e gravam a ação administrativa na mesma transação.

## 9. Railway e modo de demonstração

Instruções operacionais completas: [RAILWAY_TEST.md](RAILWAY_TEST.md).

Na demo:

~~~text
Branch: teste-railway
Root Directory: /-TCC-
Start Command: php -S 0.0.0.0:$PORT router.php
Healthcheck Path: /health.php
ZUBBO_BASE_PATH: /
ZUBBO_ENV: demo
~~~

Configure as variáveis de banco usando **referências para o serviço MySQL da própria demo**, nunca para o banco da production.

O **router.php** serve apenas rotas permitidas e impede acesso direto a **config/**, **database/**, **vendor/** e arquivos internos; o comando inicial do Railway **deve** carregar esse roteador.

**Modo demo:** a variável **ZUBBO_DEMO_MODE=true** exibe o código de confirmação na tela para apresentação. **Nunca ative esse modo em produção pública**. Fora da demo, mantenha false e configure remetente de e-mail verificado.

**Importante:** /health.php confirma resposta do runtime PHP, mas não faz teste de prontidão do banco. Fotos em **public/uploads/** no filesystem do container podem ser perdidas após deploy; para uso real é necessário armazenamento persistente.

## 10. Segurança e privacidade

Proteções implantadas:

- Hash seguro de senha; autenticação e estado da conta verificados no backend;
- Cookies HttpOnly, SameSite e Secure quando em HTTPS;
- CSRF, bloqueio de POSTs de outras origens e headers de segurança;
- PDO com prepared statements;
- Controle de acesso no admin por **id_user**, não por mera correspondência de e-mail;
- Alterações sensíveis de credenciais com revalidação e confirmação;
- Pesquisa de perfis protegida por login, sem fornecer e-mail de outras pessoas;
- Uploads limitados por tipo e tamanho de imagem;
- Rate limit de autenticação e recuperação de senha.

**Pendências para lançamento público:** política definitiva de privacidade e retenção, solução persistente para sessão/rate limit/uploads, testes de carga e revisão independente de segurança. A opção **Desativar conta** anonimiza dados básicos, mas não é uma exclusão completa de todos os relacionamentos.

Arquivos de referência:

- [SECURITY.md](SECURITY.md)
- [Página informativa de privacidade](public/privacidade.php)
- [Observabilidade e testes](docs/OBSERVABILIDADE_E_TESTES.md)

## 11. Logs e testes automatizados

O logger **config/logger.php** gera eventos JSON com horário UTC, nível, evento, request_id, rota e campos de contexto permitidos. Ele **não deve** registrar e-mail, senha, token, texto de mensagem nem descrição de denúncia.

Eventos implementados incluem autenticação, rejeições administrativas, alterações de senha e e-mail, denúncias e falhas de conexão com o banco.

No Railway, consulte o serviço da demo > **Logs** e procure eventos como:

~~~text
"event":"auth.login_denied"
"event":"account.password_changed"
"event":"admin.action_logged"
"level":"error"
~~~

**Automação:** os workflows, na raiz do repositório, executam:

| Workflow | O que verifica |
|---|---|
| PHP Syntax Check | Sintaxe dos arquivos PHP com PHP 8.4 |
| Railway Readiness | Dependências, roteador, segurança, rotas e smoke tests |
| Demo MySQL Integration | Schema e fluxos principais em banco MySQL 8.4 temporário, inclusive modo SQL estrito |

Veja as execuções em [GitHub Actions](https://github.com/silvestrevini11/Zubbo-App/actions).

É possível executar localmente o teste de segurança com:

~~~sh
php tests/security-smoke.php
~~~

O teste **tests/db-smoke.php** cria/preenche tabelas e deve ser usado **somente em banco temporário descartável**. Não execute contra demo com dados importantes ou production.

A equipe realizou uma checklist funcional de **39 testes marcados como aprovados**, incluindo login, privacidade, chat, eventos, denúncias e mobile no navegador. Isso não substitui pentest nem homologação de APK.

## 12. APK Android: integração prevista

O projeto pode evoluir para Android usando **Android Studio + Kotlin + WebView** para carregar a interface hospedada em domínio HTTPS. O PHP continua no Railway e o MySQL no servidor.

Para um primeiro protótipo, verificar:

1. Restrição de URLs a domínios conhecidos e HTTPS;
2. Cookies de sessão, login e logout;
3. Navegação de volta, links externos e downloads;
4. Upload de foto e seleção de arquivos;
5. Mapa, responsividade e mudança de tema;
6. Conexão lenta, perda de internet e restauração.

**WebView não converte PHP em código nativo**. Se no futuro a equipe preferir uma experiência Android nativa (Kotlin ou Flutter), será necessário desenvolver uma API autenticada. Não incluir segredos de banco nem de e-mail no APK.

## 13. Problemas comuns e referências

| Sintoma | O que conferir |
|---|---|
| CSS ou imagens não aparecem | Prefixo correto de URL, pasta htdocs, ZUBBO_BASE_PATH |
| Erro ao conectar ao MySQL | Serviço ligado, variáveis, existência do schema, permissões |
| Falta tabela/coluna | Scripts de criação ou migração aplicados ao banco correto |
| Admin não entra | Conta ativa e Administrador.id_user associado corretamente |
| Código de e-mail não chega | ZUBBO_MAIL_PROVIDER, remetente e credenciais configurados |
| Mensagens HTTP 403 | Token CSRF e sessão válida; recarregue formulário após novo login |
| HTTP 500 | Conferir Logs do Railway e o estado da conexão com MySQL |
| Falta imagem após redeploy | Uploads ainda dependem do filesystem do container |
| Fotos, mapa ou layout falham no Android | Configuração de WebView, HTTPS e permissões |

**Comandos Git úteis:**

~~~sh
git checkout teste-railway
git pull origin teste-railway
git status
~~~

**Referências complementares:**

- [README do projeto](../README.md)
- [Configuração Railway](RAILWAY_TEST.md)
- [Segurança](SECURITY.md)
- [Logs e automação](docs/OBSERVABILIDADE_E_TESTES.md)
- [Regras e modelagem SQL](database/scripts/criacaotables.sql)
- [Banco de dados no CI](tests/db-smoke.php)

---

Este README é a fonte de referência **técnica** da versão presente na branch **teste-railway**. Para a apresentação acadêmica do projeto, utilize o README da raiz.
