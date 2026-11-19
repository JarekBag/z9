<?php
declare(strict_types=1);

function z9_encode_file_message(string $path, string $name, string $caption): string
{
    $metadata = json_encode(
        ['path' => $path, 'name' => $name, 'caption' => $caption],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if (!is_string($metadata)) {
        throw new RuntimeException('Nie udało się przygotować informacji o załączniku.');
    }

    return '__Z9_FILE_V1__:' . $metadata;
}

function z9_parse_file_message(string $message, ?string $expectedOwner = null): ?array
{
    $prefix = '__Z9_FILE_V1__:';
    if (strncmp($message, $prefix, strlen($prefix)) !== 0) {
        return null;
    }

    $metadata = substr($message, strlen($prefix));
    $file = json_decode($metadata, true);
    if (!is_array($file) || !isset($file['path'], $file['name'], $file['caption'])
        || !is_string($file['path']) || !is_string($file['name']) || !is_string($file['caption'])) {
        return null;
    }
    $pathParts = explode('/', $file['path']);
    if ($expectedOwner !== null && (!isset($pathParts[0]) || $pathParts[0] !== $expectedOwner)) {
        return null;
    }

    return $file;
}

function z9_attachment_kind(string $filename): string
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        return 'image';
    }
    if ($extension === 'mp4') {
        return 'video';
    }
    if ($extension === 'mp3') {
        return 'audio';
    }
    if ($extension === 'pdf') {
        return 'pdf';
    }

    return 'file';
}

function z9_resolve_attachment_path(array $file, ?string $expectedOwner = null): ?string
{
    $parts = explode('/', $file['path'] ?? '');
    if (count($parts) !== 2 || !z9_is_valid_username($parts[0])
        || preg_match('/\A[a-f0-9]{32}\.upload\z/', $parts[1]) !== 1
        || ($expectedOwner !== null && $parts[0] !== $expectedOwner)) {
        return null;
    }

    $homePath = __DIR__ . DIRECTORY_SEPARATOR . $parts[0];
    if (is_link($homePath)) {
        return null;
    }

    $root = realpath(__DIR__);
    $home = realpath($homePath);
    if ($root === false || $home === false || dirname($home) !== $root) {
        return null;
    }

    $path = realpath($home . DIRECTORY_SEPARATOR . $parts[1]);
    if ($path === false || dirname($path) !== $home || !is_file($path)) {
        return null;
    }

    return $path;
}

function z9_json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) {
        http_response_code(500);
        exit('{"error":"Nie udało się przygotować odpowiedzi."}');
    }

    echo $json;
    exit;
}
