<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    session_name('Z9SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function z9_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function z9_is_valid_username(string $username): bool
{
    return preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,99}\z/', $username) === 1;
}

function z9_ensure_home_directory(string $username): string
{
    if (!z9_is_valid_username($username)) {
        throw new RuntimeException('Nieprawidłowa nazwa katalogu użytkownika.');
    }

    $home = __DIR__ . DIRECTORY_SEPARATOR . $username;
    if (is_link($home)) {
        throw new RuntimeException('Katalog użytkownika nie może być dowiązaniem symbolicznym.');
    }
    if (!is_dir($home) && !mkdir($home, 0777, true) && !is_dir($home)) {
        throw new RuntimeException('Nie udało się utworzyć katalogu użytkownika.');
    }

    $rulesPath = $home . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($rulesPath)) {
        $rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
        if (file_put_contents($rulesPath, $rules, LOCK_EX) === false) {
            throw new RuntimeException('Nie udało się zabezpieczyć katalogu użytkownika.');
        }
    }

    return $home;
}

function z9_require_login(bool $jsonResponse = false): void
{
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        return;
    }

    if ($jsonResponse) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        exit('{"error":"Zaloguj się ponownie."}');
    }

    header('Location: index.php');
    exit;
}

function z9_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function z9_valid_csrf_token($token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function z9_record_visit(mysqli $link, string $username, string $status): void
{
    $ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $browser = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $username = substr($username, 0, 128);

    try {
        $statement = mysqli_prepare(
            $link,
            'INSERT INTO goscieportalu (`username`, ipaddress, browser, status) VALUES (?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($statement, 'ssss', $username, $ipAddress, $browser, $status);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
    } catch (mysqli_sql_exception $exception) {
        error_log('Z9 visit log error: ' . $exception->getMessage());
    }
}
