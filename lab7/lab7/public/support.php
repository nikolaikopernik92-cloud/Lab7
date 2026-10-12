<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/session.php';
require dirname(__DIR__) . '/src/data.php';
requireRole('support'); // только специалист поддержки, иначе 403

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = filter_var($_POST['ticket_id'] ?? null, FILTER_VALIDATE_INT);
    $priority = $_POST['priority'] ?? null;

    if (!checkCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    if (is_int($id) && is_string($priority) && setTicketPriority($id, $priority)) {
        $message = 'Приоритет обновлён.';
    } else {
        http_response_code(400);
        $message = 'Некорректные данные.';
    }
}

$tickets = loadTickets();
$perPage = perPage();
$pages   = max(1, (int) ceil(count($tickets) / $perPage));
$page    = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$page    = min(max($page, 1), $pages);
$slice   = array_slice($tickets, ($page - 1) * $perPage, $perPage);
?>
<!doctype html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Приоритеты</title></head>
<body>
<h1>Назначение приоритетов</h1>
<p><a href="/dashboard.php">Назад</a></p>
<?php if ($message !== ''): ?><p><?= h($message) ?></p><?php endif; ?>
<table border="1" cellpadding="4">
    <tr><th>№</th><th>Тема</th><th>Приоритет</th></tr>
    <?php foreach ($slice as $t): ?>
        <tr>
            <td><?= h((string) $t['id']) ?></td>
            <td><?= h((string) $t['title']) ?></td>
            <td>
                <form method="post" action="/support.php?page=<?= $page ?>">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="ticket_id" value="<?= h((string) $t['id']) ?>">
                    <select name="priority">
                        <?php foreach (PRIORITIES as $p): ?>
                            <option value="<?= h($p) ?>" <?= $p === $t['priority'] ? 'selected' : '' ?>><?= h($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Сохранить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<p>
    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="/support.php?page=<?= $p ?>"><?= $p ?></a>
    <?php endfor; ?>
</p>
</body>
</html>
