<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/security.php';
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo json_encode([
    'id' => zubbo_url('/'),
    'name' => 'Zubbo — Encontros Esportivos',
    'short_name' => 'Zubbo',
    'start_url' => zubbo_url('/app/views/painel/Painel-inicial.php'),
    'scope' => zubbo_url('/'),
    'display' => 'standalone',
    'background_color' => '#171717',
    'theme_color' => '#ef4b25',
    'icons' => [
        ['src' => zubbo_url('/public/imagem/ZubboSemFundo.png'),
         'sizes' => 'any', 'type' => 'image/png', 'purpose' => 'any'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
