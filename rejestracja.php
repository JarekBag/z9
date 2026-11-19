<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: komunikator.php');
    exit;
}

$errors = [
    'empty' => 'Uzupełnij wszystkie wymagane pola.',
    'username' => 'Login może zawierać litery, cyfry, kropki, myślniki i podkreślenia.',
    'password' => 'Podane hasła nie są identyczne.',
    'duplicate' => 'Ta nazwa użytkownika jest już zajęta.',
    'avatar' => 'Awatar musi być obrazem PNG, JPG, GIF lub WebP o rozmiarze do 2 MB.',
    'database' => 'Nie udało się utworzyć konta. Sprawdź połączenie z bazą.',
    'csrf' => 'Sesja formularza wygasła. Spróbuj ponownie.',
    'folder' => 'Nie udało się przygotować katalogu użytkownika.',
];
$error = $_GET['error'] ?? '';
$errorMessage = is_string($error) && isset($errors[$error]) ? $errors[$error] : '';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zadanie 9 — Komunikator · Rejestracja</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="twoj_css.css">
</head>
<body class="auth-page">
<?php include __DIR__ . '/header.php'; ?>
<main class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <section class="card auth-card border-primary shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-center text-primary fw-bold mb-4">Rejestracja</h1>
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert"><?php echo z9_escape($errorMessage); ?></div>
            <?php endif; ?>
            <form method="post" action="rejestruj.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo z9_escape(z9_csrf_token()); ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="user">Nazwa użytkownika:</label>
                    <input class="form-control" type="text" id="user" name="user" maxlength="100" required autocomplete="username" pattern="[A-Za-z0-9][A-Za-z0-9._-]*">
                    <div class="form-text">Dozwolone są litery bez polskich znaków, cyfry, kropki, myślniki i podkreślenia.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="pass1">Hasło:</label>
                    <input class="form-control" type="password" id="pass1" name="pass1" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="pass2">Powtórz hasło:</label>
                    <input class="form-control" type="password" id="pass2" name="pass2" required autocomplete="new-password">
                </div>
                <div class="border rounded bg-light p-3 mb-4">
                    <label class="form-label fw-semibold" for="avatar">Awatar profilu (opcjonalnie):</label>
                    <div class="text-center mb-3">
                        <img id="avatarPreview" src="default.png" alt="Domyślny awatar" class="avatar-preview">
                    </div>
                    <input class="form-control" type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp">
                    <div class="form-text">Bez wybranego pliku zostanie użyty domyślny awatar.</div>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Zarejestruj się</button>
            </form>
            <p class="text-center mt-4 mb-0"><a href="index.php">Masz już konto? Zaloguj się</a></p>
        </div>
    </section>
</main>
<script>
    document.getElementById('avatar').addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (file) {
            document.getElementById('avatarPreview').src = URL.createObjectURL(file);
        }
    });
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
