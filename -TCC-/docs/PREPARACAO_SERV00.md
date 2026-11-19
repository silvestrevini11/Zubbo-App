# Preparação para testar o Zubbo na Serv00

Este roteiro prepara uma implantação de teste. A inscrição na Serv00 ainda está em análise; não há domínio, servidor, banco ou versão de MySQL confirmados. Não alterar a origem do APK nem desligar a demo Railway antes dos testes e da conferência dos dados.

## Já conhecido

- A Serv00 documenta sites PHP com diretório `public_html`, seleção de PHP 8.4, regras `.htaccess`, HTTPS, SSH e MySQL. A compatibilidade real depende da conta e deve ser verificada no servidor atribuído.
- O Zubbo usa URLs a partir da raiz (`ZUBBO_BASE_PATH=/`), Composer, PDO MySQL, cURL, mbstring e OpenSSL. O APK atual aponta para Railway; mudar de domínio exige outro APK assinado com a mesma chave, o mesmo identificador e versão maior.
- O verificador `tests/environment-readiness.php` foi feito para Railway e exige host privado Railway e MySQL 8.4. Não usar sua aprovação como critério de aceite na Serv00 sem adaptar essas verificações.
- O repositório contém alterações locais em andamento no banco e nos testes. Elas precisam ser revisadas antes de escolher o commit a publicar.

## Assim que a conta for aprovada

1. Registrar, sem senha no Git ou em conversas, o domínio HTTPS, nome do servidor, acesso SSH, limite de armazenamento e caminho do site. Ativar o tipo de site PHP, escolher PHP 8.4 e confirmar `php84 -v` no SSH. No PHP web, verificar extensões exigidas pelo Composer e pela aplicação.
2. Criar um banco vazio e um usuário próprio. Confirmar `SELECT VERSION(), DATABASE()` e testar a conexão de PHP com `pdo_mysql`. A documentação da Serv00 usa `mysqlX.serv00.com`, em que `X` depende do servidor. Acesso MySQL externo pode exigir plano premium; planejar importação e conferência via SSH, sem depender de DBeaver remoto.
3. Em banco descartável, testar os scripts de criação e inserts e a auditoria. Se a versão do MySQL ou alguma definição SQL for incompatível, registrar a falha e adaptar/validar antes de copiar dados reais. Não executar fixtures no banco publicado.
4. Preparar a configuração privada de ambiente e instalar dependências pelo `composer.lock`. Confirmar como a Serv00 entrega variáveis ao PHP web antes de definir o mecanismo de segredos. Nunca colocar senha, chaves VAPID ou tokens em arquivos acessíveis pela web.
5. Publicar uma cópia de teste do código. Verificar na origem HTTPS real `/health.php`, `/public/manifest.php`, `/sw.js`, `/.well-known/assetlinks.json`, ícones e página offline. Testar que `config/`, `database/`, `vendor/` e `tests/` não são acessíveis. As regras `.htaccess` da Serv00 são interpretadas por módulo próprio do Nginx; conferir cada bloqueio por HTTP, inclusive a regra `[END]`. Desativar qualquer desafio interativo/PoW no domínio usado pela TWA se ele afetar os arquivos de associação.
6. Exportar a base e os uploads da origem, preservar backup privado e restaurar uma cópia no destino. Conferir contagens e amostras de usuários, eventos e mensagens. Repetir a exportação na troca final para não perder alterações feitas entre os dois momentos. Não excluir a origem antes da conferência.
7. Testar em celular login, cadastro, mapa, localização, eventos, mensagens, fotos e notificações. Confirmar persistência após novo acesso. Só então alterar host, URLs e versão no manifesto Bubblewrap, gerar APK novo com a chave existente, publicar `assetlinks.json` na Serv00 e testar atualização sobre o APK anterior.

## Pendências que não podem ser resolvidas antes do e-mail

- Domínio/servidor atribuídos, versão real do MySQL, extensões e limites da conta.
- Compatibilidade das regras de acesso, da configuração privada de PHP e do Web Push na origem Serv00.
- Exportação, importação e comparação dos dados reais; testes em Android com o novo domínio.

Referências oficiais: [PHP](https://docs.serv00.com/PHP/), [WWW](https://docs.serv00.com/WWW/), [.htaccess](https://docs.serv00.com/htaccess/), [MySQL](https://docs.serv00.com/MySQL/), [SSL](https://docs.serv00.com/SSL/), [backup](https://docs.serv00.com/Backup/).
