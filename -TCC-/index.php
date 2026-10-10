<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';

header('Location: ' . zubbo_url('/public/index.php'), true, 302);
exit;
