<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/session.php';
require dirname(__DIR__) . '/src/data.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
$email = '';
$_SESSION['login_csrf'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue    = $_POST['email'] ?? null;
    $passwordValue = $_POST['password'] ?? null;
    $tokenValue    = $_POST['csrf_token'] ?? null;

    if (
        is_string($emailValue)
        && is_string($passwordValue)
        && is_string($tokenValue)
        && hash_equals($_SESSION['login_csrf'], $tokenValue)
    ) {
        $email = strtolower(trim($emailValue));
        $user  = authenticate($email, $passwordValue);
        if ($user !== null) {
            loginUser($user['id'], $email, $user['role']);
            header('Location: /dashboard.php');
            exit;
        }
    }
    $error = 'Неверный e-mail или пароль.';
}
?>
<!doctype html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Вход</title></head>
<body>
<h1>Служба технической поддержки</h1>
<?php if (isset($_GET['expired'])): ?>
    <p>Сеанс завершён из-за бездействия.</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <p><?= h($error) ?></p>
<?php endif; ?>
<form method="post" action="/login.php">
    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['login_csrf']) ?>">
    <label>E-mail
        <input name="email" type="email" required value="<?= h($email) ?>">
    </label>
    <label>Пароль
        <input name="password" type="password" required>
    </label>
    <button type="submit">Войти</button>
</form>
</body>
</html>
