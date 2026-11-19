<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Ta strona obsługuje wyłącznie żądania POST.');
}

if (!z9_valid_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: index.php?error=csrf');
    exit;
}

$userInput = $_POST['user'] ?? '';
$password = $_POST['pass'] ?? '';
if (!is_string($userInput) || !is_string($password) || trim($userInput) === '' || $password === '') {
    header('Location: index.php?error=login');
    exit;
}
$username = trim($userInput);

require_once __DIR__ . '/db.php';

$statement = mysqli_prepare($link, 'SELECT id, `username`, `password`, `avatar` FROM users WHERE `username` = ? LIMIT 1');
mysqli_stmt_bind_param($statement, 's', $username);
mysqli_stmt_execute($statement);
$recordId = 0;
$recordUsername = '';
$passwordHash = '';
$avatar = '';
mysqli_stmt_bind_result($statement, $recordId, $recordUsername, $passwordHash, $avatar);
$found = mysqli_stmt_fetch($statement);
mysqli_stmt_close($statement);

if ($found !== true || !password_verify($password, $passwordHash)) {
    z9_record_visit($link, $username, 'FAILED');
    mysqli_close($link);
    header('Location: index.php?error=login');
    exit;
}

z9_record_visit($link, $recordUsername, 'SUCCESS');
mysqli_close($link);

try {
    z9_ensure_home_directory($recordUsername);
} catch (RuntimeException $exception) {
    error_log('Z9 login directory error: ' . $exception->getMessage());
    header('Location: index.php?error=folder');
    exit;
}

session_regenerate_id(true);
$_SESSION['loggedin'] = true;
$_SESSION['username'] = $recordUsername;
$_SESSION['user_id'] = (int)$recordId;
$_SESSION['temp_avatar_data'] = $avatar;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: komunikator.php');
exit;
