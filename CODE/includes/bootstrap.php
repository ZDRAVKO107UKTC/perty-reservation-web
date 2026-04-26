<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Europe/Sofia');

define('APP_ROOT', realpath(__DIR__ . '/..') ?: (__DIR__ . '/..'));
const DB_HOST = 'localhost';
const DB_NAME = 'reservation_software';
const DB_CHARSET = 'utf8mb4';
const DB_USER = 'root';
const DB_PASS = '';

function app_base_url(): string
{
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $appFolder = basename(APP_ROOT);
    $needle = '/' . $appFolder;
    $position = strpos($scriptName, $needle);

    if ($position === false) {
        $baseUrl = '';
        return $baseUrl;
    }

    $baseUrl = substr($scriptName, 0, $position + strlen($needle));
    return $baseUrl;
}

function url_for(string $path = ''): string
{
    $baseUrl = app_base_url();

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function redirect(string $path, int $statusCode = 303): never
{
    $target = preg_match('#^https?://#i', $path) ? $path : url_for($path);
    header('Location: ' . $target, true, $statusCode);
    exit();
}

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $connection = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException) {
        http_response_code(500);
        exit('Database connection failed. Please check the local MySQL setup.');
    }

    return $connection;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }

    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    return $flash;
}

function flash_type_class(string $type): string
{
    return match ($type) {
        'success' => 'success',
        'warning' => 'warning',
        'danger', 'error' => 'danger',
        default => 'info',
    };
}

function normalize_role(?string $role): string
{
    return strtolower((string) $role) === 'admin' ? 'admin' : 'user';
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['id_person'] = (int) $user['id_person'];
    $_SESSION['first_name'] = $user['first_name_person'] ?? '';
    $_SESSION['last_name'] = $user['last_name_person'] ?? '';
    $_SESSION['email'] = $user['email_person'] ?? '';
    $_SESSION['phone_number'] = $user['phone_number_person'] ?? '';
    $_SESSION['role'] = normalize_role($user['role'] ?? 'user');
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function is_logged_in(): bool
{
    return isset($_SESSION['id_person']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? (int) $_SESSION['id_person'] : null;
}

function current_user_role(): string
{
    return normalize_role($_SESSION['role'] ?? 'user');
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Моля, влезте в профила си, за да продължите.');
        redirect('logIn.php');
    }
}

function require_admin(): void
{
    if (current_user_role() !== 'admin') {
        set_flash('danger', 'Нямате достъп до администраторския панел.');
        redirect('logIn.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function require_valid_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['_csrf_token'] ?? '';

    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Invalid form token. Please refresh the page and try again.');
    }
}

function format_datetime(?string $value, string $fallback = '-'): string
{
    if (!$value) {
        return $fallback;
    }

    try {
        $date = new DateTimeImmutable($value);
    } catch (Exception) {
        return $value;
    }

    return $date->format('d.m.Y H:i');
}

function format_datetime_local_value(?string $value): string
{
    if (!$value) {
        return '';
    }

    try {
        $date = new DateTimeImmutable($value);
    } catch (Exception) {
        return '';
    }

    return $date->format('Y-m-d\TH:i');
}

function parse_datetime_local(string $value): ?string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

    if (!$date) {
        return null;
    }

    return $date->format('Y-m-d H:i:s');
}

function reservation_write_error_message(PDOException $exception, string $defaultMessage): string
{
    $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
    $details = (string) ($exception->errorInfo[2] ?? $exception->getMessage());

    if ($sqlState === '23000') {
        if (str_contains($details, 'uniq_person_party')) {
            return 'За това събитие вече има активна резервация за този профил.';
        }

        if (str_contains($details, 'uniq_party_table')) {
            return 'Избраната маса вече е заета за това събитие.';
        }
    }

    return $defaultMessage;
}

function reservation_table_areas(): array
{
    return [
        1 => '205,366,331,464',
        2 => '418,368,549,464',
        3 => '634,367,765,465',
        4 => '834,368,966,464',
        5 => '1212,366,1342,465',
        6 => '121,101,252,210',
        7 => '370,101,500,209',
        8 => '617,104,748,209',
        9 => '884,101,1012,210',
        10 => '927,262,998,334',
        11 => '1038,223,1113,295',
        12 => '1096,307,1169,379',
    ];
}
