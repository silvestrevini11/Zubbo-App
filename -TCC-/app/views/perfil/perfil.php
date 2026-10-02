<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];

$stmt = $conn->prepare(
    'SELECT nome_user, email_user, foto_user FROM Usuario WHERE id_user = ? LIMIT 1'
);
$stmt->execute([$idUsuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    unset($_SESSION['usuario']);
    header('Location: ../auth/login.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT COUNT(*) FROM Amizade WHERE id_user_1 = ? OR id_user_2 = ?'
);
$stmt->execute([$idUsuario, $idUsuario]);
$quantidadeAmigos = (int) $stmt->fetchColumn();

$stmt = $conn->prepare(
    'SELECT COUNT(*) FROM Evento WHERE id_criador = ? AND status_evento <> "removido"'
);
$stmt->execute([$idUsuario]);
$quantidadeEventos = (int) $stmt->fetchColumn();

$stmt = $conn->prepare(
    'SELECT e.nome_esporte
     FROM Esporte e
     INNER JOIN Usuario_Esporte ue ON e.id_esporte = ue.id_esporte
     WHERE ue.id_user = ?
     ORDER BY e.nome_esporte'
);
$stmt->execute([$idUsuario]);
$esportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fotoPerfil = !empty($usuario['foto_user'])
    ? '/-TCC-/' . $usuario['foto_user']
    : '/-TCC-/public/imagem/blank.png';

include __DIR__ . '/../includes/head.php';
?>
<section style="padding-bottom:80px;">
    <button onclick="window.location.href='perfil-configuracoes.php'" class="perfil-config" aria-label="Configurações"></button>
    <button onclick="window.location.href='perfil-editar.php'" class="perfil-editar" aria-label="Editar perfil"></button>

    <form action="upload-foto.php" method="post" enctype="multipart/form-data">
        <?= zubbo_csrf_input() ?>
        <label for="fotoPerfil" class="perfil-pic-label">
            <img class="perfil-pic" src="<?= htmlspecialchars($fotoPerfil, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil">
        </label>
        <input type="file" id="fotoPerfil" name="fotoPerfil" accept="image/png,image/jpeg,image/webp" hidden>
    </form>

    <script>
        const inputFoto = document.getElementById('fotoPerfil');
        inputFoto.addEventListener('change', function () {
            if (this.files.length > 0) this.form.submit();
        });
    </script>

    <h1 class="perfil-nome"><?= htmlspecialchars($usuario['nome_user'], ENT_QUOTES, 'UTF-8') ?></h1>
    <h3 class="perfil-email"><?= htmlspecialchars($usuario['email_user'], ENT_QUOTES, 'UTF-8') ?></h3>

    <div class="perfil-nivel"><p class="perfil-nivel-nome">Nível iniciante</p></div>

    <div class="perfil-status">
        <div class="perfil-eventos">
            <h3 class="perfil-name">Eventos</h3>
            <h2 class="perfil-eventos-num"><?= $quantidadeEventos ?></h2>
        </div>
        <a href="amigos.php" class="perfil-amigos">
            <h3 class="perfil-name">Amigos</h3>
            <h2 class="perfil-amigos-num"><?= $quantidadeAmigos ?></h2>
        </a>
    </div>

    <div class="perfil-sobre">
        <h2 class="perfil-sobremim">Sobre Mim</h2>
        <p class="perfil-sobremim-texto"></p>
    </div>

    <div class="perfil-esportes">
        <h2>Meus Esportes</h2>
        <div class="esportes-lista">
            <?php foreach ($esportes as $esporte): ?>
                <?php
                    $nome = $esporte['nome_esporte'];
                    $classe = match ($nome) {
                        'Futebol' => 'esporte-futebol',
                        'Basquete' => 'esporte-basquete',
                        'Vôlei' => 'esporte-volei',
                        'Tênis' => 'esporte-tenis',
                        'Futsal' => 'esporte-futesal',
                        'Corrida' => 'esporte-corrida',
                        'Handebol' => 'esporte-handebol',
                        default => 'esporte-outro',
                    };
                ?>
                <div class="esporte-card <?= $classe ?>">
                    <span class="esporte-card-txt"><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="perfil-organiza-eventos">
        <h2>Eventos que organizei</h2>
        <p>Ver todos</p>
        <section class="perfil-eventos-idos"></section>
    </div>
</section>
<?php
include __DIR__ . '/../includes/under-bar.php';
include __DIR__ . '/../includes/footer.php';
