<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$currentUser = (string)($_SESSION['username'] ?? '');
$avatar = (string)($_SESSION['temp_avatar_data'] ?? 'default.png');
if (strpos($avatar, 'data:image/') !== 0) {
    $avatar = 'default.png';
}
?>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $isLoggedIn ? 'komunikator.php' : 'index.php'; ?>">Zadanie 9 — Komunikator</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#z9Navigation" aria-controls="z9Navigation" aria-expanded="false" aria-label="Otwórz menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="z9Navigation">
                <ul class="navbar-nav me-auto">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item"><a class="nav-link" href="komunikator.php">Wiadomości</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="index.php">Logowanie</a></li>
                        <li class="nav-item"><a class="nav-link" href="rejestracja.php">Rejestracja</a></li>
                    <?php endif; ?>
                </ul>
                <?php if ($isLoggedIn): ?>
                    <div class="d-flex align-items-center gap-2 text-white">
                        <img src="<?php echo z9_escape($avatar); ?>" class="rounded-circle border border-light user-avatar" alt="Awatar użytkownika">
                        <span class="small">Zalogowany: <strong><?php echo z9_escape($currentUser); ?></strong></span>
                        <a class="btn btn-outline-light btn-sm" href="logout.php">Wyloguj</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
