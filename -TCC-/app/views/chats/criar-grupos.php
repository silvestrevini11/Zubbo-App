<?php
include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';
?>

<section style="padding-top: 70px;">

<button class="btn-voltar" onclick="window.location.href='/../-TCC-/public/index.php'">
    ←  
    </button>

<h1 class="criar-grupo-title">Criar Grupo</h1>

<h3 class="criar-grupo-add-imagem">Adicionar Imagem</h3>

<form action="upload-foto.php" method="POST" enctype="multipart/form-data">

    <label for="fotoGrupo" class="foto-grupo">

        <img
            class="grupo-pic"
            src="<?= htmlspecialchars($fotoGrupo) ?>"
            alt="Foto de grupo"
        >

    </label>

    <input
        type="file"
        id="fotoGrupo"
        name="fotoGrupo"
        accept="image/png, image/jpeg, image/webp"
        hidden
    >
</form>

<h3 class="criar-grupos-NomeGP">Nome do grupo:</h3>
<form class="criar-grupos-Name" action="///.php" method="post">

        <div class="field-cad">
            <input type="text" placeholder="Nome Do Grupo" name="name-grupo-txt" required>
        </div>

</form>

<h3 class="criar-grupos-add-pessoas">Adicionar Pessoas</h3>
<button class="criar-grupos-add-pessoas">(+)</button>

<button class="criar-grupos-Create">Criar Grupo</button>

</section>

<?php
include __DIR__ . '/../../views/includes/footer.php';
?>