-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: 04 فبراير 2026 الساعة 23:39
-- إصدار الخادم: 5.7.36
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `university_labs`
--

-- --------------------------------------------------------

--
-- بنية الجدول `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'confirmed',
  `student_count` int(11) DEFAULT '6',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lab_id` (`lab_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `lab_id`, `booking_date`, `start_time`, `end_time`, `status`, `student_count`, `created_at`) VALUES
(1, 1, 2, '2026-02-03', '08:00:00', '08:30:00', 'confirmed', 6, '2026-02-03 21:51:07'),
(2, 2, 1, '2026-02-04', '08:00:00', '08:30:00', 'confirmed', 6, '2026-02-04 21:17:58'),
(3, 2, 1, '2026-02-04', '10:00:00', '10:30:00', 'confirmed', 6, '2026-02-04 22:57:20');

-- --------------------------------------------------------

--
-- بنية الجدول `chatbot_logs`
--

DROP TABLE IF EXISTS `chatbot_logs`;
CREATE TABLE IF NOT EXISTS `chatbot_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_response` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','image') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `class_schedule`
--

DROP TABLE IF EXISTS `class_schedule`;
CREATE TABLE IF NOT EXISTS `class_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `course_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `labs`
--

DROP TABLE IF EXISTS `labs`;
CREATE TABLE IF NOT EXISTS `labs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lab_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lab_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `college` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `building` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `equipment` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','maintenance','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_code` (`lab_code`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `labs`
--

INSERT INTO `labs` (`id`, `lab_code`, `lab_name`, `college`, `building`, `floor`, `capacity`, `equipment`, `status`) VALUES
(1, 'C1-1', 'معمل الحاسوب 1', 'كلية الهندسة وعلوم الحاسب', 'C', 1, 30, 'حواسيب - بروجكتر - طابعة - شبكة إنترنت', 'active'),
(2, 'C3-1', 'معمل الذكاء الاصطناعي', 'كلية الهندسة وعلوم الحاسب', 'C', 3, 25, 'حواسيب قوية - كروت شاشة متطورة - خوادم', 'active'),
(3, 'C3-2', 'معمل الأمن السيبراني', 'كلية الهندسة وعلوم الحاسب', 'C', 3, 20, 'حواسيب محصنة - برمجيات أمنية - شبكات معزولة', 'active'),
(4, 'C4-1', 'معمل المشاريع البحثية', 'كلية الهندسة وعلوم الحاسب', 'C', 4, 15, 'حواسيب محمولة - أجهزة عرض - مساحات عمل', 'active');

-- --------------------------------------------------------

--
-- بنية الجدول `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('reported','in_progress','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'reported',
  `report_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lab_id` (`lab_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `ticket_type` enum('current','past') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ticket_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('attended','absent','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `check_in_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `user_id` (`user_id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `booking_id`, `ticket_type`, `ticket_code`, `status`, `check_in_time`, `created_at`) VALUES
(2, 2, 2, 'current', 'TICKET-000002', 'pending', NULL, '2026-02-04 21:17:58'),
(3, 2, 3, 'current', 'TICKET-000003', 'pending', NULL, '2026-02-04 22:57:20');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `college` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `fullname`, `student_id`, `email`, `phone`, `college`, `specialization`, `password`, `avatar`, `created_at`, `last_login`) VALUES
(2, 'taif', '445052186', 'taeef.alotaibi@icloud.com', '0502757392', 'كلية الهندسة وعلوم الحاسب', 'علوم الحاسب', '$2y$10$SApGV7vvIqtY/X1CcZGHmuu1xB6N/d06j9zvmfZJFQDEXqHCgAG2K', 'default-avatar.png', '2026-02-04 19:35:27', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
