# Zubbo App

Projeto de TCC para encontros esportivos comunitários.

## Estrutura

- app/middleware: autenticação compartilhada
- app/services: regras de negócio reutilizáveis
- app/views: páginas e componentes
- config: banco, e-mail e segurança
- database/schema: estrutura final do banco
- database/migrations: atualizações para bancos existentes
- database/seeds: dados iniciais
- database/tools: utilitários de manutenção
- database/queries: consultas de desenvolvimento
- public: CSS, JavaScript, imagens, uploads e ponto de entrada
- docs: documentação e materiais do projeto

## Banco de dados

Instalação nova:
1. Importe database/schema/schema.sql.
2. Importe database/seeds/initial.sql.

Banco já existente:
1. Execute as migrations de database/migrations em ordem.
2. Execute database/seeds/initial.sql se quiser atualizar os dados padrão.

Os locais possuem uma restrição única por nome + endereço. O seed pode ser executado novamente sem criar cópias.

## Administrador

Cadastre uma conta normalmente no Zubbo e execute:

php database/tools/create-admin.php

O sistema utiliza login único. A mesma senha da conta comum é utilizada para acessar o painel administrativo.

## Dependências

Execute composer install quando a pasta vendor não estiver disponível.

## Segurança

Consulte SECURITY.md para variáveis de ambiente e decisões de segurança.
