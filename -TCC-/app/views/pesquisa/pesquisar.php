<?php 
include __DIR__ .'/../includes/head.php';
include __DIR__.'/../../../config/database.php';
?>

<section style="padding-bottom: 80px;">

    <div class="pesquisa-search">
        <input 
            type="text" 
            id="pesquisaInput"
            placeholder="PESQUISAR..." 
            name="text-pesquisar" 
            class="pesquisa-busca"
            autocomplete="off"
        >

        <button class="pesquisa-filtro" type="button"></button>
    </div>


    <h2 class="pesquisa-perfil">Perfis</h2>

    <div id="resultadosPerfis" class="resultados-perfis">
        <!-- Os resultados vão aparecer aqui -->
    </div>


    <h2 class="pesquisa-eventos">Eventos</h2>
    <hr class="perfil-hr">


    <h2 class="pesquisa-poles">Poliesportivos</h2>
    <hr class="perfil-hr">


    <h2 class="pesquisa-comunidade">Comunidades</h2>
    <hr class="perfil-hr">

</section>





<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>