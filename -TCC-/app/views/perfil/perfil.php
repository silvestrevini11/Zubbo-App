<?php 
include __DIR__ .'/../includes/head.php';
include __DIR__.'/../../../config/database.php';
session_start();

$id_user = $_SESSION['usuario']['id'];

$stmtUsuario = $conn->prepare("
    SELECT nome_user, email_user
    FROM Usuario
    WHERE id_user = ?
");

$stmtUsuario->execute([$id_user]);

$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("
    SELECT e.nome_esporte
    FROM Esporte e
    INNER JOIN Usuario_Esporte ue
        ON e.id_esporte = ue.id_esporte
    WHERE ue.id_user = ?
    ORDER BY e.nome_esporte
");

$stmt->execute([$id_user]);

$esportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section style="padding-bottom: 80px;
}">

<button onclick="window.location.href='./perfil-configuracoes.php';" class="perfil-config"></button>
<button onclick="window.location.href='./perfil-editar.php';" class="perfil-editar"></button>

<?php
    $stmtFoto = $conn->prepare("
    SELECT foto_user
    FROM Usuario
    WHERE id_user = ?
");

$stmtFoto->execute([$id_user]);

$usuarioFoto = $stmtFoto->fetch(PDO::FETCH_ASSOC);

$fotoPerfil = !empty($usuarioFoto['foto_user'])
    ? '/-TCC-/' . $usuarioFoto['foto_user']
    : '/-TCC-/public/imagem/blank.png';
?>

<form action="upload-foto.php" method="POST" enctype="multipart/form-data">

    <label for="fotoPerfil" class="perfil-pic-label">

        <img
            class="perfil-pic"
            src="<?= htmlspecialchars($fotoPerfil) ?>"
            alt="Foto de perfil"
        >

    </label>

    <input
        type="file"
        id="fotoPerfil"
        name="fotoPerfil"
        accept="image/png, image/jpeg, image/webp"
        hidden
    >

</form>

<script>
const inputFoto = document.getElementById('fotoPerfil');

inputFoto.addEventListener('change', function () {

    if (this.files.length > 0) {
        this.form.submit();
    }

});
</script>

<h1 class="perfil-nome">
    <?= htmlspecialchars($usuario['nome_user']) ?>
</h1>

<h3 class="perfil-email">
    <?= htmlspecialchars($usuario['email_user']) ?>
</h3>


<div class="perfil-nivel">
    <p class="perfil-nivel-nome">Nivel-Inciante</p>
</div>

<div class="perfil-status">
    <div class="perfil-eventos">
        <h3 class="perfil-name">Eventos</h3>
        <h2 class="perfil-eventos-num">0</h2>
    </div>
    <div class="perfil-amigos">
        <h3 class="perfil-name">Amigos</h3>
        <h2 class="perfil-amigos-num">0</h2>
    </div>
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
                    'Futesal' => 'esporte-futesal',
                    'Handebol' => 'esporte-handebol',
                    default => 'esporte-outro'
                };
            ?>

            <div class="esporte-card <?= $classe ?>">
                <span><?= htmlspecialchars($nome) ?></span>
            </div>

        <?php endforeach; ?>

    </div>

</div>

<div class="perfil-organiza-eventos">
    <h2>Eventos que organizei</h2>
    </p>Ver Todos</p>

    <section class="perfil-eventos-idos">
        <!--para fazer ainda-->
    </section>
</div>

</section>
<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>