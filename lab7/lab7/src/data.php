<?php
declare(strict_types=1);

// Учебные пользователи. В реальной системе хеши хранятся в БД.
const PRIORITIES = ['low', 'normal', 'high'];

function demoUsers(): array
{
    static $users = null;
    $users ??= [
        'user@atu.edu.kz' => [
            'id' => 101, 'role' => 'user',
            'hash' => password_hash('User123!', PASSWORD_DEFAULT),
        ],
        'support@atu.edu.kz' => [
            'id' => 201, 'role' => 'support',
            'hash' => password_hash('Support123!', PASSWORD_DEFAULT),
        ],
    ];
    return $users;
}

// Проверка учётных данных; возвращает данные пользователя или null
function authenticate(string $email, string $password): ?array
{
    $users = demoUsers();
    $user  = $users[$email] ?? null;
    // Фиктивный хеш выравнивает время ответа для неизвестного e-mail
    $hash  = $user['hash'] ?? password_hash('dummy', PASSWORD_DEFAULT);
    $ok    = password_verify($password, $hash);
    return ($user !== null && $ok) ? $user : null;
}

const TICKETS_FILE = __DIR__ . '/../storage/tickets.json';

function loadTickets(): array
{
    if (!is_file(TICKETS_FILE)) {
        $seed = [];
        for ($i = 1; $i <= 25; $i++) {
            $seed[] = [
                'id'       => $i,
                'owner_id' => $i % 3 === 0 ? 102 : 101,
                'title'    => "Обращение №$i: проблема с доступом",
                'priority' => 'normal',
            ];
        }
        file_put_contents(TICKETS_FILE, json_encode($seed, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $seed;
    }
    $data = json_decode((string) file_get_contents(TICKETS_FILE), true);
    return is_array($data) ? $data : [];
}

function setTicketPriority(int $ticketId, string $priority): bool
{
    if (!in_array($priority, PRIORITIES, true)) {
        return false;
    }
    $tickets = loadTickets();
    foreach ($tickets as &$t) {
        if ($t['id'] === $ticketId) {
            $t['priority'] = $priority;
            file_put_contents(TICKETS_FILE, json_encode($tickets, JSON_UNESCAPED_UNICODE), LOCK_EX);
            return true;
        }
    }
    return false;
}

// Cookie-настройка: количество обращений на странице (allow-list)
function perPage(): int
{
    $allowed = [5, 10, 20];
    $raw = $_COOKIE['per_page'] ?? null;
    if (is_string($raw) && ctype_digit($raw) && in_array((int) $raw, $allowed, true)) {
        return (int) $raw;
    }
    return 10;
}
