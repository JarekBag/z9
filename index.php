<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: komunikator.php');
    exit;
}

$error = $_GET['error'] ?? '';
$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
$messages = [
    'login' => 'Nieprawidłowa nazwa użytkownika lub hasło.',
    'csrf' => 'Sesja formularza wygasła. Spróbuj ponownie.',
    'folder' => 'Nie udało się przygotować katalogu użytkownika.',
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zadanie 9 — Komunikator</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="twoj_css.css">
</head>
<body class="auth-page">
<?php include __DIR__ . '/header.php'; ?>
<main class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <section class="card auth-card border-primary shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center text-primary fw-bold mb-2">Zadanie 9 — Komunikator</h1>
            <h2 class="h5 text-center mb-4">Zaloguj się</h2>
            <?php if ($registered): ?>
                <div class="alert alert-success" role="status">Konto zostało utworzone. Możesz się zalogować.</div>
            <?php endif; ?>
            <?php if (is_string($error) && isset($messages[$error])): ?>
                <div class="alert alert-danger" role="alert"><?php echo z9_escape($messages[$error]); ?></div>
            <?php endif; ?>
            <form method="post" action="weryfikuj.php">
                <input type="hidden" name="csrf_token" value="<?php echo z9_escape(z9_csrf_token()); ?>">
                <div class="form-floating mb-3">
                    <input class="form-control" type="text" id="username" name="user" placeholder="Nazwa użytkownika" required autocomplete="username">
                    <label for="username">Nazwa użytkownika</label>
                </div>
                <div class="form-floating mb-4">
                    <input class="form-control" type="password" id="password" name="pass" placeholder="Hasło" required autocomplete="current-password">
                    <label for="password">Hasło</label>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Zaloguj</button>
            </form>
            <p class="text-center mt-4 mb-0"><a href="rejestracja.php">Nie masz konta? Zarejestruj się</a></p>
        </div>
    </section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
