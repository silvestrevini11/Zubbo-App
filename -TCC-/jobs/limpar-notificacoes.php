<?php
declare(strict_types=1);

// Executar via cron/Task Scheduler a cada minuto. Não acessível pela web.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/database.php';
try {
    $stmt = $conn->prepare("DELETE FROM Notificacao
        WHERE lida = TRUE AND data_leitura IS NOT NULL
          AND data_leitura <= DATE_SUB(NOW(), INTERVAL 20 MINUTE)");
    $stmt->execute();
    echo 'Notificações lidas expiradas: '.$stmt->rowCount().PHP_EOL;
} catch (Throwable $e) {
    error_log('Falha ao limpar notificações expiradas: '.get_class($e));
    exit(1);
}
