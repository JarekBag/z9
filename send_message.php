<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
z9_require_login(true);
require_once __DIR__ . '/message_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    z9_json_response(405, ['error' => 'Ta strona obsługuje wyłącznie żądania POST.']);
}
if (!z9_valid_csrf_token($_POST['csrf_token'] ?? null)) {
    z9_json_response(403, ['error' => 'Sesja formularza wygasła. Odśwież stronę.']);
}

$recipientInput = $_POST['recipient'] ?? '';
$text = $_POST['message'] ?? '';
if (!is_string($recipientInput) || !is_string($text)) {
    z9_json_response(400, ['error' => 'Nieprawidłowe dane formularza.']);
}
$recipientInput = trim($recipientInput);
$text = trim($text);
if ($recipientInput === '') {
    z9_json_response(400, ['error' => 'Wybierz adresata.']);
}

$upload = $_FILES['fileToUpload'] ?? null;
$hasFile = is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
if (!$hasFile && $text === '') {
    z9_json_response(400, ['error' => 'Wpisz wiadomość lub wybierz plik.']);
}
if ($hasFile && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    z9_json_response(400, ['error' => 'Serwer nie przyjął pliku. Sprawdź limity uploadu hostingu i spróbuj ponownie.']);
}

require_once __DIR__ . '/db_connection.php';
$recipientStatement = mysqli_prepare($link, 'SELECT `username` FROM users WHERE `username` = ? LIMIT 1');
mysqli_stmt_bind_param($recipientStatement, 's', $recipientInput);
mysqli_stmt_execute($recipientStatement);
$recipient = '';
mysqli_stmt_bind_result($recipientStatement, $recipient);
$recipientFound = mysqli_stmt_fetch($recipientStatement);
mysqli_stmt_close($recipientStatement);
if ($recipientFound !== true) {
    mysqli_close($link);
    z9_json_response(400, ['error' => 'Wybrany adresat nie istnieje.']);
}

$sender = (string)$_SESSION['username'];
$storedPath = '';
if ($hasFile) {
    $temporaryPath = $upload['tmp_name'] ?? '';
    $originalName = $upload['name'] ?? '';
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath) || !is_string($originalName)) {
        mysqli_close($link);
        z9_json_response(400, ['error' => 'Nie udało się odczytać przesłanego pliku.']);
    }

    $originalName = basename(str_replace('\\', '/', $originalName));
    $originalName = preg_replace('/[\x00-\x1F\x7F]/', '', $originalName);
    if (!is_string($originalName) || $originalName === '') {
        $originalName = 'zalacznik';
    }
    $originalName = substr($originalName, 0, 255);

    try {
        $home = z9_ensure_home_directory($sender);
        $storedName = bin2hex(random_bytes(16)) . '.upload';
        $storedPath = $sender . '/' . $storedName;
        $destination = $home . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($temporaryPath, $destination)) {
            mysqli_close($link);
            z9_json_response(500, ['error' => 'Nie udało się zapisać pliku w katalogu użytkownika.']);
        }
        $message = z9_encode_file_message($storedPath, $originalName, $text);
    } catch (Exception $exception) {
        if ($storedPath !== '') {
            $storedName = basename($storedPath);
            $storedFile = __DIR__ . DIRECTORY_SEPARATOR . $sender . DIRECTORY_SEPARATOR . $storedName;
            if (is_file($storedFile)) {
                if (!unlink($storedFile)) {
                    error_log('Z9 upload cleanup failed: ' . $storedFile);
                }
            }
        }
        mysqli_close($link);
        error_log('Z9 upload preparation error: ' . $exception->getMessage());
        z9_json_response(500, ['error' => 'Nie udało się przygotować pliku do wysłania.']);
    }
} else {
    $message = $text;
}

$statement = mysqli_prepare($link, 'INSERT INTO messages (message, `user`, `recipient`) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($statement, 'sss', $message, $sender, $recipient);
try {
    mysqli_stmt_execute($statement);
} catch (mysqli_sql_exception $exception) {
    mysqli_stmt_close($statement);
    if ($storedPath !== '') {
        $storedName = basename($storedPath);
        $storedFile = __DIR__ . DIRECTORY_SEPARATOR . $sender . DIRECTORY_SEPARATOR . $storedName;
        if (is_file($storedFile) && !unlink($storedFile)) {
            error_log('Z9 orphaned upload cleanup failed: ' . $storedFile);
        }
    }
    mysqli_close($link);
    error_log('Z9 send message error: ' . $exception->getMessage());
    z9_json_response(500, ['error' => 'Nie udało się zapisać wiadomości.']);
}

mysqli_stmt_close($statement);
mysqli_close($link);
z9_json_response(201, ['message' => 'Wiadomość została wysłana.']);
