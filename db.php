<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connection.php';

try {
    mysqli_query(
        $link,
        "CREATE TABLE IF NOT EXISTS users (
            id INT NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(128) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `avatar` LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY username_unique (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci"
    );
    mysqli_query(
        $link,
        "CREATE TABLE IF NOT EXISTS goscieportalu (
            id INT NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(128) NOT NULL,
            ipaddress VARCHAR(45) NOT NULL,
            browser VARCHAR(255) NOT NULL,
            `datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status ENUM('SUCCESS', 'FAILED') NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci"
    );
    mysqli_query(
        $link,
        "CREATE TABLE IF NOT EXISTS messages (
            idk INT NOT NULL AUTO_INCREMENT,
            `datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
            message TEXT NOT NULL,
            `user` VARCHAR(100) NOT NULL,
            `recipient` VARCHAR(100) NOT NULL,
            PRIMARY KEY (idk),
            KEY messages_sender_id (`user`, idk),
            KEY messages_recipient_id (`recipient`, idk)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci"
    );

    $adminResult = mysqli_query($link, "SELECT id FROM users WHERE `username` = 'admin' LIMIT 1");
    $adminExists = mysqli_num_rows($adminResult) > 0;
    mysqli_free_result($adminResult);
    if (!$adminExists) {
        $adminUsername = 'admin';
        $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
        $adminAvatar = 'default.png';
        $statement = mysqli_prepare(
            $link,
            'INSERT IGNORE INTO users (`username`, `password`, `avatar`) VALUES (?, ?, ?)'
        );
        mysqli_stmt_bind_param($statement, 'sss', $adminUsername, $adminPassword, $adminAvatar);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Z9 database initialization error: ' . $exception->getMessage());
    http_response_code(500);
    exit('Nie udało się przygotować bazy danych dla komunikatora.');
}

try {
    z9_ensure_home_directory('admin');
} catch (RuntimeException $exception) {
    error_log('Z9 admin directory error: ' . $exception->getMessage());
    http_response_code(500);
    exit('Nie udało się przygotować katalogu administratora.');
}
