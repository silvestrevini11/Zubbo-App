# Zubbo — Web Push (branch teste-alteracoes)

O envio **Web Push real** está conectado aos seguintes acontecimentos:

- Mensagem privada entre amigos aceitos;
- Mensagem em grupo (para outros participantes ativos);
- Solicitação de amizade;
- Aceitação de amizade;
- Aprovação para participar de evento.

As notificações são genéricas, **sem nome, texto de mensagem ou outros dados privados**. O destinatário pode ativar/desativar a inscrição em **Perfil → Configurações → Alertas de mensagens** e enviar uma notificação de teste.

## 1. Instalar dependências

Na pasta \`-TCC-\` do clone da branch:

\`\`\`bash
composer install --no-interaction
\`\`\`

O \`composer.json\` e o \`composer.lock\` já contêm \`minishlink/web-push\` (PHP >=8.2) e Guzzle. Exige extensões PHP \`curl\`, \`openssl\`, \`mbstring\` e \`json\`, além de \`pdo_mysql\`. Configure certificados CA válidos; nunca desative a verificação TLS.

## 2. Criar as chaves VAPID **uma vez por ambiente**

No terminal, **sem compartilhar a saída**:

\`\`\`bash
php jobs/gerar-vapid.php
\`\`\`

Crie as variáveis no Apache/XAMPP (local) ou Railway (ambiente de teste):

\`\`\`env
ZUBBO_VAPID_PUBLIC_KEY=<chave_publica_gerada>
ZUBBO_VAPID_PRIVATE_KEY=<chave_privada_gerada>
ZUBBO_VAPID_SUBJECT=mailto:seu-email-de-contato@example.com
\`\`\`

Não cole valores reais em arquivo commitado. Guarde as chaves em ambiente seguro e mantenha-as estáveis; trocar VAPID exige reinscrever os dispositivos.

No XAMPP, em desenvolvimento você pode adicionar \`SetEnv ZUBBO_VAPID_PUBLIC_KEY ...\` etc. à configuração **local e não versionada** do Apache, depois reiniciá-lo.

## 3. Banco de dados

Em uma **base de teste com backup**, depois das migrations 005–012, execute uma única vez:

\`\`\`sql
SOURCE database/migrations/013_web_push.sql;
\`\`\`

Este comando \`SOURCE\` é reconhecido pelo cliente MySQL no terminal; no phpMyAdmin/DBeaver, abra o arquivo SQL e execute seu conteúdo no banco selecionado. O script cria a tabela \`PushSubscription\`; não cria outro banco.

## 4. Escopo e instalação PWA

O projeto agora possui:

- \`sw.js\`: Service Worker no **nível raiz da instalação**, com eventos \`push\` e \`notificationclick\`;
- \`public/manifest.php\`: manifesto gerado com o caminho correto para **localhost com /Zubbo-App/-TCC-** e Railway;
- \`public/js/push.js\`: inscrição, renovação no login, desativação e teste;
- \`app/views/notificacoes/push-inscricao.php\`: POST autenticado + CSRF, armazenamento por usuário/dispositivo;
- \`app/views/notificacoes/push-teste.php\`: teste autenticado limitado a uma vez a cada 30 segundos;
- \`app/services/PushService.php\`: envio HTTPS com VAPID, verificação de host conhecido e remoção de inscrições expiradas.

No Chrome/Firefox desktop e Android, use **HTTPS ou localhost**. Para iPhone com iOS 16.4+, instale pelo menu **Adicionar à Tela de Início** e abra o Zubbo pelo ícone antes de ativar os avisos. Sistemas operacionais podem bloquear/notificar silenciosamente em modos de foco ou economia de energia.

Se o botão mostrar "servidor ainda precisa configurar as chaves VAPID", verifique as três variáveis acima e reinicie o PHP/Apache ou faça novo deploy no ambiente de teste.

## 5. Teste ponta a ponta

1. No navegador A, entre em Configurações e clique **Ativar**, aceitando a permissão do navegador.
2. Clique **Enviar notificação de teste**. Confira se aparece um aviso do sistema.
3. Entre no Zubbo pelo navegador B com outro usuário que seja amigo aceito de A.
4. Envie uma mensagem privada de B para A.
5. Com o navegador A minimizado ou sem nenhuma aba Zubbo aberta, confira o recebimento da notificação.
6. Faça o mesmo com uma mensagem em grupo, uma solicitação de amizade, aceitação e aprovação de evento.
7. Clique na notificação para conferir se abre uma rota interna válida; teste também **Desativar**.
8. Confira a tabela \`PushSubscription\` para cada dispositivo e teste a exclusão/invalidade de inscrição, **sem compartilhar seus valores JSON ou endpoint**.

## Cuidados de segurança / operação

- Não registrar em logs tokens VAPID, dados JSON das inscrições, URLs de endpoints ou conteúdo de mensagens.
- Inscrição e exclusão de endpoints são autenticadas e protegidas por CSRF; a biblioteca só aceita destinos push de fornecedores conhecidos, para reduzir risco de SSRF.
- O endpoint salvo pertence ao usuário atual. Ao reutilizar o dispositivo para outra conta, o próprio botão sincroniza a inscrição com o usuário autenticado.
- O servidor envia imediatamente **depois de gravar** o evento de mensagem/amizade/aprovação; portanto o navegador da pessoa não precisa estar aberto, mas o HTTP de quem enviou pode demorar alguns segundos caso o fornecedor push esteja lento.
- Sem conexão com o fornecedor, o evento principal permanece salvo; o Web Push pode não ser entregue. **Não há fila de reenvio persistente** nesta primeira versão.
- A página de notificações tradicional continua a funcionar independentemente de o usuário habilitar Web Push.
- Notificações são um recurso voluntário: não solicitar permissão automaticamente.
- Uma instalação nova requer \`composer install\`, banco migrado e variáveis VAPID configuradas. **Não houve configuração de chaves no Railway nem deploy.**
