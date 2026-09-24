<?php
$paginaAdmin = $paginaAdmin ?? '';
$tituloAdmin = $tituloAdmin ?? 'Painel administrativo';
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloAdmin) ?> | Zubbo Admin</title>
    <link rel="stylesheet" href="../../../public/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="painel.php" aria-label="Zubbo Admin">
            <img src="../../../public/imagem/LogooZ.png" alt="Zubbo">
            <div><strong>Zubbo</strong><span>Admin</span></div>
        </a>

        <nav class="admin-nav" aria-label="Menu administrativo">
            <a class="<?= $paginaAdmin === 'painel' ? 'ativo' : '' ?>" href="painel.php"><span>⌂</span>Visão geral</a>
            <a class="<?= $paginaAdmin === 'usuarios' ? 'ativo' : '' ?>" href="usuarios.php"><span>♟</span>Usuários</a>
            <a class="<?= $paginaAdmin === 'eventos' ? 'ativo' : '' ?>" href="eventos.php"><span>◫</span>Eventos</a>
            <a class="<?= $paginaAdmin === 'locais' ? 'ativo' : '' ?>" href="locais.php"><span>⌖</span>Locais</a>
            <a class="<?= $paginaAdmin === 'denuncias' ? 'ativo' : '' ?>" href="denuncias.php"><span>!</span>Denúncias</a>
            <a class="<?= $paginaAdmin === 'sugestoes' ? 'ativo' : '' ?>" href="sugestoes.php"><span>＋</span>Sugestões</a>
            <a class="<?= $paginaAdmin === 'acoes' ? 'ativo' : '' ?>" href="acoes.php"><span>≡</span>Histórico</a>
        </nav>

        <div class="admin-sidebar-bottom">
            <a href="../../../public/index.php" target="_blank" rel="noopener">Abrir Zubbo ↗</a>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                <button type="submit">Sair</button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-mobile" type="button" aria-label="Abrir menu" data-admin-menu>☰</button>
            <div>
                <p>PAINEL ADMINISTRATIVO</p>
                <h1><?= htmlspecialchars($tituloAdmin) ?></h1>
            </div>
            <div class="admin-user-chip">
                <span><?= htmlspecialchars(mb_substr($_SESSION['admin']['nome'] ?? 'A', 0, 1)) ?></span>
                <div><strong><?= htmlspecialchars($_SESSION['admin']['nome'] ?? 'Administrador') ?></strong><small><?= htmlspecialchars($_SESSION['admin']['email'] ?? '') ?></small></div>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="admin-alerta admin-alerta-<?= htmlspecialchars($flash['tipo']) ?>">
                <?= htmlspecialchars($flash['mensagem']) ?>
            </div>
        <?php endif; ?>

        <section class="admin-content">
