<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Ta strona obsługuje wyłącznie żądania POST.');
}

if (!z9_valid_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: rejestracja.php?error=csrf');
    exit;
}

$userInput = $_POST['user'] ?? '';
$password = $_POST['pass1'] ?? '';
$passwordConfirmation = $_POST['pass2'] ?? '';
if (!is_string($userInput) || !is_string($password) || $password === ''
    || !is_string($passwordConfirmation) || $passwordConfirmation === '') {
    header('Location: rejestracja.php?error=empty');
    exit;
}

$username = trim($userInput);
if (!z9_is_valid_username($username)) {
    header('Location: rejestracja.php?error=username');
    exit;
}

if (!hash_equals($password, $passwordConfirmation)) {
    header('Location: rejestracja.php?error=password');
    exit;
}

$avatar = 'default.png';
$upload = $_FILES['avatar'] ?? null;
if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $temporaryPath = $upload['tmp_name'] ?? '';
    $fileSize = isset($upload['size']) ? (int)$upload['size'] : 0;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);

    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !is_string($temporaryPath)
        || !is_uploaded_file($temporaryPath)
        || $fileSize < 1
        || $fileSize > 2 * 1024 * 1024
        || $fileInfo === false) {
        if ($fileInfo !== false) {
            finfo_close($fileInfo);
        }
        header('Location: rejestracja.php?error=avatar');
        exit;
    }

    $mimeType = finfo_file($fileInfo, $temporaryPath);
    finfo_close($fileInfo);
    $imageInfo = getimagesize($temporaryPath);
    $imageData = file_get_contents($temporaryPath);
    if (!is_string($mimeType) || !in_array($mimeType, $allowedTypes, true)
        || $imageInfo === false || !is_string($imageData)) {
        header('Location: rejestracja.php?error=avatar');
        exit;
    }

    $avatar = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
}

require_once __DIR__ . '/db.php';

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$statement = mysqli_prepare($link, 'INSERT INTO users (`username`, `password`, `avatar`) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($statement, 'sss', $username, $passwordHash, $avatar);
try {
    mysqli_stmt_execute($statement);
    $userId = mysqli_insert_id($link);
} catch (mysqli_sql_exception $exception) {
    mysqli_stmt_close($statement);
    if ((int)$exception->getCode() === 1062) {
        mysqli_close($link);
        header('Location: rejestracja.php?error=duplicate');
        exit;
    }

    error_log('Z9 registration error: ' . $exception->getMessage());
    mysqli_close($link);
    header('Location: rejestracja.php?error=database');
    exit;
}
mysqli_stmt_close($statement);
mysqli_close($link);

try {
    z9_ensure_home_directory($username);
} catch (RuntimeException $exception) {
    error_log('Z9 user directory error: ' . $exception->getMessage());
    require_once __DIR__ . '/db_connection.php';
    $cleanup = mysqli_prepare($link, 'DELETE FROM users WHERE id = ?');
    mysqli_stmt_bind_param($cleanup, 'i', $userId);
    mysqli_stmt_execute($cleanup);
    mysqli_stmt_close($cleanup);
    mysqli_close($link);
    header('Location: rejestracja.php?error=folder');
    exit;
}

header('Location: index.php?registered=1');
exit;
