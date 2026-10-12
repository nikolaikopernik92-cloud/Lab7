<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/session.php';
require dirname(__DIR__) . '/src/data.php';
requireLogin();

// POST: сохранение cookie-настройки "обращений на странице"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $value = $_POST['per_page'] ?? null;
    if (
        checkCsrf($_POST['csrf_token'] ?? null)
        && is_string($value)
        && in_array($value, ['5', '10', '20'], true)
    ) {
        setcookie('per_page', $value, [
            'expires'  => time() + 2592000,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        header('Location: /dashboard.php');
        exit;
    }
    http_response_code(403);
    exit('Invalid CSRF token');
}

$isSupport = $_SESSION['role'] === 'support';
$tickets   = loadTickets();
if (!$isSupport) { // пользователь видит только свои обращения
    $tickets = array_values(array_filter(
        $tickets,
        fn(array $t): bool => $t['owner_id'] === $_SESSION['user_id']
    ));
}

$perPage = perPage();
$pages   = max(1, (int) ceil(count($tickets) / $perPage));
$page    = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$page    = min(max($page, 1), $pages);
$slice   = array_slice($tickets, ($page - 1) * $perPage, $perPage);
?>
<!doctype html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Обращения</title></head>
<body>
<h1>Мои обращения</h1>
<p>Пользователь: <?= h((string) $_SESSION['email']) ?></p>
<p>Роль: <?= h((string) $_SESSION['role']) ?></p>
<?php if ($isSupport): ?>
    <p><a href="/support.php">Назначить приоритеты</a></p>
<?php endif; ?>

<table border="1" cellpadding="4">
    <tr><th>№</th><th>Тема</th><th>Приоритет</th></tr>
    <?php foreach ($slice as $t): ?>
        <tr>
            <td><?= h((string) $t['id']) ?></td>
            <td><?= h((string) $t['title']) ?></td>
            <td><?= h((string) $t['priority']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<p>
    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="/dashboard.php?page=<?= $p ?>"><?= $p ?></a>
    <?php endfor; ?>
</p>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
    Обращений на странице:
    <select name="per_page">
        <?php foreach ([5, 10, 20] as $n): ?>
            <option value="<?= $n ?>" <?= $n === $perPage ? 'selected' : '' ?>><?= $n ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Сохранить</button>
</form>

<form method="post" action="/logout.php">
    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
    <button type="submit">Выйти</button>
</form>
</body>
</html>
