<?php
include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';
?>

<section class="chats-container">
    <button  class="chats-btn-create" onclick="window.location.href='criar-comunidade.php'">
      +
    </button>

    <h1 class="chats-titulo">
        Conversas
    </h1>

    <div class="chats-options">
        
    </div>

    <div class="seletor-tipo">
    <span class="seletor-indicador"></span>

    <button class="opcao" data-tipo="privados" onclick="window.location.href='chats.php'">
        Privados
    </button>

    <button class="opcao" data-tipo="grupos" onclick="window.location.href='chats-grupos.php'">
        Grupos
    </button>

    <button class="opcao ativa" data-tipo="comunidade" onclick="window.location.href='chats-comunidade.php'">
        Comunidade
    </button>
</div>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>