<?php
declare(strict_types=1);
require __DIR__ . '/../config/map.php';
function check(bool $ok, string $label): void {
    if (!$ok) { throw new RuntimeException($label); }
    echo "OK: $label\n";
}
putenv('ZUBBO_MAPBOX_PUBLIC_TOKEN');
putenv('ZUBBO_MAPBOX_STYLE_LIGHT');
putenv('ZUBBO_MAPBOX_STYLE_DARK');
check(zubbo_map_config()['public_token'] === '', 'missing token disables map');
putenv('ZUBBO_MAPBOX_PUBLIC_TOKEN=sk.test.signature');
check(zubbo_map_config()['public_token'] === '', 'secret token never reaches browser');
putenv('ZUBBO_MAPBOX_PUBLIC_TOKEN=pk.test.signature');
check(zubbo_map_config()['public_token'] === 'pk.test.signature', 'public token accepted');
putenv('ZUBBO_MAPBOX_PUBLIC_TOKEN=invalid');
check(zubbo_map_config()['public_token'] === '', 'invalid token rejected');
putenv('ZUBBO_MAPBOX_STYLE_LIGHT=javascript:alert(1)');
check(zubbo_map_config()['light_style'] === 'mapbox://styles/mapbox/streets-v12', 'invalid style rejected');
check(zubbo_map_config()['dark_style'] === 'mapbox://styles/mapbox/dark-v11', 'dark style default');
putenv('ZUBBO_MAPBOX_STYLE_LIGHT=mapbox://styles/example/custom');
check(zubbo_map_config()['light_style'] === 'mapbox://styles/example/custom', 'configured style accepted');
