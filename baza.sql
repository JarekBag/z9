-- Struktura bazy danych dla zadania 9 (Komunikator)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. Tabela użytkowników
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domyślne konto administratora (admin / admin)
INSERT IGNORE INTO `users` (`username`, `password`) VALUES ('admin', 'admin');

-- 2. Tabela wiadomości komunikatora
CREATE TABLE IF NOT EXISTS `messages` (
  `idk` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `message` text NOT NULL,
  `user` varchar(100) NOT NULL,
  `recipient` varchar(100) NOT NULL,
  PRIMARY KEY (`idk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela logowania wizyt (goście portalu)
CREATE TABLE IF NOT EXISTS `goscieportalu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(45) DEFAULT NULL,
  `datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `user` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
