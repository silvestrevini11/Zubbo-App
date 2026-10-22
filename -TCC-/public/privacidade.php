<?php
require_once __DIR__ . '/../config/security.php';
zubbo_start_session();
include __DIR__ . '/../app/views/includes/head.php';
?>
<main class="container-esqueci-senha" style="padding:32px 16px 100px;">
    <section class="card-esqueci-senha" style="max-width:720px;text-align:left;">
        <h1>Privacidade e uso responsável — Zubbo</h1>
        <p><strong>Informativo da versão de demonstração.</strong> Esta página explica os dados utilizados pelo sistema atual. A política de privacidade definitiva e os procedimentos de exercício de direitos serão formalizados antes da abertura ao público.</p>

        <h2>Quais informações utilizamos?</h2>
        <p>Cadastro: nome, e-mail, telefone e data de nascimento. Perfil: esportes, foto opcional e apresentação opcional. Interação: amizades, mensagens, grupos, eventos, solicitações de vagas, denúncias e sugestões.</p>

        <h2>Para que usamos?</h2>
        <p>Para permitir acesso à conta, organizar encontros esportivos, localizar locais e eventos, viabilizar conversas e apoiar a moderação da comunidade. O e-mail é usado para confirmação e recuperação da conta. Coordenadas do mapa são obtidas por um serviço externo de mapas.</p>

        <h2>Visibilidade</h2>
        <p>Outros participantes podem ver nome, foto e informações públicas do perfil. O e-mail não aparece na busca de perfis nem no perfil público de outra pessoa. Mensagens e denúncias são tratadas nos fluxos apropriados da plataforma.</p>

        <h2>Segurança e conservação</h2>
        <p>Senhas são armazenadas com hash. O acesso administrativo é controlado. A opção de desativar a conta anonimiza os dados cadastrais básicos, mas alguns registros associados a eventos, mensagens e procedimentos de moderação podem permanecer até que uma política específica de retenção e exclusão seja aprovada.</p>

        <h2>Limitações desta demonstração</h2>
        <p>Esta versão não é um serviço público homologado: ainda estão em validação regras de retenção, pedidos de acesso e eliminação de dados, canais de atendimento e medidas operacionais de proteção. Não insira informações sensíveis ou desnecessárias nas conversas e denúncias.</p>
        <p><a href="<?= htmlspecialchars(zubbo_url('/public/index.php'), ENT_QUOTES, 'UTF-8') ?>">Voltar ao Zubbo</a></p>
    </section>
</main>
<?php include __DIR__ . '/../app/views/includes/footer.php'; ?>
