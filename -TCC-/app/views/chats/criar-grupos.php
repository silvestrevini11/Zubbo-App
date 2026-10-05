<?php
require_once __DIR__ . '/../../middleware/auth.php';
include __DIR__ . '/../includes/head.php';

$id_usuario = (int) $_SESSION['usuario']['id'];
$erro = $_SESSION['grupo_erro'] ?? '';
unset($_SESSION['grupo_erro']);

$stmt = $conn->prepare("
    SELECT u.id_user, u.nome_user, u.foto_user
    FROM Amizade a
    INNER JOIN Usuario u
      ON u.id_user = CASE
        WHEN a.id_user_1 = :id1 THEN a.id_user_2
        ELSE a.id_user_1
      END
    WHERE a.id_user_1 = :id2 OR a.id_user_2 = :id3
    ORDER BY u.nome_user
");
$stmt->execute([':id1'=>$id_usuario, ':id2'=>$id_usuario, ':id3'=>$id_usuario]);
$amigos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="grupo-criar-container">
    <header class="grupo-criar-topo">
        <button type="button" class="grupo-criar-voltar" onclick="location.href='chats-grupos.php'">←</button>
        <div><span>NOVO GRUPO</span><h1>Criar grupo</h1></div>
    </header>

    <p class="grupo-criar-descricao">Dê um nome ao grupo, escreva uma descrição e escolha os amigos que participarão.</p>

    <?php if ($erro): ?>
        <div class="grupo-criar-alerta"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form action="salvar-grupo.php" method="POST" enctype="multipart/form-data" class="grupo-criar-form">
        <?= zubbo_csrf_input() ?>
        <div class="grupo-foto-wrap">
            <label for="foto_grupo" class="grupo-foto-label">
                <img id="preview-grupo" src="<?= htmlspecialchars(zubbo_url('/public/imagem/blank.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Foto do grupo">
                <span>Adicionar foto</span>
            </label>
            <input type="file" id="foto_grupo" name="foto_grupo" accept="image/png,image/jpeg,image/webp" hidden>
        </div>

        <div class="grupo-campo">
            <label for="nome_grupo">Nome do grupo</label>
            <input type="text" id="nome_grupo" name="nome_grupo" maxlength="80" placeholder="Ex.: Futsal de sábado" required>
        </div>

        <div class="grupo-campo">
            <label for="descricao_grupo">Descrição</label>
            <textarea id="descricao_grupo" name="descricao_grupo" maxlength="255" rows="4" placeholder="Qual é a ideia do grupo?"></textarea>
            <small><span id="contador-descricao">0</span>/255</small>
        </div>

        <div class="grupo-amigos-cabecalho">
            <div><h2>Adicionar amigos</h2><p>Você já entra automaticamente no grupo.</p></div>
            <span id="contador-selecionados">0 selecionados</span>
        </div>

        <?php if (!$amigos): ?>
            <div class="grupo-sem-amigos">Você ainda não possui amigos adicionados. Mesmo assim, pode criar o grupo só com você.</div>
        <?php else: ?>
            <div class="grupo-amigos-lista">
                <?php foreach ($amigos as $amigo): ?>
                    <?php $foto = $amigo['foto_user'] ? zubbo_url('/' . ltrim((string) $amigo['foto_user'], '/')) : zubbo_url('/public/imagem/blank.png'); ?>
                    <label class="grupo-amigo-item">
                        <input type="checkbox" name="participantes[]" value="<?= (int)$amigo['id_user'] ?>">
                        <img src="<?= htmlspecialchars($foto) ?>" alt="" class="grupo-amigo-foto">
                        <span class="grupo-amigo-nome"><?= htmlspecialchars($amigo['nome_user']) ?></span>
                        <span class="grupo-amigo-check">✓</span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="grupo-criar-submit">Criar grupo</button>
    </form>
</section>

<script>
const foto = document.getElementById('foto_grupo');
const preview = document.getElementById('preview-grupo');
foto?.addEventListener('change', () => {
    if (!foto.files[0]) return;
    const leitor = new FileReader();
    leitor.onload = e => preview.src = e.target.result;
    leitor.readAsDataURL(foto.files[0]);
});

const descricao = document.getElementById('descricao_grupo');
const contadorDesc = document.getElementById('contador-descricao');
descricao?.addEventListener('input', () => contadorDesc.textContent = descricao.value.length);

const caixas = document.querySelectorAll('input[name="participantes[]"]');
const contador = document.getElementById('contador-selecionados');
function atualizarSelecionados() {
    const total = document.querySelectorAll('input[name="participantes[]"]:checked').length;
    contador.textContent = total + (total === 1 ? ' selecionado' : ' selecionados');
}
caixas.forEach(c => c.addEventListener('change', atualizarSelecionados));
</script>
<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>