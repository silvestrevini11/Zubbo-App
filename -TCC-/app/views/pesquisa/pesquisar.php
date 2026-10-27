<?php
require_once __DIR__ . '/../../middleware/auth.php';
include __DIR__ . '/../includes/head.php';
?>

<section style="padding-bottom: 80px;">
    <div class="pesquisa-search">
        <input type="text" id="pesquisaInput" placeholder="PESQUISAR..." name="text-pesquisar" class="pesquisa-busca" autocomplete="off">
        <button class="pesquisa-filtro" type="button" aria-label="Pesquisar">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 50 50" aria-hidden="true" focusable="false">
                <path d="M 21 3 C 11.601563 3 4 10.601563 4 20 C 4 29.398438 11.601563 37 21 37 C 24.355469 37 27.460938 36.015625 30.09375 34.34375 L 42.375 46.625 L 46.625 42.375 L 34.5 30.28125 C 36.679688 27.421875 38 23.878906 38 20 C 38 10.601563 30.398438 3 21 3 Z M 21 7 C 28.199219 7 34 12.800781 34 20 C 34 27.199219 28.199219 33 21 33 C 13.800781 33 8 27.199219 8 20 C 8 12.800781 13.800781 7 21 7 Z" fill="currentColor"></path>
            </svg>
        </button>
    </div>

    <h2 class="pesquisa-perfil">Perfis</h2>
    <div id="resultadosPerfis" class="resultados-perfis"></div>
    <hr class="pesquisa-catalogo-hr">

    <h2 class="pesquisa-poles">Locais</h2>
    <div id="resultadosPoles" class="resultados-poles"></div>
    <hr class="pesquisa-catalogo-hr">

    <h2 class="pesquisa-comunidade">Eventos</h2>
    <div id="resultadosEventos" class="resultados-eventos"></div>
    <hr class="pesquisa-catalogo-hr">
</section>

<script src="<?= htmlspecialchars(zubbo_url('/public/js/pesquisa.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
