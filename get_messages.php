<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
z9_require_login(true);
require_once __DIR__ . '/message_helpers.php';
require_once __DIR__ . '/db_connection.php';

$currentUser = (string)$_SESSION['username'];
$isAdmin = $currentUser === 'admin';
if ($isAdmin) {
    $result = mysqli_query($link, 'SELECT idk, `datetime`, message, `user`, `recipient` FROM messages ORDER BY idk DESC');
} else {
    $statement = mysqli_prepare(
        $link,
        'SELECT idk, `datetime`, message, `user`, `recipient` FROM messages WHERE `user` = ? OR `recipient` = ? ORDER BY idk DESC'
    );
    mysqli_stmt_bind_param($statement, 'ss', $currentUser, $currentUser);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $id, $datetime, $message, $sender, $recipient);
}

$messages = [];
if ($isAdmin) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int)$row['idk'];
        $datetime = (string)$row['datetime'];
        $message = (string)$row['message'];
        $sender = (string)$row['user'];
        $recipient = (string)$row['recipient'];

        $entry = [
            'idk' => $id,
            'datetime' => $datetime,
            'user' => $sender,
            'recipient' => $recipient,
        ];
        $file = z9_parse_file_message($message, $sender);
        if ($file === null) {
            $entry['type'] = 'text';
            $entry['text'] = $message;
        } else {
            $entry['type'] = 'file';
            $entry['file'] = [
                'name' => $file['name'],
                'caption' => $file['caption'],
                'kind' => z9_attachment_kind($file['name']),
                'url' => 'download.php?idk=' . $id,
            ];
        }
        $messages[] = $entry;
    }
    mysqli_free_result($result);
} else {
    while (mysqli_stmt_fetch($statement)) {
        $id = (int)$id;
        $datetime = (string)$datetime;
        $message = (string)$message;
        $sender = (string)$sender;
        $recipient = (string)$recipient;

        $entry = [
            'idk' => $id,
            'datetime' => $datetime,
            'user' => $sender,
            'recipient' => $recipient,
        ];
        $file = z9_parse_file_message($message, $sender);
        if ($file === null) {
            $entry['type'] = 'text';
            $entry['text'] = $message;
        } else {
            $entry['type'] = 'file';
            $entry['file'] = [
                'name' => $file['name'],
                'caption' => $file['caption'],
                'kind' => z9_attachment_kind($file['name']),
                'url' => 'download.php?idk=' . $id,
            ];
        }
        $messages[] = $entry;
    }
    mysqli_stmt_close($statement);
}

mysqli_close($link);
z9_json_response(200, ['messages' => $messages, 'isAdmin' => $isAdmin]);
