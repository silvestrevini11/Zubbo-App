# Moderadores de grupos

Antes de publicar esta versão em uma instalação existente, execute `database/migrations/006_moderadores_grupo.sql` no mesmo banco da aplicação. A migration é aditiva e pode ser repetida; não precisa ser aplicada automaticamente a cada requisição. Instalações novas já recebem a tabela no script `database/scripts/criacaotables.sql`.

O responsável atual do grupo mantém acesso. Moderadores podem promover participantes ativos e editar dados e integrantes do próprio grupo. Moderadores não podem ser removidos pelo formulário de edição de participantes. A moderação do grupo não concede acesso ao painel administrativo do Zubbo.

Ao sair um moderador, outro participante ativo que ainda não é moderador é escolhido com `random_int`. Os moderadores existentes mantêm suas funções. Se o responsável sair e todos os integrantes ativos restantes já forem moderadores, um deles assume a responsabilidade. Se não houver integrante ativo restante, a saída continua permitida e o responsável fica vazio. Nenhuma mensagem de chat, notificação ou push anuncia a seleção.

Promoções, edições e saídas bloqueiam a mesma linha de grupo durante a transação, revalidando a autorização após o bloqueio. As ações POST usam a proteção CSRF da aplicação.

Para validar as alterações, utilize um banco de desenvolvimento isolado, confira permissões, sucessão de responsáveis e rollback. O comando `php database/tools/migration-contract.php` verifica o contrato SQL.
