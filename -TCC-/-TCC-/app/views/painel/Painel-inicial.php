<?php 
include __DIR__ .'/includes/head.php';
include __DIR__.'/database.php';
?>
<section style="padding-bottom: 80px;
}">

<div class="painel-top">

<a href="Notificações.php"><img class="painel-notificacao" src="./imagem/Sino.png" alt=""></a>

<h1 class="painel-saudacoes">Olá <strong id="painel-nome" class="painel-saudacoes-cor"></strong> </h1>

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
    height: 600px;
  }
</style>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  const map = L.map('map').setView([-23.686, -46.623], 16);

  const limitesDiadema = [
    [-23.73, -46.67],
    [-23.64, -46.59]
  ];

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  map.setMaxBounds(limitesDiadema);
  map.options.maxBoundsViscosity = 1.0;

  const marcador = L.marker([-23.686, -46.623])
    .addTo(map)
    .bindPopup('Diadema - SP');

  // Zoom no marcador
  map.setView([-23.686, -46.623], 16);
</script>

<h3 class="painel-atv">Atividades Proximas</h3>
<h4 class="painel-all-atv"><strong class="painel-all-atv-cor">Ver todas</strong> ></h4>

</section>
<?php
include __DIR__ . '/includes/under-bar.php';
include __DIR__ . '/includes/footer.php';
?>