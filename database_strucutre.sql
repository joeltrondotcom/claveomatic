SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `c0claveomatic` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `c0claveomatic`;

CREATE TABLE `autoclaves` (
	  `id` int(11) NOT NULL,
	  `owner` int(11) NOT NULL,
	  `enabled` tinyint(1) NOT NULL DEFAULT 1,
	  `model` varchar(32) DEFAULT NULL,
	  `nickname` varchar(32) DEFAULT NULL,
	  `cycles` varchar(2048) DEFAULT NULL,
	  `ip_address` varchar(128) DEFAULT NULL,
	  `order` int(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `logs` (
	  `id` int(11) NOT NULL,
	  `enabled` tinyint(1) NOT NULL DEFAULT 1,
	  `owner` int(11) NOT NULL,
	  `autoclave` varchar(32) DEFAULT NULL,
	  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
	  `cycle_no` int(11) DEFAULT NULL,
	  `cycle_type` varchar(16) DEFAULT NULL,
	  `cycle_temp` smallint(16) DEFAULT NULL,
	  `cycle_duration` varchar(16) DEFAULT NULL,
	  `status` varchar(16) DEFAULT NULL,
	  `operator` varchar(32) DEFAULT NULL,
	  `desc` varchar(128) DEFAULT NULL,
	  `photo_id` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `settings` (
	  `int` mediumint(9) NOT NULL,
	  `owner` mediumint(9) NOT NULL,
	  `key` varchar(64) NOT NULL,
	  `value` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
	  `id` mediumint(9) NOT NULL,
	  `username` char(99) NOT NULL,
	  `md5password` varchar(40) NOT NULL,
	  `language` varchar(32) NOT NULL DEFAULT 'english',
	  `operators` varchar(256) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


ALTER TABLE `autoclaves`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `settings`
  ADD PRIMARY KEY (`int`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id` (`id`);


ALTER TABLE `autoclaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `settings`
  MODIFY `int` mediumint(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;
COMMIT;

