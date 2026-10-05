<?php
require_once __DIR__ . '/../../../config/security.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        if (localStorage.getItem('zubbo-tema') === 'escuro') {
            document.documentElement.classList.add('tema-escuro');
        }
    </script>
    <link rel="stylesheet" href="<?= htmlspecialchars(zubbo_url('/public/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <title>Zubbo</title>
</head>
<body>
