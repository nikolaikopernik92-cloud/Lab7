<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
if (!checkCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid CSRF token');
}
logoutUser();
header('Location: /login.php');
exit;
