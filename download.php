<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
z9_require_login();
require_once __DIR__ . '/message_helpers.php';
require_once __DIR__ . '/db_connection.php';

$messageId = $_GET['idk'] ?? '';
if (!is_string($messageId) || !ctype_digit($messageId)) {
    http_response_code(404);
    exit('Nie znaleziono pliku.');
}

$statement = mysqli_prepare(
    $link,
    'SELECT message, `user`, `recipient` FROM messages WHERE idk = ? LIMIT 1'
);
$id = (int)$messageId;
mysqli_stmt_bind_param($statement, 'i', $id);
mysqli_stmt_execute($statement);
$storedMessage = '';
$sender = '';
$recipient = '';
mysqli_stmt_bind_result($statement, $storedMessage, $sender, $recipient);
$found = mysqli_stmt_fetch($statement);
mysqli_stmt_close($statement);
mysqli_close($link);

$currentUser = (string)$_SESSION['username'];
$isAdmin = $currentUser === 'admin';
if ($found !== true || (!$isAdmin && $sender !== $currentUser && $recipient !== $currentUser)) {
    http_response_code(404);
    exit('Nie znaleziono pliku.');
}

$file = z9_parse_file_message($storedMessage, $sender);
$path = $file === null ? null : z9_resolve_attachment_path($file, $sender);
if ($file === null || $path === null) {
    http_response_code(404);
    exit('Nie znaleziono pliku.');
}

$fileSize = filesize($path);
$handle = fopen($path, 'rb');
if ($fileSize === false || $handle === false) {
    http_response_code(404);
    exit('Nie znaleziono pliku.');
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'mp3' => 'audio/mpeg',
    'mp4' => 'video/mp4',
    'pdf' => 'application/pdf',
];
$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
$isInline = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'mp3', 'mp4', 'pdf'], true);
$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
if (!is_string($safeName) || $safeName === '') {
    $safeName = 'zalacznik';
}

$start = 0;
$end = $fileSize > 0 ? $fileSize - 1 : 0;
$statusCode = 200;
$rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
if ($rangeHeader !== '') {
    if (preg_match('/\Abytes=(\d*)-(\d*)\z/', trim($rangeHeader), $matches) !== 1 || $fileSize === 0) {
        fclose($handle);
        http_response_code(416);
        header('Content-Range: bytes */' . $fileSize);
        exit;
    }

    if ($matches[1] === '') {
        $suffixLength = (int)$matches[2];
        if ($suffixLength < 1) {
            fclose($handle);
            http_response_code(416);
            header('Content-Range: bytes */' . $fileSize);
            exit;
        }
        $start = max(0, $fileSize - $suffixLength);
    } else {
        $start = (int)$matches[1];
        if ($matches[2] !== '') {
            $end = (int)$matches[2];
        }
    }

    if ($start > $end || $start >= $fileSize) {
        fclose($handle);
        http_response_code(416);
        header('Content-Range: bytes */' . $fileSize);
        exit;
    }
    $end = min($end, $fileSize - 1);
    $statusCode = 206;
}

session_write_close();
http_response_code($statusCode);
header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Accept-Ranges: bytes');
header(
    'Content-Disposition: ' . ($isInline ? 'inline' : 'attachment')
    . '; filename="' . $safeName . '"; filename*=UTF-8\'\'' . rawurlencode($file['name'])
);
header('Content-Length: ' . ($fileSize > 0 ? $end - $start + 1 : 0));
if ($statusCode === 206) {
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD' && $fileSize > 0) {
    fseek($handle, $start);
    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(65536, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
    }
}
fclose($handle);
