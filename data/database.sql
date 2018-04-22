-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Czas generowania: 07 Wrz 2017, 22:36
-- Wersja serwera: 5.7.19-0ubuntu0.16.04.1
-- Wersja PHP: 5.6.31-4+ubuntu16.04.1+deb.sury.org+4

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `silex`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_adverts`
--

CREATE TABLE `si_adverts` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `city` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `type` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `modified_at` datetime NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_advert_photos`
--

CREATE TABLE `si_advert_photos` (
  `id` int(10) UNSIGNED NOT NULL,
  `filepath` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `advert_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_categories`
--

CREATE TABLE `si_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_conversations`
--

CREATE TABLE `si_conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic` varchar(255) CHARACTER SET utf8 NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `advert_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_messages`
--

CREATE TABLE `si_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `created_at` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_profiles`
--

CREATE TABLE `si_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_roles`
--

CREATE TABLE `si_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

INSERT INTO `si_roles` (`id`, `name`) VALUES
(1, 'ROLE_ADMIN'),
(2, 'ROLE_USER');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `si_users`
--

CREATE TABLE `si_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `login` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `si_users` (`id`, `login`, `password`, `role_id`) VALUES ('1', 'admin', '$2y$13$9V5.L5JdIdlPGC4bBVnnjedOiLncyM2nJACentpXYl9IxLMZTnHLi', 1);
INSERT INTO `si_profiles` (`id`, `name`, `surname`, `email`, `user_id`) VALUES (1, 'admin', 'admin', 'admin@admin.admin', 1);

--
-- Indeksy dla zrzutów tabel
--

--
-- Indexes for table `si_adverts`
--
ALTER TABLE `si_adverts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `si_advert_photos`
--
ALTER TABLE `si_advert_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `advert_id` (`advert_id`);

--
-- Indexes for table `si_categories`
--
ALTER TABLE `si_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `si_conversations`
--
ALTER TABLE `si_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IX_conversations_1` (`owner_id`),
  ADD KEY `IX_conversations_2` (`user_id`),
  ADD KEY `IX_conversations_3` (`advert_id`);

--
-- Indexes for table `si_messages`
--
ALTER TABLE `si_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IX_messages_1` (`conversation_id`),
  ADD KEY `IX_messages_2` (`user_id`);

--
-- Indexes for table `si_profiles`
--
ALTER TABLE `si_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UQ_profiles_1` (`email`),
  ADD KEY `IX_profiles_1` (`user_id`);

--
-- Indexes for table `si_roles`
--
ALTER TABLE `si_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `si_users`
--
ALTER TABLE `si_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UQ_users_1` (`login`),
  ADD KEY `IX_users_1` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT dla tabeli `si_adverts`
--
ALTER TABLE `si_adverts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT dla tabeli `si_advert_photos`
--
ALTER TABLE `si_advert_photos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT dla tabeli `si_categories`
--
ALTER TABLE `si_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT dla tabeli `si_conversations`
--
ALTER TABLE `si_conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT dla tabeli `si_messages`
--
ALTER TABLE `si_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT dla tabeli `si_profiles`
--
ALTER TABLE `si_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT dla tabeli `si_roles`
--
ALTER TABLE `si_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT dla tabeli `si_users`
--
ALTER TABLE `si_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `si_adverts`
--
ALTER TABLE `si_adverts`
  ADD CONSTRAINT `si_adverts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `si_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `si_adverts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `si_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Ograniczenia dla tabeli `si_advert_photos`
--
ALTER TABLE `si_advert_photos`
  ADD CONSTRAINT `si_advert_photos_ibfk_1` FOREIGN KEY (`advert_id`) REFERENCES `si_adverts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Ograniczenia dla tabeli `si_conversations`
--
ALTER TABLE `si_conversations`
  ADD CONSTRAINT `FK_conversations_1` FOREIGN KEY (`owner_id`) REFERENCES `si_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FK_conversations_2` FOREIGN KEY (`user_id`) REFERENCES `si_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FK_conversations_3` FOREIGN KEY (`advert_id`) REFERENCES `si_adverts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ograniczenia dla tabeli `si_messages`
--
ALTER TABLE `si_messages`
  ADD CONSTRAINT `FK_messages_1` FOREIGN KEY (`conversation_id`) REFERENCES `si_conversations` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FK_messages_2` FOREIGN KEY (`user_id`) REFERENCES `si_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ograniczenia dla tabeli `si_profiles`
--
ALTER TABLE `si_profiles`
  ADD CONSTRAINT `FK_profiles_1` FOREIGN KEY (`user_id`) REFERENCES `si_users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ograniczenia dla tabeli `si_users`
--
ALTER TABLE `si_users`
  ADD CONSTRAINT `FK_users_1` FOREIGN KEY (`role_id`) REFERENCES `si_roles` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
