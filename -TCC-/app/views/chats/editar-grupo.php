<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];
$id_grupo = (int) ($_GET['id_grupo'] ?? 0);
$erro = $_SESSION['editar_grupo_erro'] ?? '';
$sucesso = $_SESSION['editar_grupo_sucesso'] ?? '';
unset($_SESSION['editar_grupo_erro'], $_SESSION['editar_grupo_sucesso']);

$stmt = $conn->prepare("
    SELECT id_grupo, id_conversa, id_criador, nome_grupo, descricao_grupo, foto_grupo
    FROM Grupo
    WHERE id_grupo = ? AND id_criador = ?
");
$stmt->execute([$id_grupo, $id_usuario]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: chats-grupos.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT u.id_user, u.nome_user, u.foto_user
    FROM Participantes_Conversa pc
    INNER JOIN Usuario u ON u.id_user = pc.id_user
    WHERE pc.id_conversa = ?
    ORDER BY (u.id_user = ?) DESC, u.nome_user
");
$stmt->execute([$grupo['id_conversa'], $id_usuario]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT u.id_user, u.nome_user, u.foto_user
    FROM Amizade a
    INNER JOIN Usuario u
      ON u.id_user = CASE WHEN a.id_user_1 = :id1 THEN a.id_user_2 ELSE a.id_user_1 END
    WHERE (a.id_user_1 = :id2 OR a.id_user_2 = :id3)
      AND u.id_user NOT IN (
          SELECT pc.id_user
          FROM Participantes_Conversa pc
          WHERE pc.id_conversa = :conversa
      )
    ORDER BY u.nome_user
");
$stmt->execute([
    ':id1'=>$id_usuario,
    ':id2'=>$id_usuario,
    ':id3'=>$id_usuario,
    ':conversa'=>$grupo['id_conversa']
]);
$amigosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fotoGrupo = $grupo['foto_grupo']
    ? '/-TCC-/'.$grupo['foto_grupo']
    : '/-TCC-/public/imagem/blank.png';
?>

<section class="grupo-criar-container">
    <header class="grupo-criar-topo">
        <button type="button" class="grupo-criar-voltar"
            onclick="location.href='chat-grupo.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>'">←</button>
        <div>
            <span>CONFIGURAÇÕES</span>
            <h1>Editar grupo</h1>
        </div>
    </header>

    <?php if ($erro): ?>
        <div class="grupo-criar-alerta"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="grupo-lista-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form action="salvar-edicao-grupo.php" method="POST" enctype="multipart/form-data" class="grupo-criar-form">
        <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">

        <div class="grupo-foto-wrap">
            <label for="foto_grupo" class="grupo-foto-label">
                <img id="preview-grupo" src="<?= htmlspecialchars($fotoGrupo) ?>" alt="Foto do grupo">
                <span>Alterar foto</span>
            </label>
            <input type="file" id="foto_grupo" name="foto_grupo"
                accept="image/png,image/jpeg,image/webp" hidden>
        </div>

        <div class="grupo-campo">
            <label for="nome_grupo">Nome do grupo</label>
            <input type="text" id="nome_grupo" name="nome_grupo"
                maxlength="80" value="<?= htmlspecialchars($grupo['nome_grupo']) ?>" required>
        </div>

        <div class="grupo-campo">
            <label for="descricao_grupo">Descrição</label>
            <textarea id="descricao_grupo" name="descricao_grupo"
                maxlength="255" rows="4"><?= htmlspecialchars($grupo['descricao_grupo'] ?? '') ?></textarea>
            <small><span id="contador-descricao"><?= mb_strlen($grupo['descricao_grupo'] ?? '') ?></span>/255</small>
        </div>

        <div class="grupo-amigos-cabecalho">
            <div>
                <h2>Participantes</h2>
                <p>Marque quem deseja remover do grupo.</p>
            </div>
        </div>

        <div class="grupo-amigos-lista">
            <?php foreach ($participantes as $p): ?>
                <?php
                    $foto = $p['foto_user'] ? '/-TCC-/'.$p['foto_user'] : '/-TCC-/public/imagem/blank.png';
                    $criador = (int)$p['id_user'] === $id_usuario;
                ?>
                <label class="grupo-amigo-item <?= $criador ? 'grupo-criador-item' : '' ?>">
                    <?php if (!$criador): ?>
                        <input type="checkbox" name="remover[]" value="<?= (int)$p['id_user'] ?>">
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($foto) ?>" alt="" class="grupo-amigo-foto">
                    <span class="grupo-amigo-nome">
                        <?= htmlspecialchars($p['nome_user']) ?>
                        <?php if ($criador): ?><small> • Criador</small><?php endif; ?>
                    </span>
                    <?php if (!$criador): ?>
                        <span class="grupo-remover-check">Remover</span>
                    <?php else: ?>
                        <span class="grupo-criador-badge">Admin</span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="grupo-amigos-cabecalho">
            <div>
                <h2>Adicionar pessoas</h2>
                <p>Somente amigos que ainda não estão no grupo.</p>
            </div>
        </div>

        <?php if (!$amigosDisponiveis): ?>
            <div class="grupo-sem-amigos">Não há outros amigos disponíveis para adicionar.</div>
        <?php else: ?>
            <div class="grupo-amigos-lista">
                <?php foreach ($amigosDisponiveis as $amigo): ?>
                    <?php $foto = $amigo['foto_user'] ? '/-TCC-/'.$amigo['foto_user'] : '/-TCC-/public/imagem/blank.png'; ?>
                    <label class="grupo-amigo-item">
                        <input type="checkbox" name="adicionar[]" value="<?= (int)$amigo['id_user'] ?>">
                        <img src="<?= htmlspecialchars($foto) ?>" alt="" class="grupo-amigo-foto">
                        <span class="grupo-amigo-nome"><?= htmlspecialchars($amigo['nome_user']) ?></span>
                        <span class="grupo-amigo-check">✓</span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="grupo-criar-submit">Salvar alterações</button>
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
const contador = document.getElementById('contador-descricao');
descricao?.addEventListener('input', () => contador.textContent = descricao.value.length);
</script>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>