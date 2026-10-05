<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

header('Location: ../auth/login.php');
exit;
