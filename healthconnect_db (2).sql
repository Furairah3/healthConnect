-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 12 déc. 2025 à 18:08
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `healthconnect_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `hc_activity_logs`
--

CREATE TABLE `hc_activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `log_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_action` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_activity_logs`
--

INSERT INTO `hc_activity_logs` (`log_id`, `user_id`, `activity_type`, `activity_description`, `ip_address`, `user_agent`, `log_date`, `admin_action`) VALUES
(10, 4, 'request_create', 'Created health request: me', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:30:34', 0),
(11, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 15:38:32', 0),
(12, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 15:41:25', 0),
(13, 7, 'registration', 'New user registered as doctor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 15:43:28', 0),
(14, 4, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 16:15:25', 0),
(15, 4, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 16:20:05', 0),
(16, 7, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:18:34', 0),
(17, 2, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:29:45', 0),
(18, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:30:03', 0),
(19, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:30:45', 0),
(20, 2, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:31:48', 0),
(21, 2, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:37:29', 0),
(22, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:37:43', 0),
(23, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:50:28', 0),
(24, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:57:31', 0),
(25, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:57:49', 0),
(26, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:58:09', 0),
(27, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 17:59:40', 0),
(28, 8, 'registration', 'New user registered as patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 13:06:37', 0),
(29, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 13:07:18', 0),
(30, 8, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 13:44:01', 0),
(31, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 13:58:13', 0),
(32, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:03:40', 0),
(33, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:04:01', 0),
(34, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:04:05', 0),
(35, 9, 'registration', 'New user registered as volunteer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:10:04', 0),
(36, 10, 'registration', 'New user registered as volunteer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:14:52', 0),
(37, 11, 'registration', 'New user registered as patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:18:03', 0),
(38, 12, 'registration', 'New user registered as patient', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:24:10', 0),
(39, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:24:33', 0),
(40, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:24:51', 0),
(41, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:25:11', 0),
(42, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:25:20', 0),
(43, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:32:24', 0),
(44, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:32:39', 0),
(45, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:38:40', 0),
(46, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:38:57', 0),
(47, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:46:16', 0),
(48, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:46:31', 0),
(49, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:49:13', 0),
(50, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:49:27', 0),
(51, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:51:00', 0),
(52, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 14:52:01', 0),
(53, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 15:17:27', 0),
(54, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 15:17:39', 0),
(55, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 15:55:40', 0),
(56, 7, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 16:08:06', 0),
(57, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 16:08:20', 0),
(58, 8, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 16:08:41', 0),
(59, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 16:08:53', 0),
(60, 6, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 16:26:27', 0),
(61, 13, 'admin_login', 'System administrator logged in', '::1', 'Mozilla/5.0 Admin Browser', '2025-12-09 08:00:00', 1),
(62, 13, 'doctor_approval', 'Approved Dr. Kojo Ampofo - Credentials verified', '::1', 'Mozilla/5.0 Admin Browser', '2025-11-10 10:00:00', 1),
(63, 13, 'doctor_approval', 'Approved Dr. Akua Nkrumah - Pediatrics specialist', '::1', 'Mozilla/5.0 Admin Browser', '2025-11-13 11:30:00', 1),
(64, 13, 'resource_upload', 'Uploaded training resource: Basic First Aid Manual', '::1', 'Mozilla/5.0 Admin Browser', '2025-11-15 10:00:00', 1),
(65, 64, 'response_submitted', 'Responded to medical request about Cough and Cold', '192.168.1.100', 'Mozilla/5.0 Volunteer App', '2025-12-05 14:30:00', 0),
(66, 63, 'response_submitted', 'Responded to medical request about Joint Pain', '192.168.1.101', 'Mozilla/5.0 Volunteer App', '2025-12-04 16:45:00', 0),
(67, 68, 'response_submitted', 'Responded to medical request about Anxiety and Sleep Issues', '192.168.1.102', 'Mozilla/5.0 Volunteer App', '2025-12-06 15:20:00', 0),
(68, 65, 'response_submitted', 'Responded to medical request about Minor Burn', '192.168.1.103', 'Mozilla/5.0 Volunteer App', '2025-12-03 13:45:00', 0),
(69, 67, 'response_submitted', 'Responded to medical request about Diabetes Management', '192.168.1.104', 'Mozilla/5.0 Volunteer App', '2025-12-02 17:30:00', 0),
(70, 66, 'response_submitted', 'Responded to medical request about Pregnancy Concerns', '192.168.1.105', 'Mozilla/5.0 Volunteer App', '2025-12-01 12:20:00', 0),
(71, 48, 'request_created', 'Created medical request: Severe Chest Pain', '192.168.1.106', 'Mozilla/5.0 Patient App', '2025-12-08 08:15:00', 0),
(72, 49, 'request_created', 'Created medical request: High Fever in Child', '192.168.1.107', 'Mozilla/5.0 Patient App', '2025-12-08 10:30:00', 0),
(73, 13, 'request_assigned', 'Assigned medical request to volunteer', '::1', 'Mozilla/5.0 Admin Browser', '2025-12-08 11:00:00', 1),
(74, NULL, 'admin_login', 'System administrator logged in', '::1', 'Mozilla/5.0 Admin Browser', '2025-12-09 08:00:00', 1),
(75, NULL, 'doctor_approval', 'Approved Dr. Kojo Ampofo - Credentials verified', '::1', 'Mozilla/5.0 Admin Browser', '2025-11-10 10:00:00', 1),
(76, NULL, 'doctor_approval', 'Approved Dr. Akua Nkrumah - Pediatrics specialist', '::1', 'Mozilla/5.0 Admin Browser', '2025-11-13 11:30:00', 1),
(77, NULL, 'resource_upload', 'Uploaded training resource: Basic First Aid Manual', '::1', 'Mozilla/5.0 Admin Browser', '2025-11-15 10:00:00', 1),
(78, 30, 'response_submitted', 'Responded to medical request about Cough and Cold', '192.168.1.100', 'Mozilla/5.0 Volunteer App', '2025-12-05 14:30:00', 0),
(79, 29, 'response_submitted', 'Responded to medical request about Joint Pain', '192.168.1.101', 'Mozilla/5.0 Volunteer App', '2025-12-04 16:45:00', 0),
(80, 14, 'request_created', 'Created medical request: Severe Chest Pain', '192.168.1.106', 'Mozilla/5.0 Patient App', '2025-12-08 08:15:00', 0),
(81, 15, 'request_created', 'Created medical request: Persistent Headache', '192.168.1.107', 'Mozilla/5.0 Patient App', '2025-12-07 09:20:00', 0),
(82, 13, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 17:05:17', 0),
(83, 13, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 19:49:37', 0),
(84, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 20:00:31', 0),
(85, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 20:01:02', 0),
(86, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 21:09:38', 0),
(87, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 21:18:08', 0),
(88, 8, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-09 21:41:23', 0),
(89, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-10 12:16:30', 0),
(90, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-12 11:08:53', 0),
(91, 6, 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-12 14:55:53', 0);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `hc_doctor_tips_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `hc_doctor_tips_stats` (
`doctor_user_id` int(11)
,`doctor_name` varchar(100)
,`total_tips` bigint(21)
,`total_likes` decimal(32,0)
,`total_views` decimal(32,0)
,`avg_reading_time` decimal(14,4)
,`latest_tip` timestamp
);

-- --------------------------------------------------------

--
-- Structure de la table `hc_doctor_verifications`
--

CREATE TABLE `hc_doctor_verifications` (
  `verification_id` int(11) NOT NULL,
  `doctor_user_id` int(11) NOT NULL,
  `document_filename` varchar(255) NOT NULL,
  `verification_status` enum('pending_review','approved','rejected') DEFAULT 'pending_review',
  `reviewed_by_admin_id` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_doctor_verifications`
--

INSERT INTO `hc_doctor_verifications` (`verification_id`, `doctor_user_id`, `document_filename`, `verification_status`, `reviewed_by_admin_id`, `review_date`, `admin_notes`, `submission_date`) VALUES
(14, 71, 'dr_kojo_ampofo_certificate.pdf', 'approved', 13, '2025-11-10 10:00:00', 'Credentials verified. Approved for general practice.', '2025-11-05 09:00:00'),
(15, 72, 'dr_akua_nkrumah_certificate.pdf', 'approved', 13, '2025-11-13 11:30:00', 'Pediatrics board certified. Excellent credentials.', '2025-11-08 11:30:00'),
(16, 73, 'dr_kwame_osei_certificate.pdf', 'pending_review', NULL, NULL, NULL, '2025-11-12 14:15:00'),
(17, 74, 'dr_esi_mensah_certificate.pdf', 'approved', 13, '2025-11-18 16:00:00', 'Gynecology specialist with 15 years experience.', '2025-11-15 16:45:00'),
(18, 75, 'dr_yaw_boateng_certificate.pdf', 'pending_review', NULL, NULL, NULL, '2025-11-20 10:20:00'),
(19, 76, 'dr_abena_asare_certificate.pdf', 'approved', 13, '2025-11-28 14:30:00', 'Psychiatry board certified. Approved for mental health consultations.', '2025-11-25 13:30:00'),
(20, 77, 'dr_kofi_agyeman_certificate.pdf', 'approved', 13, '2025-12-01 15:00:00', 'Cardiology specialist. Documents verified.', '2025-11-28 15:40:00'),
(21, 78, 'dr_ama_bonsu_certificate.pdf', 'pending_review', NULL, NULL, NULL, '2025-12-02 09:15:00'),
(22, 79, 'dr_nana_kwasi_certificate.pdf', 'approved', 13, '2025-12-08 11:00:00', 'ENT specialist. Credentials verified.', '2025-12-05 11:25:00'),
(23, 80, 'dr_miriam_tetteh_certificate.pdf', 'rejected', 13, '2025-12-10 10:30:00', 'Certificate appears altered. Requires resubmission with original documents.', '2025-12-08 14:50:00'),
(24, 37, 'dr_kojo_ampofo_certificate.pdf', 'approved', 13, '2025-11-10 10:00:00', 'Credentials verified. Approved for general practice.', '2025-11-05 09:00:00'),
(25, 38, 'dr_akua_nkrumah_certificate.pdf', 'approved', 13, '2025-11-13 11:30:00', 'Pediatrics board certified. Excellent credentials.', '2025-11-08 11:30:00'),
(26, 39, 'dr_kwame_osei_certificate.pdf', 'pending_review', NULL, NULL, NULL, '2025-11-12 14:15:00'),
(27, 40, 'dr_esi_mensah_certificate.pdf', 'approved', 13, '2025-11-18 16:00:00', 'Gynecology specialist with 15 years experience.', '2025-11-15 16:45:00');

-- --------------------------------------------------------

--
-- Structure de la table `hc_feedback`
--

CREATE TABLE `hc_feedback` (
  `feedback_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `feedback_rating` int(11) DEFAULT 5 CHECK (`feedback_rating` >= 1 and `feedback_rating` <= 5),
  `feedback_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hc_forum_comments`
--

CREATE TABLE `hc_forum_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hc_forum_posts`
--

CREATE TABLE `hc_forum_posts` (
  `post_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('volunteer','general','healthcare','emergency','discussion') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hc_health_tips`
--

CREATE TABLE `hc_health_tips` (
  `tip_id` int(11) NOT NULL,
  `doctor_user_id` int(11) NOT NULL,
  `tip_title` varchar(200) NOT NULL,
  `tip_content` text NOT NULL,
  `total_likes` int(11) DEFAULT 0,
  `tip_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_published` tinyint(1) DEFAULT 1,
  `category` varchar(50) DEFAULT 'general',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_by_admin_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `total_views` int(11) DEFAULT 0,
  `difficulty_level` enum('basic','intermediate','advanced') DEFAULT 'basic',
  `reading_time_minutes` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_health_tips`
--

INSERT INTO `hc_health_tips` (`tip_id`, `doctor_user_id`, `tip_title`, `tip_content`, `total_likes`, `tip_date`, `created_at`, `last_modified`, `is_published`, `category`, `is_featured`, `created_by_admin_id`, `is_active`, `total_views`, `difficulty_level`, `reading_time_minutes`) VALUES
(1, 37, 'Managing High Blood Pressure Naturally', 'High blood pressure is a silent killer, but you can manage it naturally:\n\nDietary Changes:\n• Reduce salt intake to less than 5g per day\n• Eat potassium-rich foods: bananas, sweet potatoes, spinach\n• Include garlic in your meals (natural vasodilator)\n\nLifestyle Modifications:\n• 30 minutes of moderate exercise daily\n• Practice stress-reduction techniques\n• Maintain healthy weight (BMI under 25)\n\nMonitoring:\n• Check BP at same time daily\n• Keep a BP log to share with your doctor\n• Limit alcohol to 1 drink per day', 3, '2025-11-20 09:00:00', '2025-11-20 09:00:00', '2025-12-09 17:08:20', 1, 'chronic_disease', 1, NULL, 1, 0, 'basic', 5),
(2, 38, 'Childhood Fever: When to Worry', 'Fever in children can be alarming. Here\'s when to seek medical help:\n\nSeek IMMEDIATE medical attention if:\n• Fever in baby under 3 months\n• Temperature over 40°C (104°F)\n• Child is lethargic or difficult to wake\n• Stiff neck or severe headache\n• Difficulty breathing\n\nHome care for mild fever:\n• Keep child hydrated with water, oral rehydration solution\n• Dress in light clothing\n• Use lukewarm sponge bath (not cold water)\n• Age-appropriate paracetamol as directed\n\nMonitor for:\n• Dehydration signs (dry mouth, no tears, no urine for 8 hours)\n• Rash that doesn\'t fade when pressed\n• Seizures', 2, '2025-11-22 11:30:00', '2025-11-22 11:30:00', '2025-12-09 17:08:20', 1, 'pediatric', 1, NULL, 1, 0, 'basic', 5),
(3, 40, 'Prenatal Nutrition Guide', 'Eating for two? Focus on quality nutrition during pregnancy:\n\nEssential Nutrients:\n• Folic Acid: Leafy greens, lentils, fortified cereals (prevents neural tube defects)\n• Iron: Lean meat, beans, spinach (prevents anemia)\n• Calcium: Milk, yogurt, small fish with bones (for baby\'s bone development)\n• Protein: Chicken, fish, eggs, beans (building blocks for baby)\n\nFoods to Avoid:\n• Raw or undercooked meat/eggs\n• Unpasteurized dairy products\n• High-mercury fish (shark, swordfish)\n• Excessive caffeine (limit to 200mg/day)\n\nSample Daily Meal Plan:\n• Breakfast: Fortified oatmeal with milk and fruit\n• Lunch: Grilled chicken salad with avocado\n• Snack: Yogurt with nuts\n• Dinner: Fish stew with vegetables and whole grain rice', 0, '2025-11-25 14:15:00', '2025-11-25 14:15:00', '2025-12-09 17:08:20', 1, 'maternal_health', 1, NULL, 1, 0, 'basic', 5),
(4, 37, 'First Aid for Common Injuries', 'Be prepared for common household injuries:\n\nCuts and Scrapes:\n1. Wash with clean water and mild soap\n2. Apply antibiotic ointment\n3. Cover with sterile bandage\n4. Change dressing daily\n\nSprains (RICE Method):\n• Rest the injured area\n• Ice for 20 minutes every 2-3 hours\n• Compression with elastic bandage\n• Elevate above heart level\n\nBurns:\n• Cool with running water for 10-20 minutes\n• Do not apply ice directly\n• Cover with sterile, non-stick dressing\n• Seek medical help for burns larger than palm\n\nNosebleeds:\n• Sit upright, lean forward slightly\n• Pinch soft part of nose for 10 minutes\n• Breathe through mouth\n• Avoid blowing nose for several hours', 0, '2025-12-03 13:40:00', '2025-12-03 13:40:00', '2025-12-09 17:08:20', 1, 'first_aid', 0, NULL, 1, 0, 'basic', 5),
(5, 38, 'Childhood Vaccination Schedule', 'Protect your child with timely vaccinations:\n\nBirth:\n• BCG (Tuberculosis)\n• Oral Polio Vaccine (OPV0)\n• Hepatitis B (first dose)\n\n6 Weeks:\n• Pentavalent 1 (DTP, HepB, Hib)\n• OPV1\n• PCV 1 (Pneumococcal)\n• Rotavirus 1\n\n10 Weeks:\n• Pentavalent 2\n• OPV2\n• PCV 2\n• Rotavirus 2\n\n14 Weeks:\n• Pentavalent 3\n• OPV3\n• PCV 3\n• IPV (Inactivated Polio Vaccine)\n\n9 Months:\n• Measles-Rubella (MR1)\n• Yellow Fever\n\n18 Months:\n• MR2\n• Vitamin A supplement', 0, '2025-12-05 15:30:00', '2025-12-05 15:30:00', '2025-12-09 22:00:44', 1, 'pediatric', 0, NULL, 1, 1, 'basic', 5),
(6, 3, 'Managing High Blood Pressure Naturally', '<h4>High blood pressure is often called the \"silent killer\" because it typically has no symptoms. Here are natural ways to manage it:</h4>\r\n    <ul>\r\n        <li><strong>Reduce salt intake:</strong> Aim for less than 5g (1 teaspoon) per day</li>\r\n        <li><strong>Eat potassium-rich foods:</strong> Bananas, sweet potatoes, spinach, and beans</li>\r\n        <li><strong>Include garlic in meals:</strong> Natural vasodilator that helps relax blood vessels</li>\r\n        <li><strong>Regular exercise:</strong> 30 minutes of moderate activity daily</li>\r\n        <li><strong>Manage stress:</strong> Practice deep breathing, meditation, or yoga</li>\r\n        <li><strong>Maintain healthy weight:</strong> Aim for BMI under 25</li>\r\n    </ul>\r\n    <p><strong>Important:</strong> Always continue prescribed medications and consult your doctor before making changes.</p>', 42, '2025-11-20 09:00:00', '2025-11-20 09:00:00', '2025-12-09 17:08:20', 1, 'chronic_disease', 1, NULL, 1, 156, 'intermediate', 8),
(7, 3, 'First Aid for Common Household Injuries', '<h4>Be prepared for common injuries at home:</h4>\r\n    \r\n    <h5>Cuts and Scrapes:</h5>\r\n    <ol>\r\n        <li>Wash with clean water and mild soap</li>\r\n        <li>Apply antibiotic ointment</li>\r\n        <li>Cover with sterile bandage</li>\r\n        <li>Change dressing daily</li>\r\n    </ol>\r\n    \r\n    <h5>Sprains (Remember RICE):</h5>\r\n    <ul>\r\n        <li><strong>R</strong>est the injured area</li>\r\n        <li><strong>I</strong>ce for 20 minutes every 2-3 hours</li>\r\n        <li><strong>C</strong>ompression with elastic bandage</li>\r\n        <li><strong>E</strong>levate above heart level</li>\r\n    </ul>\r\n    \r\n    <h5>Burns:</h5>\r\n    <ul>\r\n        <li>Cool with running water for 10-20 minutes</li>\r\n        <li>Do NOT apply ice directly</li>\r\n        <li>Cover with sterile, non-stick dressing</li>\r\n        <li>Seek medical help for burns larger than your palm</li>\r\n    </ul>', 33, '2025-12-03 13:40:00', '2025-12-03 13:40:00', '2025-12-09 17:08:20', 1, 'first_aid', 0, NULL, 1, 89, 'basic', 6),
(8, 5, 'Childhood Fever: When to Worry', '<h4>Fever in children can be alarming. Here\'s when to seek medical help:</h4>\r\n    \r\n    <h5>Seek <span style=\"color: red;\">IMMEDIATE</span> medical attention if:</h5>\r\n    <ul>\r\n        <li>Fever in baby under 3 months</li>\r\n        <li>Temperature over 40°C (104°F)</li>\r\n        <li>Child is lethargic or difficult to wake</li>\r\n        <li>Stiff neck or severe headache</li>\r\n        <li>Difficulty breathing</li>\r\n        <li>Persistent vomiting</li>\r\n        <li>Seizures</li>\r\n    </ul>\r\n    \r\n    <h5>Home care for mild fever:</h5>\r\n    <ul>\r\n        <li>Keep child hydrated with water or oral rehydration solution</li>\r\n        <li>Dress in light clothing</li>\r\n        <li>Use lukewarm sponge bath (never cold water)</li>\r\n        <li>Give age-appropriate paracetamol as directed</li>\r\n    </ul>\r\n    \r\n    <h5>Monitor for dehydration signs:</h5>\r\n    <ul>\r\n        <li>Dry mouth and tongue</li>\r\n        <li>No tears when crying</li>\r\n        <li>No urine for 8 hours</li>\r\n        <li>Sunken eyes</li>\r\n    </ul>', 38, '2025-11-22 11:30:00', '2025-11-22 11:30:00', '2025-12-09 17:08:20', 1, 'pediatric', 1, NULL, 1, 203, 'basic', 7),
(9, 7, 'Prenatal Nutrition Guide', '<h4>Eating for two? Focus on quality nutrition during pregnancy:</h4>\r\n    \r\n    <h5>Essential Nutrients:</h5>\r\n    <table border=\"1\" style=\"border-collapse: collapse; width: 100%;\">\r\n        <tr>\r\n            <th>Nutrient</th>\r\n            <th>Sources</th>\r\n            <th>Importance</th>\r\n        </tr>\r\n        <tr>\r\n            <td><strong>Folic Acid</strong></td>\r\n            <td>Leafy greens, lentils, fortified cereals</td>\r\n            <td>Prevents neural tube defects</td>\r\n        </tr>\r\n        <tr>\r\n            <td><strong>Iron</strong></td>\r\n            <td>Lean meat, beans, spinach</td>\r\n            <td>Prevents anemia</td>\r\n        </tr>\r\n        <tr>\r\n            <td><strong>Calcium</strong></td>\r\n            <td>Milk, yogurt, small fish with bones</td>\r\n            <td>Baby\'s bone development</td>\r\n        </tr>\r\n        <tr>\r\n            <td><strong>Protein</strong></td>\r\n            <td>Chicken, fish, eggs, beans</td>\r\n            <td>Building blocks for baby</td>\r\n        </tr>\r\n    </table>\r\n    \r\n    <h5>Foods to Avoid:</h5>\r\n    <ul>\r\n        <li>Raw or undercooked meat/eggs</li>\r\n        <li>Unpasteurized dairy products</li>\r\n        <li>High-mercury fish (shark, swordfish)</li>\r\n        <li>Excessive caffeine (limit to 200mg/day)</li>\r\n        <li>Alcohol</li>\r\n    </ul>\r\n    \r\n    <h5>Sample Daily Meal Plan:</h5>\r\n    <ul>\r\n        <li><strong>Breakfast:</strong> Fortified oatmeal with milk and fruit</li>\r\n        <li><strong>Mid-morning:</strong> Handful of nuts</li>\r\n        <li><strong>Lunch:</strong> Grilled chicken salad with avocado</li>\r\n        <li><strong>Afternoon:</strong> Yogurt with berries</li>\r\n        <li><strong>Dinner:</strong> Fish stew with vegetables and whole grain rice</li>\r\n    </ul>', 29, '2025-11-25 14:15:00', '2025-11-25 14:15:00', '2025-12-09 17:08:20', 1, 'maternal_health', 1, NULL, 1, 145, 'intermediate', 10),
(10, 7, 'Diabetes Prevention Tips', '<h4>Prevent or delay type 2 diabetes with these lifestyle changes:</h4>\r\n    \r\n    <h5>Dietary Changes:</h5>\r\n    <ul>\r\n        <li>Choose whole grains over refined carbohydrates</li>\r\n        <li>Eat plenty of vegetables (especially non-starchy ones)</li>\r\n        <li>Limit sugary drinks and processed foods</li>\r\n        <li>Include healthy fats (avocado, nuts, olive oil)</li>\r\n        <li>Control portion sizes</li>\r\n    </ul>\r\n    \r\n    <h5>Physical Activity:</h5>\r\n    <ul>\r\n        <li>Aim for 150 minutes of moderate exercise per week</li>\r\n        <li>Include both cardio and strength training</li>\r\n        <li>Reduce sedentary time - stand up every 30 minutes</li>\r\n        <li>Find activities you enjoy to maintain consistency</li>\r\n    </ul>\r\n    \r\n    <h5>Weight Management:</h5>\r\n    <ul>\r\n        <li>Lose 5-7% of body weight if overweight</li>\r\n        <li>Monitor waist circumference (less than 40\" for men, 35\" for women)</li>\r\n        <li>Focus on sustainable changes, not quick fixes</li>\r\n    </ul>\r\n    \r\n    <h5>Regular Monitoring:</h5>\r\n    <ul>\r\n        <li>Get annual check-ups if at risk</li>\r\n        <li>Know your numbers: blood sugar, blood pressure, cholesterol</li>\r\n        <li>Family history matters - inform your doctor</li>\r\n    </ul>', 24, '2025-12-05 15:30:00', '2025-12-05 15:30:00', '2025-12-09 17:08:20', 1, 'chronic_disease', 0, NULL, 1, 78, 'basic', 8);

-- --------------------------------------------------------

--
-- Structure de la table `hc_medical_requests`
--

CREATE TABLE `hc_medical_requests` (
  `request_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `request_title` varchar(200) NOT NULL,
  `request_description` text NOT NULL,
  `urgency_level` enum('low','medium','high') DEFAULT 'medium',
  `category` varchar(50) DEFAULT NULL,
  `patient_location` varchar(255) DEFAULT NULL,
  `request_status` enum('pending','responded','closed') DEFAULT 'pending',
  `volunteer_response` text DEFAULT NULL,
  `responded_by_user_id` int(11) DEFAULT NULL,
  `response_text` text DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `response_date` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `admin_assigned` tinyint(1) DEFAULT 0,
  `admin_assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_medical_requests`
--

INSERT INTO `hc_medical_requests` (`request_id`, `patient_id`, `request_title`, `request_description`, `urgency_level`, `category`, `patient_location`, `request_status`, `volunteer_response`, `responded_by_user_id`, `response_text`, `request_date`, `response_date`, `admin_notes`, `admin_assigned`, `admin_assigned_by`, `assigned_at`, `closed_at`) VALUES
(1, 48, 'Severe Chest Pain', 'I have been experiencing sharp chest pain for the past 3 hours. Pain radiates to left arm. Difficulty breathing.', 'high', 'cardiac', 'Kumasi, Ghana', 'pending', NULL, NULL, NULL, '2025-12-08 08:15:00', NULL, NULL, 0, NULL, NULL, NULL),
(2, 49, 'High Fever in Child', 'My 5-year-old daughter has had high fever (39.5°C) for 2 days. She is lethargic and not eating.', 'high', 'pediatric', 'Accra, Ghana', 'pending', NULL, NULL, NULL, '2025-12-08 10:30:00', NULL, NULL, 1, 13, NULL, NULL),
(3, 50, 'Difficulty Breathing', 'Sudden onset of difficulty breathing. Wheezing sound when breathing. Feeling tightness in chest.', 'high', 'respiratory', 'Tamale, Ghana', 'pending', NULL, NULL, NULL, '2025-12-08 14:45:00', NULL, NULL, 0, NULL, NULL, NULL),
(4, 51, 'Persistent Headache', 'Headache lasting for 5 days. Not relieved by painkillers. Accompanied by nausea.', 'medium', 'neurological', 'Cape Coast, Ghana', 'pending', NULL, NULL, NULL, '2025-12-07 09:20:00', NULL, NULL, 0, NULL, NULL, NULL),
(5, 52, 'Back Injury', 'Fell from ladder 2 days ago. Severe lower back pain. Difficulty standing straight.', 'medium', 'orthopedic', 'Takoradi, Ghana', 'pending', NULL, NULL, NULL, '2025-12-07 11:45:00', NULL, NULL, 1, 13, NULL, NULL),
(6, 53, 'Skin Rash', 'Red, itchy rash spreading on arms and chest. Started 3 days ago after gardening.', 'medium', 'dermatology', 'Sunyani, Ghana', 'pending', NULL, NULL, NULL, '2025-12-07 16:30:00', NULL, NULL, 0, NULL, NULL, NULL),
(7, 49, 'Cough and Cold', 'Persistent cough for 1 week. Yellowish phlegm. Low-grade fever.', 'low', 'respiratory', 'Accra, Ghana', 'responded', NULL, 64, 'Based on your symptoms, it sounds like a bacterial respiratory infection. I recommend:\n1. Increase fluid intake\n2. Use steam inhalation 2-3 times daily\n3. Over-the-counter expectorant cough syrup\n4. Rest and avoid cold beverages\nIf fever persists beyond 48 hours or breathing becomes difficult, please visit a clinic for antibiotic evaluation.', '2025-12-05 10:15:00', '2025-12-05 14:30:00', NULL, 1, 13, NULL, NULL),
(8, 48, 'Joint Pain', 'Knee joint pain worsening over 2 months. Swelling and stiffness in morning.', 'medium', 'orthopedic', 'Kumasi, Ghana', 'responded', NULL, 63, 'This could be osteoarthritis or rheumatism. Suggestions:\n1. Apply warm compress twice daily\n2. Gentle knee exercises (straight leg raises)\n3. Consider turmeric supplements (natural anti-inflammatory)\n4. Avoid climbing stairs when possible\n5. Use walking stick for support\nIf pain worsens, please see a doctor for proper diagnosis.', '2025-12-04 14:20:00', '2025-12-04 16:45:00', NULL, 0, NULL, NULL, NULL),
(9, 50, 'Anxiety and Sleep Issues', 'Difficulty sleeping due to racing thoughts. Feeling anxious throughout the day.', 'medium', 'mental_health', 'Tamale, Ghana', 'responded', NULL, 68, 'These are common anxiety symptoms. Try these techniques:\n1. Practice deep breathing exercises (4-7-8 technique)\n2. Establish bedtime routine (no screens 1 hour before sleep)\n3. Write down worries in a journal before bed\n4. Regular moderate exercise (morning walks)\n5. Limit caffeine after 2 PM\nIf symptoms persist, consider speaking with a mental health professional.', '2025-12-06 09:40:00', '2025-12-06 15:20:00', NULL, 1, 13, NULL, NULL),
(10, 51, 'Minor Burn', 'Burned finger while cooking 3 days ago. Redness and mild pain.', 'low', 'first_aid', 'Cape Coast, Ghana', 'closed', NULL, 65, 'For minor burns:\n1. Keep area clean with mild soap and water\n2. Apply aloe vera or antibiotic ointment\n3. Cover with sterile bandage\n4. Change dressing daily\n5. Watch for signs of infection (increased redness, pus)\nThe burn should heal within 7-10 days. Avoid popping any blisters.', '2025-12-03 11:30:00', '2025-12-03 13:45:00', NULL, 0, NULL, NULL, NULL),
(11, 52, 'Diabetes Management', 'Blood sugar readings consistently high (200-250 mg/dL). Feeling tired all the time.', 'medium', 'chronic_disease', 'Takoradi, Ghana', 'closed', NULL, 67, 'For better diabetes management:\n1. Monitor diet - reduce carbs and sugary foods\n2. Regular exercise (30 min walk daily)\n3. Stay hydrated with water\n4. Check blood sugar before meals and bedtime\n5. Consider reviewing medication with your doctor\nHigh blood sugar can cause fatigue. Please consult your doctor for medication adjustment.', '2025-12-02 15:10:00', '2025-12-02 17:30:00', NULL, 1, 13, NULL, NULL),
(12, 53, 'Pregnancy Concerns', 'First pregnancy, 12 weeks. Morning sickness severe, unable to keep food down.', 'medium', 'maternal_health', 'Sunyani, Ghana', 'responded', NULL, 66, 'Morning sickness tips:\n1. Eat small, frequent meals (every 2-3 hours)\n2. Try ginger tea or ginger candies\n3. Eat crackers before getting out of bed\n4. Avoid strong smells\n5. Stay hydrated with small sips throughout day\nIf vomiting prevents keeping any fluids down for 24 hours, contact your healthcare provider immediately.', '2025-12-01 08:45:00', '2025-12-01 12:20:00', NULL, 0, NULL, NULL, NULL),
(13, 54, 'Eye Infection', 'Red, itchy eyes with discharge. Started yesterday.', 'medium', 'ophthalmology', 'Elmina, Ghana', 'closed', NULL, 63, 'This sounds like conjunctivitis. Recommendations:\n1. Avoid touching/rubbing eyes\n2. Use clean, warm compress 3 times daily\n3. Wash hands frequently\n4. Use separate towels\n5. Over-the-counter eye drops for allergies if no improvement\nIf symptoms worsen or vision is affected, see a doctor as antibiotics may be needed.', '2025-11-30 14:30:00', '2025-12-01 10:15:00', NULL, 1, 13, NULL, NULL),
(14, 14, 'Severe Chest Pain', 'I have been experiencing sharp chest pain for the past 3 hours. Pain radiates to left arm. Difficulty breathing.', 'high', 'cardiac', 'Kumasi, Ghana', 'pending', NULL, NULL, NULL, '2025-12-08 08:15:00', NULL, NULL, 0, NULL, NULL, NULL),
(15, 15, 'Persistent Headache', 'Headache lasting for 5 days. Not relieved by painkillers. Accompanied by nausea.', 'medium', 'neurological', 'Accra, Ghana', 'pending', NULL, NULL, NULL, '2025-12-07 09:20:00', NULL, NULL, 0, NULL, NULL, NULL),
(16, 16, 'Cough and Cold', 'Persistent cough for 1 week. Yellowish phlegm. Low-grade fever.', 'low', 'respiratory', 'Tamale, Ghana', 'responded', NULL, 30, 'Based on your symptoms, it sounds like a bacterial respiratory infection. I recommend:\n1. Increase fluid intake\n2. Use steam inhalation 2-3 times daily\n3. Over-the-counter expectorant cough syrup\n4. Rest and avoid cold beverages\nIf fever persists beyond 48 hours or breathing becomes difficult, please visit a clinic for antibiotic evaluation.', '2025-12-05 10:15:00', '2025-12-05 14:30:00', NULL, 1, NULL, NULL, NULL),
(17, 17, 'Joint Pain', 'Knee joint pain worsening over 2 months. Swelling and stiffness in morning.', 'medium', 'orthopedic', 'Cape Coast, Ghana', 'responded', NULL, 29, 'This could be osteoarthritis or rheumatism. Suggestions:\n1. Apply warm compress twice daily\n2. Gentle knee exercises (straight leg raises)\n3. Consider turmeric supplements (natural anti-inflammatory)\n4. Avoid climbing stairs when possible\n5. Use walking stick for support\nIf pain worsens, please see a doctor for proper diagnosis.', '2025-12-04 14:20:00', '2025-12-04 16:45:00', NULL, 0, NULL, NULL, NULL),
(18, 18, 'Minor Burn', 'Burned finger while cooking 3 days ago. Redness and mild pain.', 'low', 'first_aid', 'Takoradi, Ghana', 'closed', NULL, 31, 'For minor burns:\n1. Keep area clean with mild soap and water\n2. Apply aloe vera or antibiotic ointment\n3. Cover with sterile bandage\n4. Change dressing daily\n5. Watch for signs of infection (increased redness, pus)\nThe burn should heal within 7-10 days. Avoid popping any blisters.', '2025-12-03 11:30:00', '2025-12-03 13:45:00', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `hc_platform_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `hc_platform_stats` (
`total_patients` bigint(21)
,`total_volunteers` bigint(21)
,`total_doctors` bigint(21)
,`total_requests` bigint(21)
,`pending_requests` bigint(21)
,`responded_requests` bigint(21)
,`closed_requests` bigint(21)
,`total_tips` bigint(21)
,`pending_verifications` bigint(21)
,`total_resources` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure de la table `hc_tip_likes`
--

CREATE TABLE `hc_tip_likes` (
  `like_id` int(11) NOT NULL,
  `health_tip_id` int(11) NOT NULL,
  `user_who_liked_id` int(11) NOT NULL,
  `like_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_tip_likes`
--

INSERT INTO `hc_tip_likes` (`like_id`, `health_tip_id`, `user_who_liked_id`, `like_date`) VALUES
(1, 1, 14, '2025-11-21 10:30:00'),
(2, 1, 15, '2025-11-21 11:45:00'),
(3, 1, 16, '2025-11-22 09:15:00'),
(4, 2, 17, '2025-11-23 14:20:00'),
(5, 2, 18, '2025-11-23 16:30:00');

-- --------------------------------------------------------

--
-- Structure de la table `hc_training_resources`
--

CREATE TABLE `hc_training_resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` enum('pdf','video','document','quiz') DEFAULT NULL,
  `uploaded_by_admin_id` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `access_level` enum('all','volunteers','doctors','patients') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `duration_minutes` int(11) DEFAULT 0,
  `total_views` int(11) DEFAULT 0,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `tags` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_training_resources`
--

INSERT INTO `hc_training_resources` (`id`, `title`, `description`, `category`, `file_path`, `file_type`, `uploaded_by_admin_id`, `upload_date`, `access_level`, `is_active`, `duration_minutes`, `total_views`, `difficulty_level`, `tags`) VALUES
(1, 'Basic First Aid Manual', 'Complete guide to first aid procedures for common emergencies', 'first_aid', '/resources/first_aid_manual.pdf', 'pdf', NULL, '2025-11-15 10:00:00', 'all', 1, 0, 0, 'beginner', NULL),
(2, 'Malaria Diagnosis and Treatment', 'Protocols for malaria diagnosis, treatment, and prevention', 'infectious_disease', '/resources/malaria_protocols.pdf', 'pdf', NULL, '2025-11-18 14:30:00', 'volunteers', 1, 0, 0, 'beginner', NULL),
(3, 'Communication Skills for Volunteers', 'How to effectively communicate with patients remotely', 'communication', '/resources/communication_skills.mp4', 'video', NULL, '2025-11-20 11:15:00', 'volunteers', 1, 0, 0, 'beginner', NULL),
(4, 'Diabetes Management Guide', 'Comprehensive guide to diabetes care and monitoring', 'chronic_disease', '/resources/diabetes_management.pdf', 'pdf', NULL, '2025-11-22 16:45:00', 'all', 1, 0, 0, 'beginner', NULL),
(5, 'Basic First Aid Certification Course', 'Complete guide covering CPR, wound care, burns, fractures, and emergency response protocols. Includes step-by-step instructions and illustrations.', 'first_aid', '/resources/first-aid-basics.pdf', 'pdf', 1, '2025-11-15 09:00:00', 'all', 1, 180, 0, 'beginner', 'first aid,emergency,cpr,wound care'),
(6, 'CPR & AED Training Video', 'Step-by-step video demonstration of CPR techniques and Automated External Defibrillator (AED) usage for adults, children, and infants.', 'emergency_care', '/resources/cpr-aed-training.mp4', 'video', 1, '2025-11-16 14:30:00', 'all', 1, 45, 0, 'beginner', 'cpr,aed,emergency,resuscitation'),
(7, 'Emergency Response Checklist', 'Quick reference guide for common medical emergencies including heart attacks, strokes, seizures, and allergic reactions.', 'emergency_care', '/resources/emergency-checklist.pdf', 'pdf', 1, '2025-11-17 11:15:00', 'volunteers', 1, 20, 0, 'beginner', 'emergency,checklist,quick guide'),
(8, 'Choking Relief Techniques', 'Learn how to perform abdominal thrusts (Heimlich maneuver) on adults, children, and infants. Video demonstration included.', 'first_aid', '/resources/choking-relief.mp4', 'video', 1, '2025-11-18 10:00:00', 'all', 1, 35, 0, 'beginner', 'choking,heimlich,first aid'),
(9, 'Bleeding Control and Wound Care', 'Essential techniques for controlling bleeding, cleaning wounds, and proper bandaging methods.', 'first_aid', '/resources/bleeding-control.pdf', 'pdf', 1, '2025-11-19 13:45:00', 'volunteers', 1, 60, 0, 'intermediate', 'bleeding,wound care,bandaging'),
(10, 'First Aid Quiz: Test Your Knowledge', 'Interactive quiz to assess your first aid knowledge. Perfect for volunteers preparing for certification.', 'first_aid', '/resources/first-aid-quiz.pdf', 'quiz', 1, '2025-11-20 16:20:00', 'volunteers', 1, 30, 0, 'beginner', 'quiz,assessment,test'),
(11, 'Prenatal Care Essentials', 'Complete guide to prenatal care, nutrition, warning signs, and preparation for delivery in rural settings.', 'maternal_health', '/resources/prenatal-care.pdf', 'pdf', 1, '2025-11-21 09:30:00', 'volunteers', 1, 120, 0, 'intermediate', 'pregnancy,prenatal,nutrition'),
(12, 'Childhood Immunization Guide', 'Updated immunization schedule, vaccine information, and administration guidelines for children (0-5 years).', 'pediatric', '/resources/immunization-guide.pdf', 'pdf', 1, '2025-11-22 15:45:00', 'all', 1, 90, 0, 'beginner', 'vaccination,immunization,children'),
(13, 'Newborn Care Workshop', 'Video workshop covering essential newborn care including breastfeeding, hygiene, and recognizing emergencies.', 'neonatal', '/resources/newborn-care.mp4', 'video', 1, '2025-11-23 14:00:00', 'volunteers', 1, 85, 0, 'intermediate', 'newborn,breastfeeding,infant care'),
(14, 'Common Childhood Illnesses', 'Guide to identifying and managing common childhood illnesses like malaria, diarrhea, and respiratory infections.', 'pediatric', '/resources/childhood-illnesses.pdf', 'pdf', 1, '2025-11-24 11:20:00', 'volunteers', 1, 75, 0, 'intermediate', 'children,illnesses,malaria,diarrhea'),
(15, 'Nutrition for Pregnant Women', 'Dietary guidelines and meal plans for pregnant women in resource-limited settings.', 'maternal_health', '/resources/pregnancy-nutrition.pdf', 'pdf', 1, '2025-11-25 10:15:00', 'all', 1, 50, 0, 'beginner', 'nutrition,pregnancy,diet'),
(16, 'Diabetes Management Program', 'Comprehensive training on diabetes monitoring, diet planning, medication management, and complication prevention.', 'chronic_disease', '/resources/diabetes-management.pdf', 'pdf', 1, '2025-11-26 09:00:00', 'volunteers', 1, 150, 0, 'intermediate', 'diabetes,chronic disease,monitoring'),
(17, 'Hypertension Control Guidelines', 'Evidence-based guidelines for blood pressure monitoring, lifestyle modifications, and medication adherence.', 'cardiovascular', '/resources/hypertension-guide.pdf', 'pdf', 1, '2025-11-27 14:30:00', 'all', 1, 95, 0, 'beginner', 'hypertension,bp,lifestyle'),
(18, 'Asthma & Respiratory Care', 'Managing asthma and other respiratory conditions, including inhaler techniques and emergency response.', 'respiratory', '/resources/asthma-care.mp4', 'video', 1, '2025-11-28 13:20:00', 'volunteers', 1, 70, 0, 'intermediate', 'asthma,respiratory,inhaler'),
(19, 'Living with Arthritis', 'Practical advice for managing arthritis pain, exercises, and daily living adaptations.', 'chronic_disease', '/resources/arthritis-guide.pdf', 'pdf', 1, '2025-11-29 11:45:00', 'patients', 1, 60, 0, 'beginner', 'arthritis,pain management,exercise'),
(20, 'Common Diseases in Rural Areas', 'Identification, management, and prevention of common diseases prevalent in rural communities.', 'medical_knowledge', '/resources/rural-diseases.pdf', 'pdf', 1, '2025-11-30 10:00:00', 'volunteers', 1, 180, 0, 'intermediate', 'diseases,rural,diagnosis'),
(21, 'Infection Control Protocols', 'Essential infection prevention and control measures including hand hygiene, PPE usage, and disinfection.', 'infection_control', '/resources/infection-control.mp4', 'video', 1, '2025-12-01 15:30:00', 'all', 1, 65, 0, 'beginner', 'infection control,hygiene,ppe'),
(22, 'Medication Safety Guide', 'Safe medication practices, dosage calculations, drug interactions, and storage requirements.', 'pharmacology', '/resources/medication-safety.pdf', 'pdf', 1, '2025-12-02 09:45:00', 'volunteers', 1, 110, 0, 'intermediate', 'medication,safety,dosage'),
(23, 'Vital Signs Monitoring', 'How to accurately measure and interpret vital signs: temperature, pulse, respiration, and blood pressure.', 'clinical_skills', '/resources/vital-signs.pdf', 'pdf', 1, '2025-12-03 13:15:00', 'volunteers', 1, 80, 0, 'beginner', 'vital signs,monitoring,measurement'),
(24, 'Medical Terminology Basics', 'Essential medical terminology for volunteers to effectively communicate with healthcare professionals.', 'medical_knowledge', '/resources/medical-terminology.pdf', 'pdf', 1, '2025-12-04 16:00:00', 'volunteers', 1, 120, 0, 'beginner', 'terminology,medical terms'),
(25, 'Mental Health First Aid', 'Training on recognizing mental health issues, providing initial support, and referral to professional help.', 'mental_health', '/resources/mental-health-first-aid.pdf', 'pdf', 1, '2025-12-05 10:30:00', 'volunteers', 1, 140, 0, 'intermediate', 'mental health,first aid,support'),
(26, 'Stress Management Techniques', 'Practical techniques for managing stress, anxiety, and burnout for both patients and volunteers.', 'wellness', '/resources/stress-management.mp4', 'video', 1, '2025-12-06 14:00:00', 'all', 1, 55, 0, 'beginner', 'stress,anxiety,wellness'),
(27, 'Coping with Chronic Illness', 'Psychological strategies for patients dealing with long-term health conditions.', 'mental_health', '/resources/coping-chronic-illness.pdf', 'pdf', 1, '2025-12-07 11:20:00', 'patients', 1, 70, 0, 'beginner', 'coping,chronic illness,mental health'),
(28, 'Effective Patient Communication', 'Training on communicating effectively with patients, active listening, empathy, and cultural sensitivity.', 'communication', '/resources/patient-communication.pdf', 'pdf', 1, '2025-12-08 09:15:00', 'volunteers', 1, 100, 0, 'beginner', 'communication,patient care,empathy'),
(29, 'Telemedicine Best Practices', 'Guidelines for conducting effective remote consultations and using digital tools for healthcare delivery.', 'telemedicine', '/resources/telemedicine-guide.pdf', 'pdf', 1, '2025-12-09 13:40:00', 'volunteers', 1, 85, 0, 'intermediate', 'telemedicine,remote care,digital health'),
(30, 'Cultural Competency in Healthcare', 'Understanding cultural differences in healthcare beliefs and practices for effective service delivery.', 'communication', '/resources/cultural-competency.mp4', 'video', 1, '2025-12-10 15:00:00', 'volunteers', 1, 75, 0, 'intermediate', 'culture,diversity,sensitivity'),
(31, 'HealthConnect Volunteer Handbook', 'Complete handbook covering all policies, procedures, and ethical guidelines for volunteers.', 'volunteer_training', '/resources/volunteer-handbook.pdf', 'pdf', 1, '2025-11-10 08:00:00', 'volunteers', 1, 200, 0, 'beginner', 'handbook,policies,ethics'),
(32, 'Volunteer Onboarding Presentation', 'Introduction to HealthConnect platform, mission, and volunteer responsibilities.', 'volunteer_training', '/resources/onboarding-presentation.pdf', 'pdf', 1, '2025-11-11 10:30:00', 'volunteers', 1, 60, 0, 'beginner', 'onboarding,orientation'),
(33, 'Ethical Guidelines for Volunteers', 'Detailed ethical framework and decision-making guidelines for healthcare volunteers.', 'ethics', '/resources/ethical-guidelines.pdf', 'pdf', 1, '2025-11-12 14:00:00', 'volunteers', 1, 90, 0, 'intermediate', 'ethics,guidelines,decision making'),
(34, 'Community Health Assessment', 'How to conduct basic community health assessments and identify priority health needs.', 'community_health', '/resources/community-assessment.pdf', 'pdf', 1, '2025-11-13 11:15:00', 'volunteers', 1, 120, 0, 'advanced', 'community,assessment,health needs');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `hc_training_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `hc_training_stats` (
`category` varchar(100)
,`total_resources` bigint(21)
,`pdf_count` decimal(22,0)
,`video_count` decimal(22,0)
,`quiz_count` decimal(22,0)
,`avg_duration` decimal(14,4)
,`min_difficulty` enum('beginner','intermediate','advanced')
,`max_difficulty` enum('beginner','intermediate','advanced')
);

-- --------------------------------------------------------

--
-- Structure de la table `hc_users`
--

CREATE TABLE `hc_users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `email_address` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_role` enum('patient','volunteer','doctor','admin') NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `certificate_filename` varchar(255) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_users`
--

INSERT INTO `hc_users` (`user_id`, `full_name`, `profession`, `email_address`, `location`, `password_hash`, `user_role`, `is_approved`, `certificate_filename`, `date_created`, `created_at`, `last_updated`, `is_active`) VALUES
(1, 'furairah idi', 'Patient', 'idi@example.com', NULL, '$2y$10$m12LBaRJjSeKhiPTiR5I0..rPxBISBz2YvA8jTGQVe6VqesAvkQfe', 'patient', 1, NULL, '2025-12-03 12:23:57', '2025-12-03 12:23:57', '2025-12-09 17:08:20', 1),
(2, 'furairah idi', 'Healthcare Volunteer', 'furairah@example.com', NULL, '$2y$10$2c0g4sSe5VOxvjrEDf8iju.l523LwGqGqnmyla.hUDtJ83rcSG.7y', 'volunteer', 1, NULL, '2025-12-03 12:24:51', '2025-12-03 12:24:51', '2025-12-09 17:08:20', 1),
(3, 'furairah idi', 'Doctor', 'furaira@example.com', NULL, '$2y$10$k71QwcFkWbolZCkWRYpZ.eugPBSn7NIIKzqolztkgA8NhlgimFFhG', 'doctor', 0, 'cert_69302c477ebdd5.80211546.png', '2025-12-03 12:25:43', '2025-12-03 12:25:43', '2025-12-09 17:08:20', 1),
(4, 'idi', 'Patient', 'furair@example.com', NULL, '$2y$10$VV08ZkU8m20IztAdguszRuR5Bhcfh/UoS0xyf9hCAXMu1WPz0V6pK', 'patient', 1, NULL, '2025-12-03 13:31:56', '2025-12-03 13:31:56', '2025-12-09 17:08:20', 1),
(5, 'fatma', 'Doctor', 'ali@example.com', NULL, '$2y$10$UJAPKi8/SKnsK2NuACxzo.uCb/2nr7vLuzZ8V3ofvEiInqH570BJy', 'doctor', 1, 'cert_69303c8fb87612.30576019.png', '2025-12-03 13:35:11', '2025-12-03 13:35:11', '2025-12-09 19:52:57', 1),
(6, 'volunteer', 'Nurse', 'volonteer@example.com', 'accra', '$2y$10$UIaBqxX.rR0Wwe54kEELM.9rnKOCosDlP21ikg.gFN8eCpupeJ76y', 'volunteer', 1, NULL, '2025-12-03 13:51:09', '2025-12-03 13:51:09', '2025-12-09 17:08:20', 1),
(7, 'Doctor', 'Doctor', 'Doctor@example.com', NULL, '$2y$10$5H7/431NlYfk4l9QyNlFeeYfLL3lMp3jxw1YIDvXs0GAJieGZABbS', 'doctor', 0, 'cert_69305aa0419274.20794054.png', '2025-12-03 15:43:28', '2025-12-03 15:43:28', '2025-12-09 17:08:20', 1),
(8, 'Foureiratou ZAKARI', 'Patient', 'furairah@ashesi.edu.gh', NULL, '$2y$10$000hOCiZoQVvH24F/IR3qOninNv3ghM0XT3.J34uC5cE47Y8pE5T.', 'patient', 1, NULL, '2025-12-09 13:06:37', '2025-12-09 13:06:37', '2025-12-09 17:08:20', 1),
(9, 'me', 'Healthcare Volunteer', 'you@example.com', NULL, '$2y$10$AXc919uzINhJ4xg..U/A/OiePqnu4ZlaQj7qmByvAxVGCP/A1g.Dq', 'volunteer', 1, NULL, '2025-12-09 14:10:04', '2025-12-09 14:10:04', '2025-12-09 17:08:20', 1),
(10, 'me', 'Healthcare Volunteer', 'youme@example.com', NULL, '$2y$10$ykcK9IOIITCRs.bI5BEsOOgcEsQrf4oD4L9EbeLBLoi3kiAnt9yFS', 'volunteer', 1, NULL, '2025-12-09 14:14:52', '2025-12-09 14:14:52', '2025-12-09 17:08:20', 1),
(11, 'me', 'Patient', 'youm@example.com', NULL, '$2y$10$pAmZBMachg.zJEfsavDXzu.nIHTQ3RF652epjn9C0AheWfGhNzlju', 'patient', 1, NULL, '2025-12-09 14:18:03', '2025-12-09 14:18:03', '2025-12-09 17:08:20', 1),
(12, 'me', 'Patient', 'youma@example.com', NULL, '$2y$10$pjdmUftJYtSQpnc6kXyq7.QQMRt3BFiY6oihCRA2WmhKIXUWbOshm', 'patient', 1, NULL, '2025-12-09 14:24:10', '2025-12-09 14:24:10', '2025-12-09 17:08:20', 1),
(13, 'System Administrator', 'Platform Admin', 'admin@healthconnect.org', 'Accra, Ghana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NULL, '2025-11-01 08:00:00', '2025-11-01 08:00:00', '2025-12-09 17:08:20', 1),
(14, 'Kwame Mensah', 'Farmer', 'kwame.mensah@example.com', 'Kumasi, Ghana', '$2y$10$passwordhash1', 'patient', 1, NULL, '2025-11-15 09:30:00', '2025-11-15 09:30:00', '2025-12-09 17:08:20', 1),
(15, 'Ama Serwaa', 'Teacher', 'ama.serwaa@example.com', 'Accra, Ghana', '$2y$10$passwordhash2', 'patient', 1, NULL, '2025-11-20 10:15:00', '2025-11-20 10:15:00', '2025-12-09 17:08:20', 1),
(16, 'Kofi Asare', 'Shopkeeper', 'kofi.asare@example.com', 'Tamale, Ghana', '$2y$10$passwordhash3', 'patient', 1, NULL, '2025-11-25 14:45:00', '2025-11-25 14:45:00', '2025-12-09 17:08:20', 1),
(17, 'Akosua Boateng', 'Housewife', 'akosua.boateng@example.com', 'Cape Coast, Ghana', '$2y$10$passwordhash4', 'patient', 1, NULL, '2025-11-28 16:20:00', '2025-11-28 16:20:00', '2025-12-09 17:08:20', 1),
(18, 'Yaw Owusu', 'Driver', 'yaw.owusu@example.com', 'Takoradi, Ghana', '$2y$10$passwordhash5', 'patient', 1, NULL, '2025-12-01 11:10:00', '2025-12-01 11:10:00', '2025-12-09 17:08:20', 1),
(19, 'Efua Dapaah', 'Student', 'efua.dapaah@example.com', 'Sunyani, Ghana', '$2y$10$passwordhash6', 'patient', 1, NULL, '2025-12-02 13:30:00', '2025-12-02 13:30:00', '2025-12-09 17:08:20', 1),
(20, 'Kwabena Anokye', 'Fisherman', 'kwabena.anokye@example.com', 'Elmina, Ghana', '$2y$10$passwordhash7', 'patient', 1, NULL, '2025-12-03 15:45:00', '2025-12-03 15:45:00', '2025-12-09 17:08:20', 1),
(21, 'Adwoa Fosu', 'Trader', 'adwoa.fosu@example.com', 'Koforidua, Ghana', '$2y$10$passwordhash8', 'patient', 1, NULL, '2025-12-04 17:20:00', '2025-12-04 17:20:00', '2025-12-09 17:08:20', 1),
(22, 'Nana Kwaku', 'Elder', 'nana.kwaku@example.com', 'Wa, Ghana', '$2y$10$passwordhash9', 'patient', 1, NULL, '2025-12-05 10:00:00', '2025-12-05 10:00:00', '2025-12-09 17:08:20', 1),
(23, 'Mariama Issah', 'Student Nurse', 'mariama.issah@example.com', 'Tamale, Ghana', '$2y$10$passwordhash10', 'patient', 1, NULL, '2025-12-06 14:15:00', '2025-12-06 14:15:00', '2025-12-09 17:08:20', 1),
(24, 'Samuel Agyeman', 'Construction Worker', 'samuel.agyeman@example.com', 'Tema, Ghana', '$2y$10$passwordhash11', 'patient', 1, NULL, '2025-12-07 09:45:00', '2025-12-07 09:45:00', '2025-12-09 17:08:20', 1),
(25, 'Comfort Tetteh', 'Market Woman', 'comfort.tetteh@example.com', 'Ho, Ghana', '$2y$10$passwordhash12', 'patient', 1, NULL, '2025-12-08 16:30:00', '2025-12-08 16:30:00', '2025-12-09 17:08:20', 1),
(26, 'Ibrahim Mohammed', 'Mechanic', 'ibrahim.mohammed@example.com', 'Bolgatanga, Ghana', '$2y$10$passwordhash13', 'patient', 1, NULL, '2025-12-09 11:20:00', '2025-12-09 11:20:00', '2025-12-09 17:08:20', 1),
(27, 'Patience Asante', 'Seamstress', 'patience.asante@example.com', 'Kumasi, Ghana', '$2y$10$passwordhash14', 'patient', 1, NULL, '2025-12-09 13:40:00', '2025-12-09 13:40:00', '2025-12-09 17:08:20', 1),
(28, 'Bright Osei', 'Driver Mate', 'bright.osei@example.com', 'Accra, Ghana', '$2y$10$passwordhash15', 'patient', 1, NULL, '2025-12-09 15:10:00', '2025-12-09 15:10:00', '2025-12-09 17:08:20', 1),
(29, 'Dr. Grace Ansah', 'Retired Doctor', 'grace.ansah@example.com', 'Accra, Ghana', '$2y$10$passwordhash16', 'volunteer', 1, NULL, '2025-11-10 08:30:00', '2025-11-10 08:30:00', '2025-12-09 17:08:20', 1),
(30, 'Nurse Comfort Mensah', 'Registered Nurse', 'comfort.mensah@example.com', 'Kumasi, Ghana', '$2y$10$passwordhash17', 'volunteer', 1, NULL, '2025-11-12 10:45:00', '2025-11-12 10:45:00', '2025-12-09 17:08:20', 1),
(31, 'Emmanuel Ofori', 'Medical Student', 'emmanuel.ofori@example.com', 'Cape Coast, Ghana', '$2y$10$passwordhash18', 'volunteer', 1, NULL, '2025-11-15 14:20:00', '2025-11-15 14:20:00', '2025-12-09 17:08:20', 1),
(32, 'Felicia Quartey', 'Community Health Worker', 'felicia.quartey@example.com', 'Tamale, Ghana', '$2y$10$passwordhash19', 'volunteer', 1, NULL, '2025-11-18 16:15:00', '2025-11-18 16:15:00', '2025-12-09 17:08:20', 1),
(33, 'Ruth Abban', 'Pharmacist', 'ruth.abban@example.com', 'Takoradi, Ghana', '$2y$10$passwordhash20', 'volunteer', 1, NULL, '2025-11-22 09:30:00', '2025-11-22 09:30:00', '2025-12-09 17:08:20', 1),
(34, 'Daniel Tawia', 'Public Health Officer', 'daniel.tawia@example.com', 'Sunyani, Ghana', '$2y$10$passwordhash21', 'volunteer', 1, NULL, '2025-11-25 11:45:00', '2025-11-25 11:45:00', '2025-12-09 17:08:20', 1),
(35, 'Adelaide Sarpong', 'Midwife', 'adelaide.sarpong@example.com', 'Koforidua, Ghana', '$2y$10$passwordhash22', 'volunteer', 1, NULL, '2025-11-28 13:20:00', '2025-11-28 13:20:00', '2025-12-09 17:08:20', 1),
(36, 'Michael Anim', 'Physiotherapist', 'michael.anim@example.com', 'Tema, Ghana', '$2y$10$passwordhash23', 'volunteer', 1, NULL, '2025-12-01 15:30:00', '2025-12-01 15:30:00', '2025-12-09 17:08:20', 1),
(37, 'Dr. Kojo Ampofo', 'General Practitioner', 'kojo.ampofo@example.com', 'Accra, Ghana', '$2y$10$passwordhash24', 'doctor', 1, NULL, '2025-11-05 09:00:00', '2025-11-05 09:00:00', '2025-12-09 17:08:20', 1),
(38, 'Dr. Akua Nkrumah', 'Pediatrician', 'akua.nkrumah@example.com', 'Kumasi, Ghana', '$2y$10$passwordhash25', 'doctor', 1, NULL, '2025-11-08 11:30:00', '2025-11-08 11:30:00', '2025-12-09 17:08:20', 1),
(39, 'Dr. Kwame Osei', 'Surgeon', 'kwame.osei@example.com', 'Tamale, Ghana', '$2y$10$passwordhash26', 'doctor', 0, NULL, '2025-11-12 14:15:00', '2025-11-12 14:15:00', '2025-12-09 17:08:20', 1),
(40, 'Dr. Esi Mensah', 'Gynecologist', 'esi.mensah@example.com', 'Cape Coast, Ghana', '$2y$10$passwordhash27', 'doctor', 1, NULL, '2025-11-15 16:45:00', '2025-11-15 16:45:00', '2025-12-09 17:08:20', 1),
(41, 'Dr. Yaw Boateng', 'Dermatologist', 'yaw.boateng@example.com', 'Takoradi, Ghana', '$2y$10$passwordhash28', 'doctor', 0, NULL, '2025-11-20 10:20:00', '2025-11-20 10:20:00', '2025-12-09 17:08:20', 1),
(42, 'Dr. Abena Asare', 'Psychiatrist', 'abena.asare@example.com', 'Sunyani, Ghana', '$2y$10$passwordhash29', 'doctor', 1, NULL, '2025-11-25 13:30:00', '2025-11-25 13:30:00', '2025-12-09 17:08:20', 1),
(43, 'Dr. Kofi Agyeman', 'Cardiologist', 'kofi.agyeman@example.com', 'Koforidua, Ghana', '$2y$10$passwordhash30', 'doctor', 1, NULL, '2025-11-28 15:40:00', '2025-11-28 15:40:00', '2025-12-09 17:08:20', 1),
(44, 'Dr. Ama Serwaa Bonsu', 'Ophthalmologist', 'ama.bonsu@example.com', 'Ho, Ghana', '$2y$10$passwordhash31', 'doctor', 0, NULL, '2025-12-02 09:15:00', '2025-12-02 09:15:00', '2025-12-09 17:08:20', 1),
(45, 'Dr. Nana Kwasi', 'ENT Specialist', 'nana.kwasi@example.com', 'Bolgatanga, Ghana', '$2y$10$passwordhash32', 'doctor', 1, NULL, '2025-12-05 11:25:00', '2025-12-05 11:25:00', '2025-12-09 17:08:20', 1),
(46, 'Dr. Miriam Tetteh', 'Dentist', 'miriam.tetteh@example.com', 'Tema, Ghana', '$2y$10$passwordhash33', 'doctor', 0, NULL, '2025-12-08 14:50:00', '2025-12-08 14:50:00', '2025-12-09 17:08:20', 1),
(48, 'Kwame Mensah', 'Farmer', 'kwame.mensah1@example.com', 'Kumasi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-11-15 09:30:00', '2025-11-15 09:30:00', '2025-12-09 17:08:20', 1),
(49, 'Ama Serwaa', 'Teacher', 'ama.serwaa1@example.com', 'Accra, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-11-20 10:15:00', '2025-11-20 10:15:00', '2025-12-09 17:08:20', 1),
(50, 'Kofi Asare', 'Shopkeeper', 'kofi.asare1@example.com', 'Tamale, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-11-25 14:45:00', '2025-11-25 14:45:00', '2025-12-09 17:08:20', 1),
(51, 'Akosua Boateng', 'Housewife', 'akosua.boateng1@example.com', 'Cape Coast, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-11-28 16:20:00', '2025-11-28 16:20:00', '2025-12-09 17:08:20', 1),
(52, 'Yaw Owusu', 'Driver', 'yaw.owusu1@example.com', 'Takoradi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-01 11:10:00', '2025-12-01 11:10:00', '2025-12-09 17:08:20', 1),
(53, 'Efua Dapaah', 'Student', 'efua.dapaah1@example.com', 'Sunyani, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-02 13:30:00', '2025-12-02 13:30:00', '2025-12-09 17:08:20', 1),
(54, 'Kwabena Anokye', 'Fisherman', 'kwabena.anokye1@example.com', 'Elmina, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-03 15:45:00', '2025-12-03 15:45:00', '2025-12-09 17:08:20', 1),
(55, 'Adwoa Fosu', 'Trader', 'adwoa.fosu1@example.com', 'Koforidua, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-04 17:20:00', '2025-12-04 17:20:00', '2025-12-09 17:08:20', 1),
(56, 'Nana Kwaku', 'Elder', 'nana.kwaku1@example.com', 'Wa, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-05 10:00:00', '2025-12-05 10:00:00', '2025-12-09 17:08:20', 1),
(57, 'Mariama Issah', 'Student Nurse', 'mariama.issah1@example.com', 'Tamale, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-06 14:15:00', '2025-12-06 14:15:00', '2025-12-09 17:08:20', 1),
(58, 'Samuel Agyeman', 'Construction Worker', 'samuel.agyeman1@example.com', 'Tema, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-07 09:45:00', '2025-12-07 09:45:00', '2025-12-09 17:08:20', 1),
(59, 'Comfort Tetteh', 'Market Woman', 'comfort.tetteh1@example.com', 'Ho, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-08 16:30:00', '2025-12-08 16:30:00', '2025-12-09 17:08:20', 1),
(60, 'Ibrahim Mohammed', 'Mechanic', 'ibrahim.mohammed1@example.com', 'Bolgatanga, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-09 11:20:00', '2025-12-09 11:20:00', '2025-12-09 17:08:20', 1),
(61, 'Patience Asante', 'Seamstress', 'patience.asante1@example.com', 'Kumasi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-09 13:40:00', '2025-12-09 13:40:00', '2025-12-09 17:08:20', 1),
(62, 'Bright Osei', 'Driver Mate', 'bright.osei1@example.com', 'Accra, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'patient', 1, NULL, '2025-12-09 15:10:00', '2025-12-09 15:10:00', '2025-12-09 17:08:20', 1),
(63, 'Dr. Grace Ansah', 'Retired Doctor', 'grace.ansah1@example.com', 'Accra, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-10 08:30:00', '2025-11-10 08:30:00', '2025-12-09 17:08:20', 1),
(64, 'Nurse Comfort Mensah', 'Registered Nurse', 'comfort.mensah1@example.com', 'Kumasi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-12 10:45:00', '2025-11-12 10:45:00', '2025-12-09 17:08:20', 1),
(65, 'Emmanuel Ofori', 'Medical Student', 'emmanuel.ofori1@example.com', 'Cape Coast, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-15 14:20:00', '2025-11-15 14:20:00', '2025-12-09 17:08:20', 1),
(66, 'Felicia Quartey', 'Community Health Worker', 'felicia.quartey1@example.com', 'Tamale, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-18 16:15:00', '2025-11-18 16:15:00', '2025-12-09 17:08:20', 1),
(67, 'Ruth Abban', 'Pharmacist', 'ruth.abban1@example.com', 'Takoradi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-22 09:30:00', '2025-11-22 09:30:00', '2025-12-09 17:08:20', 1),
(68, 'Daniel Tawia', 'Public Health Officer', 'daniel.tawia1@example.com', 'Sunyani, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-25 11:45:00', '2025-11-25 11:45:00', '2025-12-09 17:08:20', 1),
(69, 'Adelaide Sarpong', 'Midwife', 'adelaide.sarpong1@example.com', 'Koforidua, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-11-28 13:20:00', '2025-11-28 13:20:00', '2025-12-09 17:08:20', 1),
(70, 'Michael Anim', 'Physiotherapist', 'michael.anim1@example.com', 'Tema, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'volunteer', 1, NULL, '2025-12-01 15:30:00', '2025-12-01 15:30:00', '2025-12-09 17:08:20', 1),
(71, 'Dr. Kojo Ampofo', 'General Practitioner', 'kojo.ampofo1@example.com', 'Accra, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 1, NULL, '2025-11-05 09:00:00', '2025-11-05 09:00:00', '2025-12-09 17:08:20', 1),
(72, 'Dr. Akua Nkrumah', 'Pediatrician', 'akua.nkrumah1@example.com', 'Kumasi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 1, NULL, '2025-11-08 11:30:00', '2025-11-08 11:30:00', '2025-12-09 17:08:20', 1),
(73, 'Dr. Kwame Osei', 'Surgeon', 'kwame.osei1@example.com', 'Tamale, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 0, NULL, '2025-11-12 14:15:00', '2025-11-12 14:15:00', '2025-12-09 17:08:20', 1),
(74, 'Dr. Esi Mensah', 'Gynecologist', 'esi.mensah1@example.com', 'Cape Coast, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 1, NULL, '2025-11-15 16:45:00', '2025-11-15 16:45:00', '2025-12-09 17:08:20', 1),
(75, 'Dr. Yaw Boateng', 'Dermatologist', 'yaw.boateng1@example.com', 'Takoradi, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 0, NULL, '2025-11-20 10:20:00', '2025-11-20 10:20:00', '2025-12-09 17:08:20', 1),
(76, 'Dr. Abena Asare', 'Psychiatrist', 'abena.asare1@example.com', 'Sunyani, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 1, NULL, '2025-11-25 13:30:00', '2025-11-25 13:30:00', '2025-12-09 17:08:20', 1),
(77, 'Dr. Kofi Agyeman', 'Cardiologist', 'kofi.agyeman1@example.com', 'Koforidua, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 1, NULL, '2025-11-28 15:40:00', '2025-11-28 15:40:00', '2025-12-09 17:08:20', 1),
(78, 'Dr. Ama Serwaa Bonsu', 'Ophthalmologist', 'ama.bonsu1@example.com', 'Ho, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 0, NULL, '2025-12-02 09:15:00', '2025-12-02 09:15:00', '2025-12-09 17:08:20', 1),
(79, 'Dr. Nana Kwasi', 'ENT Specialist', 'nana.kwasi1@example.com', 'Bolgatanga, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 1, NULL, '2025-12-05 11:25:00', '2025-12-05 11:25:00', '2025-12-09 17:08:20', 1),
(80, 'Dr. Miriam Tetteh', 'Dentist', 'miriam.tetteh1@example.com', 'Tema, Ghana', '$2y$10$WlZtZ3N0ZXJ2aWNlIQ==', 'doctor', 0, NULL, '2025-12-08 14:50:00', '2025-12-08 14:50:00', '2025-12-09 17:08:20', 1);

--
-- Déclencheurs `hc_users`
--
DELIMITER $$
CREATE TRIGGER `set_profession_on_insert` BEFORE INSERT ON `hc_users` FOR EACH ROW BEGIN
    IF NEW.profession IS NULL OR NEW.profession = '' THEN
        CASE NEW.user_role
            WHEN 'volunteer' THEN SET NEW.profession = 'Healthcare Volunteer';
            WHEN 'patient' THEN SET NEW.profession = 'Patient';
            WHEN 'doctor' THEN SET NEW.profession = 'Doctor';
            ELSE SET NEW.profession = 'Patient';
        END CASE;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `hc_user_sessions`
--

CREATE TABLE `hc_user_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_remembered` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hc_user_sessions`
--

INSERT INTO `hc_user_sessions` (`session_id`, `user_id`, `login_time`, `last_activity`, `ip_address`, `user_agent`, `is_remembered`) VALUES
('13b7ae3292caaacd910208a112f7af21f0f2ff10bd1de567ad7bf37f89f0b8ba', 8, '2025-12-09 13:07:18', '2025-12-09 13:07:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1),
('13d8c06f9245909ebaae10628cff4f538f562da9f8c172d2b762e73994f73df3', 4, '2025-12-03 14:30:05', '2025-12-03 14:30:05', NULL, NULL, 1),
('5513c57c4dd7d9071295b02da578e54a3179b0e9bf55189c414f807cd9747731', 4, '2025-12-03 13:47:22', '2025-12-03 13:47:22', NULL, NULL, 1),
('8553936b794a6debb37341a943acb3ab579be2a5662c1bbcd2a9f2a50812fca3', 4, '2025-12-03 13:32:26', '2025-12-03 13:32:26', NULL, NULL, 1),
('session_def456', 29, '2025-12-09 14:53:10', '2025-12-09 16:23:10', '192.168.1.101', 'Mozilla/5.0 Volunteer App', 0),
('session_ghi789', 14, '2025-12-08 16:53:10', '2025-12-09 13:53:10', '192.168.1.106', 'Mozilla/5.0 Patient App', 1);

-- --------------------------------------------------------

--
-- Structure de la vue `hc_doctor_tips_stats`
--
DROP TABLE IF EXISTS `hc_doctor_tips_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hc_doctor_tips_stats`  AS SELECT `h`.`doctor_user_id` AS `doctor_user_id`, `u`.`full_name` AS `doctor_name`, count(0) AS `total_tips`, sum(`h`.`total_likes`) AS `total_likes`, sum(`h`.`total_views`) AS `total_views`, avg(`h`.`reading_time_minutes`) AS `avg_reading_time`, max(`h`.`tip_date`) AS `latest_tip` FROM (`hc_health_tips` `h` join `hc_users` `u` on(`h`.`doctor_user_id` = `u`.`user_id`)) WHERE `h`.`is_published` = 1 GROUP BY `h`.`doctor_user_id`, `u`.`full_name` ORDER BY sum(`h`.`total_likes`) DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `hc_platform_stats`
--
DROP TABLE IF EXISTS `hc_platform_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hc_platform_stats`  AS SELECT (select count(0) from `hc_users` where `hc_users`.`user_role` = 'patient' and `hc_users`.`is_active` = 1) AS `total_patients`, (select count(0) from `hc_users` where `hc_users`.`user_role` = 'volunteer' and `hc_users`.`is_active` = 1) AS `total_volunteers`, (select count(0) from `hc_users` where `hc_users`.`user_role` = 'doctor' and `hc_users`.`is_approved` = 1 and `hc_users`.`is_active` = 1) AS `total_doctors`, (select count(0) from `hc_medical_requests`) AS `total_requests`, (select count(0) from `hc_medical_requests` where `hc_medical_requests`.`request_status` = 'pending') AS `pending_requests`, (select count(0) from `hc_medical_requests` where `hc_medical_requests`.`request_status` = 'responded') AS `responded_requests`, (select count(0) from `hc_medical_requests` where `hc_medical_requests`.`request_status` = 'closed') AS `closed_requests`, (select count(0) from `hc_health_tips` where `hc_health_tips`.`is_active` = 1) AS `total_tips`, (select count(0) from `hc_doctor_verifications` where `hc_doctor_verifications`.`verification_status` = 'pending_review') AS `pending_verifications`, (select count(0) from `hc_training_resources` where `hc_training_resources`.`is_active` = 1) AS `total_resources` ;

-- --------------------------------------------------------

--
-- Structure de la vue `hc_training_stats`
--
DROP TABLE IF EXISTS `hc_training_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hc_training_stats`  AS SELECT `hc_training_resources`.`category` AS `category`, count(0) AS `total_resources`, sum(case when `hc_training_resources`.`file_type` = 'pdf' then 1 else 0 end) AS `pdf_count`, sum(case when `hc_training_resources`.`file_type` = 'video' then 1 else 0 end) AS `video_count`, sum(case when `hc_training_resources`.`file_type` = 'quiz' then 1 else 0 end) AS `quiz_count`, avg(`hc_training_resources`.`duration_minutes`) AS `avg_duration`, min(`hc_training_resources`.`difficulty_level`) AS `min_difficulty`, max(`hc_training_resources`.`difficulty_level`) AS `max_difficulty` FROM `hc_training_resources` WHERE `hc_training_resources`.`is_active` = 1 GROUP BY `hc_training_resources`.`category` ORDER BY count(0) DESC ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `hc_activity_logs`
--
ALTER TABLE `hc_activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_log_date` (`log_date`),
  ADD KEY `idx_user_activity` (`user_id`,`activity_type`);

--
-- Index pour la table `hc_doctor_verifications`
--
ALTER TABLE `hc_doctor_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `doctor_user_id` (`doctor_user_id`),
  ADD KEY `reviewed_by_admin_id` (`reviewed_by_admin_id`),
  ADD KEY `idx_verification_status` (`verification_status`);

--
-- Index pour la table `hc_feedback`
--
ALTER TABLE `hc_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `volunteer_id` (`volunteer_id`);

--
-- Index pour la table `hc_forum_comments`
--
ALTER TABLE `hc_forum_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Index pour la table `hc_forum_posts`
--
ALTER TABLE `hc_forum_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category` (`category`);

--
-- Index pour la table `hc_health_tips`
--
ALTER TABLE `hc_health_tips`
  ADD PRIMARY KEY (`tip_id`),
  ADD KEY `idx_doctor_user` (`doctor_user_id`),
  ADD KEY `idx_tip_date` (`tip_date`);

--
-- Index pour la table `hc_medical_requests`
--
ALTER TABLE `hc_medical_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `responded_by_user_id` (`responded_by_user_id`),
  ADD KEY `idx_request_status` (`request_status`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `admin_assigned_by` (`admin_assigned_by`);

--
-- Index pour la table `hc_tip_likes`
--
ALTER TABLE `hc_tip_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_user_tip_like` (`health_tip_id`,`user_who_liked_id`),
  ADD KEY `user_who_liked_id` (`user_who_liked_id`),
  ADD KEY `idx_health_tip_id` (`health_tip_id`);

--
-- Index pour la table `hc_training_resources`
--
ALTER TABLE `hc_training_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_uploaded_by` (`uploaded_by_admin_id`);

--
-- Index pour la table `hc_users`
--
ALTER TABLE `hc_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email_address` (`email_address`),
  ADD KEY `idx_user_role` (`user_role`),
  ADD KEY `idx_email` (`email_address`);

--
-- Index pour la table `hc_user_sessions`
--
ALTER TABLE `hc_user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `hc_activity_logs`
--
ALTER TABLE `hc_activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT pour la table `hc_doctor_verifications`
--
ALTER TABLE `hc_doctor_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `hc_feedback`
--
ALTER TABLE `hc_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `hc_forum_comments`
--
ALTER TABLE `hc_forum_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `hc_forum_posts`
--
ALTER TABLE `hc_forum_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `hc_health_tips`
--
ALTER TABLE `hc_health_tips`
  MODIFY `tip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `hc_medical_requests`
--
ALTER TABLE `hc_medical_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `hc_tip_likes`
--
ALTER TABLE `hc_tip_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `hc_training_resources`
--
ALTER TABLE `hc_training_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `hc_users`
--
ALTER TABLE `hc_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `hc_activity_logs`
--
ALTER TABLE `hc_activity_logs`
  ADD CONSTRAINT `hc_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `hc_users` (`user_id`);

--
-- Contraintes pour la table `hc_doctor_verifications`
--
ALTER TABLE `hc_doctor_verifications`
  ADD CONSTRAINT `hc_doctor_verifications_ibfk_1` FOREIGN KEY (`doctor_user_id`) REFERENCES `hc_users` (`user_id`),
  ADD CONSTRAINT `hc_doctor_verifications_ibfk_2` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `hc_users` (`user_id`);

--
-- Contraintes pour la table `hc_health_tips`
--
ALTER TABLE `hc_health_tips`
  ADD CONSTRAINT `hc_health_tips_ibfk_1` FOREIGN KEY (`doctor_user_id`) REFERENCES `hc_users` (`user_id`);

--
-- Contraintes pour la table `hc_medical_requests`
--
ALTER TABLE `hc_medical_requests`
  ADD CONSTRAINT `hc_medical_requests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `hc_users` (`user_id`),
  ADD CONSTRAINT `hc_medical_requests_ibfk_2` FOREIGN KEY (`responded_by_user_id`) REFERENCES `hc_users` (`user_id`),
  ADD CONSTRAINT `hc_medical_requests_ibfk_3` FOREIGN KEY (`admin_assigned_by`) REFERENCES `hc_users` (`user_id`);

--
-- Contraintes pour la table `hc_tip_likes`
--
ALTER TABLE `hc_tip_likes`
  ADD CONSTRAINT `hc_tip_likes_ibfk_1` FOREIGN KEY (`health_tip_id`) REFERENCES `hc_health_tips` (`tip_id`),
  ADD CONSTRAINT `hc_tip_likes_ibfk_2` FOREIGN KEY (`user_who_liked_id`) REFERENCES `hc_users` (`user_id`);

--
-- Contraintes pour la table `hc_training_resources`
--
ALTER TABLE `hc_training_resources`
  ADD CONSTRAINT `fk_uploaded_by` FOREIGN KEY (`uploaded_by_admin_id`) REFERENCES `hc_users` (`user_id`);

--
-- Contraintes pour la table `hc_user_sessions`
--
ALTER TABLE `hc_user_sessions`
  ADD CONSTRAINT `hc_user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `hc_users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
