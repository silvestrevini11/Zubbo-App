<?php
declare(strict_types=1);

// Impressão pública da chave que assina br.com.zubbo.app. A chave privada fica fora do Git.
$fingerprint = '73:5D:E3:CF:47:04:5A:3B:98:D3:7D:39:23:8D:89:A3:A2:63:A4:DF:00:62:68:F3:EA:81:42:0B:C1:7A:47:02';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo json_encode([[
    'relation' => ['delegate_permission/common.handle_all_urls'],
    'target' => [
        'namespace' => 'android_app',
        'package_name' => 'br.com.zubbo.app',
        'sha256_cert_fingerprints' => [$fingerprint],
    ],
]], JSON_UNESCAPED_SLASHES);
