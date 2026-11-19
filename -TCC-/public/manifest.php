<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/security.php';
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo json_encode([
    'id' => zubbo_url('/'),
    'name' => 'Zubbo — Encontros Esportivos',
    'short_name' => 'Zubbo',
    'start_url' => zubbo_url('/public/index.php'),
    'scope' => zubbo_url('/'),
    'display' => 'standalone',
    'background_color' => '#171717',
    'theme_color' => '#ef4b25',
    'icons' => [
        ['src' => zubbo_url('/public/imagem/zubbo-icon-192.png'),
         'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => zubbo_url('/public/imagem/zubbo-icon-512.png'),
         'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
