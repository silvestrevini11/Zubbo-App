<?php
require_once __DIR__ . '/../config/security.php';
zubbo_start_session();

if (isset($_SESSION['usuario'])) {
    header('Location: ../app/views/painel/painel-inicial.php');
    exit;
}

include __DIR__ . '/../app/views/includes/head.php';
?>
<section class="Tela-init">
    <svg class="Mark-init" fill="#ff7300" height="50px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true">
        <path d="M256,0C149.3,0,64,85.3,64,192c0,36.9,11,65.4,30.1,94.3l141.7,215c4.3,6.5,11.7,10.7,20.2,10.7c8.5,0,16-4.3,20.2-10.7l141.7-215C437,257.4,448,228.9,448,192C448,85.3,362.7,0,256,0z M256,298.6c-58.9,0-106.7-47.8-106.7-106.8S197.1,85,256,85s106.7,47.8,106.7,106.8S314.9,298.6,256,298.6z M256,128c-35.4,0-64,28.6-64,64s28.6,64,64,64s64-28.6,64-64S291.4,128,256,128z"/>
    </svg>

    <img class="Logo-init" src="./imagem/ZubboSemFundo.png" alt="Zubbo">
    <br>
    <h1 class="Titulo-init">Zubbo</h1>
    <h2 class="Sub-titulo-init">Encontre. Pratique. <strong>Conecte-se</strong></h2>
    <p class="desc-init">Descubra lugares e pessoas para praticar esportes perto de você</p>

    <button onclick="window.location.href='../app/views/auth/login.php'" class="btn-entrar-init">Entrar</button>
    <button onclick="window.location.href='../app/views/auth/cadastro.php'" class="btn-criar-init">Criar Conta</button>

    <p class="under-init">Inclusão, esporte e comunidade em um só lugar.</p>
</section>
<?php include __DIR__ . '/../app/views/includes/footer.php'; ?>
