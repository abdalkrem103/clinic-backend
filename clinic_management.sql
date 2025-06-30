-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 28 يونيو 2025 الساعة 04:43
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_management`
--

-- --------------------------------------------------------

--
-- بنية الجدول `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `appointments`:
--   `patient_id`
--       `patients` -> `id`
--   `doctor_id`
--       `doctors` -> `id`
--   `appointment_id`
--       `doctor_schedules` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_id`, `appointment_date`, `appointment_time`, `status`, `notes`, `created_at`) VALUES
(1, 4, 1, NULL, '2025-06-28', '09:00:00', 'confirmed', '', '2025-06-27 04:10:06'),
(2, 4, 1, NULL, '2025-06-28', '18:30:00', 'confirmed', '', '2025-06-27 05:26:01');

-- --------------------------------------------------------

--
-- بنية الجدول `dental_chart`
--

CREATE TABLE `dental_chart` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `tooth_id` varchar(10) NOT NULL,
  `note` text NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `service_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `dental_chart`:
--   `patient_id`
--       `patients` -> `id`
--   `service_id`
--       `services` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `dental_chart`
--

INSERT INTO `dental_chart` (`id`, `patient_id`, `tooth_id`, `note`, `status`, `created_at`, `updated_at`, `service_id`) VALUES
(1, 1, '12', 'تسوس بسيط', 'cavity', '2025-06-27 03:21:14', NULL, 1),
(2, 1, '23', 'حشو قديم يحتاج تجديد', 'filling', '2025-06-27 03:21:14', NULL, 3),
(3, 1, '31', 'سليم', 'healthy', '2025-06-27 03:21:14', NULL, NULL),
(4, 1, '34', 'يحتاج تنظيف', 'cleaning_needed', '2025-06-27 03:21:14', NULL, 2),
(5, 2, '11', 'تسوس عميق', 'deep_cavity', '2025-06-27 03:21:14', NULL, 3),
(6, 2, '22', 'مكسور', 'broken', '2025-06-27 03:21:14', NULL, 4),
(7, 2, '33', 'سليم', 'healthy', '2025-06-27 03:21:14', NULL, NULL),
(8, 2, '44', 'مفقود', 'missing', '2025-06-27 03:21:14', NULL, 6),
(9, 3, '11', 'حشوة اولية', 'filling', '2025-06-27 03:45:55', NULL, NULL),
(10, 4, '28', 'هنع', 'extraction', '2025-06-27 06:46:04', NULL, NULL),
(11, 4, '24', 'خحهتا', 'filling', '2025-06-27 06:57:49', NULL, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(100) NOT NULL,
  `experience` int(11) DEFAULT NULL,
  `working_hours` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `doctors`:
--

--
-- إرجاع أو استيراد بيانات الجدول `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `specialty`, `experience`, `working_hours`, `image`, `created_at`) VALUES
(1, 'د. أحمد محمد', 'تقويم الأسنان', 10, 'الأحد - الخميس: 9:00 - 17:00', NULL, '2025-06-27 03:21:14'),
(2, 'د. فاطمة علي', 'جراحة الفم والأسنان', 8, 'الأحد - الخميس: 8:00 - 16:00', NULL, '2025-06-27 03:21:14');

-- --------------------------------------------------------

--
-- بنية الجدول `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `doctor_schedules`:
--   `doctor_id`
--       `doctors` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `week_start`, `day_of_week`, `start_time`, `end_time`, `slot_duration`, `is_available`, `notes`) VALUES
(1, 1, '2025-01-27', 1, '09:00:00', '17:00:00', 30, 1, NULL),
(2, 1, '2025-01-27', 2, '09:00:00', '17:00:00', 30, 1, NULL),
(3, 1, '2025-01-27', 3, '09:00:00', '17:00:00', 30, 1, NULL),
(4, 1, '2025-01-27', 4, '09:00:00', '17:00:00', 30, 1, NULL),
(5, 1, '2025-01-27', 5, '09:00:00', '17:00:00', 30, 1, NULL),
(6, 2, '2025-01-27', 1, '08:00:00', '16:00:00', 30, 1, NULL),
(7, 2, '2025-01-27', 2, '08:00:00', '16:00:00', 30, 1, NULL),
(8, 2, '2025-01-27', 3, '08:00:00', '16:00:00', 30, 1, NULL),
(9, 2, '2025-01-27', 4, '08:00:00', '16:00:00', 30, 1, NULL),
(10, 2, '2025-01-27', 5, '08:00:00', '16:00:00', 30, 1, NULL),
(11, 1, '2025-06-22', 1, '08:00:00', '12:00:00', 30, 1, ''),
(12, 1, '2025-06-22', 1, '17:00:00', '22:00:00', 30, 1, ''),
(13, 1, '2025-06-22', 2, '08:00:00', '12:00:00', 30, 1, ''),
(14, 1, '2025-06-22', 2, '17:00:00', '22:00:00', 30, 1, ''),
(15, 1, '2025-06-22', 3, '08:00:00', '12:00:00', 30, 1, ''),
(16, 1, '2025-06-22', 3, '17:00:00', '22:00:00', 30, 1, ''),
(17, 1, '2025-06-22', 4, '08:00:00', '12:00:00', 30, 1, ''),
(18, 1, '2025-06-22', 4, '17:00:00', '22:00:00', 30, 1, ''),
(19, 1, '2025-06-22', 7, '08:00:00', '12:00:00', 30, 1, ''),
(20, 1, '2025-06-22', 7, '17:00:00', '22:00:00', 30, 1, ''),
(21, 1, '2025-06-22', 5, '08:00:00', '12:00:00', 30, 1, ''),
(22, 1, '2025-06-22', 5, '17:00:00', '22:00:00', 30, 1, '');

-- --------------------------------------------------------

--
-- بنية الجدول `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_threshold` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `inventory`:
--

--
-- إرجاع أو استيراد بيانات الجدول `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `quantity`, `min_threshold`, `created_at`) VALUES
(1, 'قفازات طبية', 1002, 100, '2025-06-27 03:21:14'),
(2, 'إبر حقن', 500, 50, '2025-06-27 03:21:14'),
(3, 'مخدر موضعي', 200, 20, '2025-06-27 03:21:14'),
(4, 'حشوات أسنان', 299, 30, '2025-06-27 03:21:14'),
(5, 'أدوات تنظيف', 150, 15, '2025-06-27 03:21:14'),
(6, 'حشوة', 17, 18, '2025-06-27 03:27:11');

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','inventory','payment','system') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `notifications`:
--   `user_id`
--       `users` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `type`, `is_read`, `created_at`, `updated_at`, `user_id`) VALUES
(1, 'مرحباً بك في النظام', 'تم تسجيل الدخول بنجاح', 'system', 0, '2025-06-27 03:21:14', NULL, 1),
(2, 'تحديث النظام', 'تم تحديث النظام إلى الإصدار الجديد', 'system', 0, '2025-06-27 03:21:14', NULL, 1),
(3, 'جدول مواعيد جديد', 'تم إضافة جدول مواعيد جديد للطبيب بتاريخ: 2025-06-22', '', 0, '2025-06-27 03:26:32', NULL, NULL),
(4, 'تحديث خريطة الأسنان', 'تم إضافة ملاحظة جديدة على السن رقم 11', 'system', 0, '2025-06-27 03:45:55', NULL, 3),
(5, 'تحديث خريطة الأسنان', 'تم إضافة ملاحظة جديدة على السن رقم 28', 'system', 0, '2025-06-27 06:46:04', NULL, 4),
(6, 'تحديث خريطة الأسنان', 'تم إضافة ملاحظة جديدة على السن رقم 24', 'system', 0, '2025-06-27 06:57:49', NULL, 4);

-- --------------------------------------------------------

--
-- بنية الجدول `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `password_resets`:
--

-- --------------------------------------------------------

--
-- بنية الجدول `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `patients`:
--   `user_id`
--       `users` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `last_name`, `phone_number`, `email`, `date_of_birth`, `gender`, `address`, `blood_type`, `allergies`, `created_at`, `user_id`, `national_id`) VALUES
(1, 'محمد', 'أحمد', '0501234567', 'mohamed@example.com', '1990-05-15', 'male', NULL, 'O+', NULL, '2025-06-27 03:21:14', 4, NULL),
(2, 'سارة', 'محمد', '0509876543', 'sara@example.com', '1985-08-20', 'female', NULL, 'A+', NULL, '2025-06-27 03:21:14', NULL, NULL),
(3, 'سعد', 'الكناص', '0997888316', 'abdalkrem112002@gmail.com', '2002-01-01', 'male', 'canda', 'O+', 'لا يوجد ', '2025-06-27 03:37:09', NULL, NULL),
(4, 'محمد ', 'الكناص', '0330303030', 'aboodraaealhdod123@gmail.com', '2002-01-01', 'male', 'الإسكندرية', 'O-', ' لا يوجد ', '2025-06-27 04:09:38', 5, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','bank_transfer') NOT NULL,
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'paid',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_gateway` varchar(50) DEFAULT NULL,
  `payment_url` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `paid_amount` decimal(10,2) GENERATED ALWAYS AS (`total_amount`) STORED,
  `remaining_amount` decimal(10,2) GENERATED ALWAYS AS (0.00) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `payments`:
--   `patient_id`
--       `patients` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `payments`
--

INSERT INTO `payments` (`id`, `patient_id`, `total_amount`, `payment_method`, `payment_status`, `transaction_id`, `payment_gateway`, `payment_url`, `payment_date`, `notes`, `payment_details`, `created_at`) VALUES
(6, 4, 5000.00, 'cash', 'paid', NULL, NULL, NULL, '2025-06-27 06:45:05', '', NULL, '2025-06-27 06:45:05'),
(7, 4, 100.00, 'cash', 'paid', NULL, NULL, NULL, '2025-06-27 06:58:27', '', NULL, '2025-06-27 06:58:27');

-- --------------------------------------------------------

--
-- بنية الجدول `payment_services`
--

CREATE TABLE `payment_services` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `appointment_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `dental_chart_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `payment_services`:
--   `payment_id`
--       `payments` -> `id`
--   `service_id`
--       `services` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `payment_services`
--

INSERT INTO `payment_services` (`id`, `payment_id`, `service_id`, `service_name`, `service_price`, `quantity`, `appointment_id`, `notes`, `dental_chart_id`) VALUES
(1, 6, 5, 'تقويم الأسنان', 5000.00, 1, NULL, NULL, NULL),
(2, 7, 1, 'فحص عام', 100.00, 1, NULL, NULL, 11);

-- --------------------------------------------------------

--
-- بنية الجدول `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `services`:
--

--
-- إرجاع أو استيراد بيانات الجدول `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `created_at`) VALUES
(1, 'فحص عام', 'فحص شامل للأسنان واللثة', 100.00, '2025-06-27 03:21:14'),
(2, 'تنظيف الأسنان', 'تنظيف وإزالة الجير', 150.00, '2025-06-27 03:21:14'),
(3, 'حشو الأسنان', 'حشو تجويف الأسنان', 200.00, '2025-06-27 03:21:14'),
(4, 'خلع الأسنان', 'خلع الأسنان التالفة', 300.00, '2025-06-27 03:21:14'),
(5, 'تقويم الأسنان', 'تقويم الأسنان المعوجة', 5000.00, '2025-06-27 03:21:14'),
(6, 'زراعة الأسنان', 'زراعة أسنان جديدة', 8000.00, '2025-06-27 03:21:14');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `token`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '3e3316d0f7b169b84d0557ae2a41b7fe2ddfc0204494fe16cb562ca6de511153', '2025-06-27 03:24:27', '2025-06-27 03:21:14'),
(2, 'doctor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', NULL, NULL, '2025-06-27 03:21:14'),
(3, 'receptionist1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', NULL, NULL, '2025-06-27 03:21:14'),
(4, 'patient1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', NULL, NULL, '2025-06-27 03:21:14'),
(5, '05020016693', '$2y$10$zDPPC2J2dXcHfFe/I.ARqOqTz2Up3tLoNX270GIYcCE87CRanDBd2', 'patient', NULL, NULL, '2025-06-27 04:09:38');

-- --------------------------------------------------------

--
-- بنية الجدول `user_notification_settings`
--

CREATE TABLE `user_notification_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `user_notification_settings`:
--   `user_id`
--       `users` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `user_notification_settings`
--

INSERT INTO `user_notification_settings` (`id`, `user_id`, `setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'email_notifications', 1, '2025-06-27 03:27:35', '2025-06-27 03:27:35'),
(2, 1, 'sms_notifications', 1, '2025-06-27 03:27:35', '2025-06-27 03:27:35'),
(3, 1, 'appointment_reminders', 1, '2025-06-27 03:27:35', '2025-06-27 03:27:35'),
(4, 1, 'inventory_alerts', 1, '2025-06-27 03:27:35', '2025-06-27 03:27:35'),
(5, 1, 'payment_reminders', 1, '2025-06-27 03:27:35', '2025-06-27 03:27:35');

-- --------------------------------------------------------

--
-- بنية الجدول `xrays`
--

CREATE TABLE `xrays` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `image_data` varchar(255) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `analysis_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis_result`)),
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `analysis_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `xrays`:
--   `patient_id`
--       `patients` -> `id`
--

--
-- إرجاع أو استيراد بيانات الجدول `xrays`
--

INSERT INTO `xrays` (`id`, `patient_id`, `image_data`, `upload_date`, `analysis_result`, `description`, `created_at`, `analysis_date`) VALUES
(1, 3, '685e13af36019_1750995887.jpg', '2025-06-27 03:44:47', '{\"analysis_result\":{\"follow_up_plan\":{\"follow_up_date\":\"3 \\u0623\\u0634\\u0647\\u0631\",\"priority\":1},\"metadata\":{\"image_quality\":{\"brightness\":\"\\u0645\\u0646\\u0627\\u0633\\u0628\\u0629\",\"contrast\":\"\\u062c\\u064a\\u062f\",\"overall_quality\":\"\\u062c\\u064a\\u062f\\u0629\"},\"image_statistics\":{\"image_type\":\"\\u0623\\u0634\\u0639\\u0629 \\u0623\\u0633\\u0646\\u0627\\u0646\",\"mean_intensity\":127.80524063805025,\"std_intensity\":73.63642679443959}},\"primary_findings\":{\"confidence\":100,\"severity_assessment\":\"\\u0645\\u0646\\u062e\\u0641\\u0636\",\"status\":\"\\u0637\\u0628\\u064a\\u0639\\u064a\",\"yolo_detections\":[],\"yolo_labels\":[]},\"recommendations\":{\"follow_up\":[\"\\u0645\\u062a\\u0627\\u0628\\u0639\\u0629 \\u062f\\u0648\\u0631\\u064a\\u0629 \\u0643\\u0644 3 \\u0623\\u0634\\u0647\\u0631\",\"\\u0625\\u062c\\u0631\\u0627\\u0621 \\u0641\\u062d\\u0648\\u0635\\u0627\\u062a \\u062f\\u0648\\u0631\\u064a\\u0629\"],\"immediate_actions\":[\"\\u0645\\u0631\\u0627\\u062c\\u0639\\u0629 \\u0627\\u0644\\u0637\\u0628\\u064a\\u0628 \\u0627\\u0644\\u0645\\u062e\\u062a\\u0635\",\"\\u0625\\u062c\\u0631\\u0627\\u0621 \\u0627\\u0644\\u0641\\u062d\\u0648\\u0635\\u0627\\u062a \\u0627\\u0644\\u0644\\u0627\\u0632\\u0645\\u0629\"],\"prevention\":[\"\\u0627\\u0644\\u062d\\u0641\\u0627\\u0638 \\u0639\\u0644\\u0649 \\u0646\\u0638\\u0627\\u0641\\u0629 \\u0627\\u0644\\u0641\\u0645\",\"\\u062a\\u062c\\u0646\\u0628 \\u0627\\u0644\\u0623\\u0637\\u0639\\u0645\\u0629 \\u0627\\u0644\\u0636\\u0627\\u0631\\u0629\"]},\"report_id\":\"XR-99680\",\"timestamp\":\"2025-06-27T03:44:58\"},\"message\":\"\\u062a\\u0645 \\u062a\\u062d\\u0644\\u064a\\u0644 \\u0627\\u0644\\u0635\\u0648\\u0631\\u0629 \\u0628\\u0646\\u062c\\u0627\\u062d\",\"message_en\":\"Image analysis completed successfully\",\"success\":true}', NULL, '2025-06-27 06:44:47', '2025-06-27 06:44:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointments_ibfk_3` (`appointment_id`);

--
-- Indexes for table `dental_chart`
--
ALTER TABLE `dental_chart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `fk_patient_user` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `payment_services`
--
ALTER TABLE `payment_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`);

--
-- Indexes for table `user_notification_settings`
--
ALTER TABLE `user_notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_setting` (`user_id`,`setting_name`);

--
-- Indexes for table `xrays`
--
ALTER TABLE `xrays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dental_chart`
--
ALTER TABLE `dental_chart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_services`
--
ALTER TABLE `payment_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_notification_settings`
--
ALTER TABLE `user_notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `xrays`
--
ALTER TABLE `xrays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `doctor_schedules` (`id`);

--
-- قيود الجداول `dental_chart`
--
ALTER TABLE `dental_chart`
  ADD CONSTRAINT `fk_dental_chart_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dental_chart_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- قيود الجداول `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- قيود الجداول `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);

--
-- قيود الجداول `payment_services`
--
ALTER TABLE `payment_services`
  ADD CONSTRAINT `fk_payment_services_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `user_notification_settings`
--
ALTER TABLE `user_notification_settings`
  ADD CONSTRAINT `user_notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `xrays`
--
ALTER TABLE `xrays`
  ADD CONSTRAINT `xrays_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
