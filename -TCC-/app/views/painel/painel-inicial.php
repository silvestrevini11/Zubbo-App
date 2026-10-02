<?php

session_start();

include __DIR__.'/../../../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

$nomeUsuario = $_SESSION['usuario']['nome'];
$locaisMapa = [];
$erroLocaisMapa = false;
try {
    $stmtLocaisMapa = $conn->query("
        SELECT DISTINCT nome_local, endereco_local, tipo_local
        FROM LocalEsp
        WHERE status_local = 'aprovado'
          AND endereco_local LIKE '%Diadema%'
        ORDER BY nome_local
    ");
    $locaisMapa = $stmtLocaisMapa->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log('Erro ao carregar locais do mapa: ' . $erro->getMessage());
    $erroLocaisMapa = true;
}
$filtrosEventos = ['todos' => 'Todos', 'futsal' => 'Futsal', 'volei' => 'Vôlei', 'futebol' => 'Futebol'];
$filtroEvento = is_string($_GET['esporte'] ?? null) ? $_GET['esporte'] : 'todos';
if (!isset($filtrosEventos[$filtroEvento])) $filtroEvento = 'todos';
$buscaEvento = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$eventosPainel = [];
$erroEventosPainel = false;
try {
    $sqlEventos = "
        SELECT ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento,
               e.nome_esporte, l.nome_local,
               (SELECT COUNT(*) FROM Lista_Evento le WHERE le.id_evento = ev.id_evento) AS integrantes
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        WHERE ev.status_evento = 'ativo'
          AND TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()
    ";
    $parametrosEventos = [];
    if ($filtroEvento !== 'todos') {
        $sqlEventos .= ' AND e.nome_esporte = ?';
        $parametrosEventos[] = $filtrosEventos[$filtroEvento];
    }
    if ($buscaEvento !== '') {
        $sqlEventos .= ' AND (ev.nome_evento LIKE ? OR l.nome_local LIKE ?)';
        $parametrosEventos[] = '%' . $buscaEvento . '%';
        $parametrosEventos[] = '%' . $buscaEvento . '%';
    }
    $sqlEventos .= ' ORDER BY ev.data_evento, ev.horario_evento, ev.id_evento LIMIT 30';
    $stmtEventos = $conn->prepare($sqlEventos);
    $stmtEventos->execute($parametrosEventos);
    $eventosPainel = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log('Erro ao carregar eventos do painel: ' . $erro->getMessage());
    $erroEventosPainel = true;
}
function escaparPainelEvento($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

include __DIR__ . '/../includes/head.php';

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

<link rel="stylesheet" href="../../../public/css/painel-eventos.css">
<form class="painel-eventos-filtros" method="get" action="painel-inicial.php">
    <label for="painel-busca-evento">Pesquisar eventos</label>
    <div class="painel-eventos-busca">
        <input id="painel-busca-evento" name="q" type="search" maxlength="100" placeholder="Nome do evento ou local" value="<?= escaparPainelEvento($buscaEvento) ?>">
        <button type="submit" name="esporte" value="<?= escaparPainelEvento($filtroEvento) ?>">Pesquisar</button>
    </div>
    <div class="painel-eventos-modalidades" role="group" aria-label="Filtrar eventos por esporte">
        <?php foreach ($filtrosEventos as $chave => $rotulo): ?>
        <button type="submit" name="esporte" value="<?= $chave ?>" aria-pressed="<?= $filtroEvento === $chave ? 'true' : 'false' ?>"><?= $rotulo ?></button>
        <?php endforeach; ?>
    </div>
</form>

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

<div id="map" role="region" aria-label="Mapa dos locais esportivos de Diadema"></div>
<p id="painel-status-mapa" role="status" style="padding: 12px 20px; font-size: 13px;">Carregando locais esportivos...</p>

<link
  href="https://api.mapbox.com/mapbox-gl-js/v3.29.0/mapbox-gl.css"
  rel="stylesheet"
/>

<script src="https://api.mapbox.com/mapbox-gl-js/v3.29.0/mapbox-gl.js"></script>

<script>
  mapboxgl.accessToken = 'pk.eyJ1Ijoia2lpbmd6ejAyMiIsImEiOiJjbXR2eHhsODUwMzFjMnhxYmswOTRncmh5In0.MeIvXt_Cxk4WZbjYjKMEYw';

  const centroDiadema = [-46.623, -23.686];
  

  // Mapbox usa [longitude, latitude].


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
    zoom: 13,
    maxBounds: limitesDiadema
  });

  map.addControl(
    new mapboxgl.NavigationControl(),
    'top-right'
  );

  function criarPopupLocal(nome, endereco) {
    const conteudo = document.createElement('div');
    conteudo.style.color = '#222';
    const titulo = document.createElement('strong');
    titulo.textContent = nome;
    const descricao = document.createElement('p');
    descricao.textContent = endereco;
    conteudo.append(titulo, descricao);
    return new mapboxgl.Popup({ offset: 25 }).setDOMContent(conteudo);
  }

  new mapboxgl.Marker({ color: '#e63946' })
    .setLngLat(centroDiadema)
    .setPopup(criarPopupLocal('Centro de Diadema', 'Diadema - SP'))
    .addTo(map);

  const locaisMapa = <?= json_encode($locaisMapa, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
  const erroLocaisMapa = <?= $erroLocaisMapa ? 'true' : 'false' ?>;
  const statusMapa = document.getElementById('painel-status-mapa');

  async function carregarMarcadoresLocais() {
    if (erroLocaisMapa) {
      statusMapa.textContent = 'Não foi possível carregar os locais cadastrados.';
      return;
    }
    if (!locaisMapa.length) {
      statusMapa.textContent = 'Nenhum local aprovado de Diadema cadastrado.';
      return;
    }
    let adicionados = 0;
    let falhas = 0;
    for (const local of locaisMapa) {
      const controlador = new AbortController();
      const tempoLimite = setTimeout(() => controlador.abort(), 10000);
      try {
        const parametros = new URLSearchParams({
          q: local.endereco_local,
          country: 'br',
          language: 'pt',
          types: 'address',
          autocomplete: 'false',
          limit: '1',
          bbox: limitesDiadema.flat().join(','),
          proximity: centroDiadema.join(','),
          access_token: mapboxgl.accessToken
        });
        const resposta = await fetch(
          'https://api.mapbox.com/search/geocode/v6/forward?' + parametros,
          { signal: controlador.signal }
        );
        if (!resposta.ok) throw new Error('Falha ao localizar endereço');
        const dados = await resposta.json();
        const resultado = dados.features && dados.features[0];
        const coordenadas = resultado && resultado.geometry && resultado.geometry.coordinates;
        if (!coordenadas || resultado.geometry.type !== 'Point' ||
            !coordenadas.every(Number.isFinite) ||
            coordenadas[0] < limitesDiadema[0][0] || coordenadas[0] > limitesDiadema[1][0] ||
            coordenadas[1] < limitesDiadema[0][1] || coordenadas[1] > limitesDiadema[1][1]) {
          throw new Error('Endereço não localizado em Diadema');
        }
        new mapboxgl.Marker({ color: '#ef4b25' })
          .setLngLat(coordenadas)
          .setPopup(criarPopupLocal(local.nome_local, local.endereco_local))
          .addTo(map);
        adicionados++;
      } catch (erro) {
        falhas++;
        console.warn('Não foi possível marcar o local:', local.nome_local, erro);
      } finally {
        clearTimeout(tempoLimite);
      }
      statusMapa.textContent = adicionados + ' de ' + locaisMapa.length + ' locais no mapa.';
    }
    if (falhas) {
      statusMapa.textContent += ' ' + falhas + ' endereço(s) não localizado(s).';
    }
  }
  carregarMarcadoresLocais();

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



<section class="painel-eventos-lista" aria-labelledby="painel-eventos-titulo">
    <div class="painel-eventos-cabecalho">
        <h2 id="painel-eventos-titulo">Próximos eventos<?= $filtroEvento !== 'todos' ? ' de ' . escaparPainelEvento($filtrosEventos[$filtroEvento]) : '' ?></h2>
        <a href="../eventos/eventos.php">Ver todos →</a>
    </div>
    <?php if ($erroEventosPainel): ?>
        <p class="painel-eventos-vazio" role="alert">Não foi possível carregar os eventos. Tente novamente.</p>
    <?php elseif (!$eventosPainel): ?>
        <p class="painel-eventos-vazio">Nenhum evento encontrado para este filtro.</p>
        <a class="painel-eventos-link" href="../eventos/criar-evento.php">Criar um evento</a>
    <?php else: ?>
        <div class="painel-eventos-grid">
        <?php foreach ($eventosPainel as $evento): ?>
            <article class="painel-evento-card">
                <span class="painel-evento-esporte"><?= escaparPainelEvento($evento['nome_esporte']) ?></span>
                <h3><?= escaparPainelEvento($evento['nome_evento']) ?></h3>
                <p><?= escaparPainelEvento(date('d/m/Y', strtotime($evento['data_evento']))) ?> às <?= escaparPainelEvento(substr($evento['horario_evento'], 0, 5)) ?></p>
                <p><?= escaparPainelEvento($evento['nome_local']) ?></p>
                <p><?= (int) $evento['integrantes'] ?> integrante(s)</p>
                <a class="painel-eventos-link" href="../eventos/detalhes-evento.php?id_evento=<?= (int) $evento['id_evento'] ?>">Entrar no evento →</a>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

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