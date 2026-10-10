<?php 
include __DIR__ .'/../includes/head.php';
include __DIR__.'/../../../config/database.php';
?>

<section style="padding-bottom: 80px;">
    <div class="pesquisa-search">
        <input type="text" id="pesquisaInput" placeholder="PESQUISAR..." name="text-pesquisar" class="pesquisa-busca" autocomplete="off">
        <button class="pesquisa-filtro" type="button"></button>
    </div>

    <h2 class="pesquisa-perfil">Perfis</h2>
    <div id="resultadosPerfis" class="resultados-perfis"></div>
    <hr class="pesquisa-catalogo-hr">

    <h2 class="pesquisa-poles">Poles</h2>
    <div id="resultadosPoles" class="resultados-poles"></div>
    <hr class="perfil-hr">

    <h2 class="pesquisa-comunidade">Eventos</h2>
    <div id="resultadosEventos" class="resultados-eventos"></div>
    <hr class="pesquisa-catalogo-hr">
</section>

<script src="<?= htmlspecialchars(zubbo_url('/public/js/pesquisa.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>