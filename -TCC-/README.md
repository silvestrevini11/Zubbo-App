# Zubbo App

Projeto de TCC para encontros esportivos comunitários.

## Segurança e configuração local

O projeto não deve conter credenciais SMTP ou senhas administrativas no código. Consulte SECURITY.md.

Para bancos existentes, aplique:

1. database/migrations/002_admin_user_relation.sql
2. database/migrations/003_deduplicate_locations.sql

Para promover uma conta ativa a administrador:

php database/tools/create-admin.php

A autenticação administrativa usa o login normal do Zubbo.

## Validação

O workflow em .github/workflows/php-lint.yml verifica a sintaxe dos arquivos PHP em pushes e pull requests.
