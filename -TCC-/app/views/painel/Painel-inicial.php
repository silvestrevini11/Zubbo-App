<?php

session_start();

include __DIR__ .'/../includes/head.php';
include __DIR__.'/../../../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

$nomeUsuario = $_SESSION['usuario']['nome'];

?>
<section style="padding-bottom: 80px;">

<div class="painel-top">

<a
    href="../notificacoes/notificacoes.php"
    class="painel-notificacao-link"
>
    <img
        class="painel-notificacao"
        src="../../../public/imagem/Sino.png"
        alt="Notificações"
    >

    <span
        class="painel-notificacao-contador"
        id="painel-notificacao-contador"
    ></span>
</a>

<h1 class="painel-saudacoes">Olá <strong class="painel-saudacoes-cor"><?= htmlspecialchars($nomeUsuario) ?></strong></h1>

<h4 class="painel-sub-saudacoes">Pronto para <strong class="painel-sub-saudacoes-cor">jogar</strong> hoje</h4>
</div>

<div class="painel-search">
        <input type="text" placeholder="PESQUISAR..." name="text" class="painel-busca">
        <button class="painel-filtro"></button>
</div>

<div class="painel-esportes">
    <button class="painel-esporte-todos"><img class="painel-todos-img" src="" alt="">Todos</button>
    <button class="painel-esporte-futebol"><img class="painel-futebol-img" src="" alt="">Futebol</button>
    <button class="painel-esporte-basquete"><img class="painel-basquete-img" src="" alt="">Basquete</button>
    <button class="painel-esporte-volei"><img class="painel-volei-img" src="" alt="">Volei</button>
    <button class="painel-esporte-corrida"><img class="painel-corrida-img" src="" alt="">Corrida</button>
    <button class="painel-esporte-outros"><img class="painel-outros-img" src="" alt="">Outros</button>
</div>

<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<style>
  #map {
    width: 100%;
    height: 500px;
  }
</style>

<div id="map"></div>

<link
  href="https://api.mapbox.com/mapbox-gl-js/v3.29.0/mapbox-gl.css"
  rel="stylesheet"
/>

<script src="https://api.mapbox.com/mapbox-gl-js/v3.29.0/mapbox-gl.js"></script>

<script>
  mapboxgl.accessToken = 'pk.eyJ1Ijoia2lpbmd6ejAyMiIsImEiOiJjbXRseXozaHowMDNqMnlwa3djb2N5b2l2In0.5A9fNPCehI8fGGMcdgAV_A';

  const centroDiadema = [-46.623, -23.686];

  const limitesDiadema = [
    [-46.67, -23.73],
    [-46.59, -23.64]
  ];

  const estiloClaro = 'mapbox://styles/kiingzz022/cmtly3m06011101s91vtve38u';
  const estiloEscuro = 'mapbox://styles/kiingzz022/cmtlydg6j00co01s2e3hfh82s';

  const map = new mapboxgl.Map({
    container: 'map',
    style: document.documentElement.classList.contains('tema-escuro')
      ? estiloEscuro
      : estiloClaro,
    center: centroDiadema,
    zoom: 16,
    maxBounds: limitesDiadema
  });

  map.addControl(
    new mapboxgl.NavigationControl(),
    'top-right'
  );

  const marcador = new mapboxgl.Marker({
    color: '#e63946'
  })
    .setLngLat(centroDiadema)
    .setPopup(
      new mapboxgl.Popup({
        offset: 25
      }).setHTML(
        '<strong class="painel-marker">Diadema - SP</strong>'
      )
    )
    .addTo(map);

  function atualizarTemaMapa() {
    const temaEscuro = document.documentElement.classList.contains('tema-escuro');

    map.setStyle(temaEscuro ? estiloEscuro : estiloClaro);
  }

  const observer = new MutationObserver(() => {
    atualizarTemaMapa();
  });

  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  });
</script>



<h3 class="painel-atv">Atividades Proximas</h3>
<h4 class="painel-all-atv"><strong class="painel-all-atv-cor">Ver todas</strong> ></h4>

<script>

const contadorNotificacao =
    document.getElementById('painel-notificacao-contador');


async function atualizarNotificacoes() {

    try {

        const resposta = await fetch(
            '../notificacoes/buscar-notificacoes.php'
        );

        if (!resposta.ok) {
            return;
        }

        const dados = await resposta.json();

        const quantidade = dados.quantidade;


        if (quantidade > 0) {

            contadorNotificacao.textContent =
                quantidade > 99 ? '99+' : quantidade;

            contadorNotificacao.style.display = 'flex';

        } else {

            contadorNotificacao.textContent = '';

            contadorNotificacao.style.display = 'none';

        }

    } catch (erro) {

        console.error(
            'Erro ao buscar notificações:',
            erro
        );

    }

}


/*
    Verifica imediatamente
*/

atualizarNotificacoes();


/*
    Verifica novas notificações
    a cada 1 segundo.
*/

setInterval(atualizarNotificacoes, 1000);

</script>

</section>
<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>