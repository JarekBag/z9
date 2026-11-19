<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/message_helpers.php';
z9_require_login(true);
if ((string)$_SESSION['username'] !== 'admin') {
    z9_json_response(403, ['error' => 'Tylko administrator może usuwać wiadomości.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    z9_json_response(405, ['error' => 'Ta strona obsługuje wyłącznie żądania POST.']);
}
if (!z9_valid_csrf_token($_POST['csrf_token'] ?? null)) {
    z9_json_response(403, ['error' => 'Sesja formularza wygasła. Odśwież stronę.']);
}

$messageId = $_POST['idk'] ?? '';
if (!is_string($messageId) || !ctype_digit($messageId)) {
    z9_json_response(400, ['error' => 'Nieprawidłowy identyfikator wiadomości.']);
}

require_once __DIR__ . '/db_connection.php';
$id = (int)$messageId;
$statement = mysqli_prepare($link, 'SELECT message, `user` FROM messages WHERE idk = ? LIMIT 1');
mysqli_stmt_bind_param($statement, 'i', $id);
mysqli_stmt_execute($statement);
$storedMessage = '';
$sender = '';
mysqli_stmt_bind_result($statement, $storedMessage, $sender);
$found = mysqli_stmt_fetch($statement);
mysqli_stmt_close($statement);
if ($found !== true) {
    mysqli_close($link);
    z9_json_response(404, ['error' => 'Wiadomość już nie istnieje.']);
}

$delete = mysqli_prepare($link, 'DELETE FROM messages WHERE idk = ?');
mysqli_stmt_bind_param($delete, 'i', $id);
mysqli_stmt_execute($delete);
mysqli_stmt_close($delete);
mysqli_close($link);

$file = z9_parse_file_message($storedMessage, $sender);
if ($file !== null) {
    $path = z9_resolve_attachment_path($file, $sender);
    if ($path !== null && !unlink($path)) {
        error_log('Z9 attachment cleanup failed: ' . $path);
    }
}

z9_json_response(200, ['message' => 'Wiadomość została usunięta.']);
