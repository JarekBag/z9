<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
z9_require_login();
require_once __DIR__ . '/db_connection.php';

$users = [];
$result = mysqli_query($link, 'SELECT `username` FROM users ORDER BY `username`');
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = (string)$row['username'];
}
mysqli_free_result($result);
mysqli_close($link);

$isAdmin = (string)($_SESSION['username'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zadanie 9 — Komunikator</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="twoj_css.css">
    <script src="komunikator.js" defer></script>
</head>
<body class="chat-page">
<?php include __DIR__ . '/header.php'; ?>
<main class="container py-4">
    <section class="card chat-card shadow-sm mx-auto mb-4">
        <div class="card-header bg-white">
            <h1 class="h4 mb-1">Komunikator</h1>
            <p class="text-muted small mb-0">Wiadomości aktualizują się automatycznie, bez przeładowania strony.</p>
        </div>
        <div class="card-body">
            <form id="messageForm" action="send_message.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo z9_escape(z9_csrf_token()); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="recipient">Adresat:</label>
                        <select class="form-select" id="recipient" name="recipient" required>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo z9_escape($user); ?>"><?php echo z9_escape($user); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" for="message">Wiadomość:</label>
                        <textarea class="form-control" id="message" name="message" rows="3" placeholder="Napisz wiadomość lub dodaj załącznik..."></textarea>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" for="fileToUpload">Plik (opcjonalnie):</label>
                        <input class="form-control" type="file" id="fileToUpload" name="fileToUpload">
                        <div class="form-text">Aplikacja nie ogranicza typu ani rozmiaru pliku. Mogą obowiązywać limity ustawione przez hosting PHP.</div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="sendButton" type="submit">Wyślij</button>
                    </div>
                </div>
                <div id="sendStatus" class="status-line small text-muted mt-2" role="status" aria-live="polite"></div>
            </form>
        </div>
    </section>

    <section class="card chat-card shadow-sm mx-auto">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Wiadomości</h2>
            <span class="badge text-bg-<?php echo $isAdmin ? 'warning' : 'primary'; ?>">
                <?php echo $isAdmin ? 'Administrator — wszystkie rozmowy' : 'Twoje rozmowy'; ?>
            </span>
        </div>
        <div id="messageFeed" class="message-feed" role="log" aria-live="polite" aria-relevant="additions removals" data-is-admin="<?php echo $isAdmin ? '1' : '0'; ?>">
            <p class="text-muted mb-0" id="feedPlaceholder">Ładowanie wiadomości...</p>
        </div>
        <div class="card-footer bg-white small text-muted">
            Odświeżanie co 1,8 sekundy. Dymki zachowują swoje elementy multimedialne podczas aktualizacji.
            <span id="loadStatus" class="d-block status-line" role="status"></span>
        </div>
    </section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
