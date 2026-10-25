<?php
declare(strict_types=1);

// Only a public Mapbox token may be delivered to the browser.
function zubbo_map_config(): array
{
    $token = trim((string) getenv('ZUBBO_MAPBOX_PUBLIC_TOKEN'));
    if (!preg_match('/^pk\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/D', $token)) {
        $token = '';
    }
    $style = static function (string $variable, string $fallback): string {
        $value = trim((string) getenv($variable));
        return preg_match('~^mapbox://styles/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+$~D', $value)
            ? $value : $fallback;
    };
    return [
        'public_token' => $token,
        'light_style' => $style('ZUBBO_MAPBOX_STYLE_LIGHT', 'mapbox://styles/mapbox/streets-v12'),
        'dark_style' => $style('ZUBBO_MAPBOX_STYLE_DARK', 'mapbox://styles/mapbox/dark-v11'),
    ];
}
