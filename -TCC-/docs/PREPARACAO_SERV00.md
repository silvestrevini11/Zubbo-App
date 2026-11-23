# Preparação para hospedagem web

Este guia reúne cuidados gerais para publicar o Zubbo em um servidor PHP. Confirme os recursos e limites do provedor escolhido antes de qualquer migração.

1. Configure PHP 8.4, Composer, extensões `pdo_mysql`, `curl`, `mbstring`, `openssl` e `fileinfo`, HTTPS e um banco MySQL compatível.
2. Crie um banco vazio e usuário próprio. Configure as variáveis `ZUBBO_DB_*`, `ZUBBO_BASE_PATH` e `ZUBBO_BASE_URL` de forma privada, sem colocar segredos em arquivos públicos.
3. Instale dependências com `composer install` e publique a aplicação em uma pasta apropriada do servidor.
4. Confira o acesso à página inicial, `/health.php`, `/public/manifest.php`, `/sw.js` e aos recursos estáticos. Bloqueie por HTTP o acesso a `config/`, `database/`, `vendor/` e arquivos internos; verifique as regras do servidor usado.
5. Antes de migrar dados, faça backup do banco e dos uploads e teste a restauração em ambiente separado.
6. Verifique cadastro, login, mapa, eventos, mensagens, notificações, upload e persistência após reinício.
7. Só substitua o ambiente anterior após conferir dados, HTTPS, segurança e funcionalidades.

A implantação e os testes de produção ainda precisam ser validados no provedor escolhido.
