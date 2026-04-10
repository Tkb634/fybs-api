-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2026 at 06:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fybs_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `addiction_breaker_programs`
--

CREATE TABLE `addiction_breaker_programs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_type` enum('substance','focus','healing','comprehensive') DEFAULT NULL,
  `addiction_type` varchar(100) DEFAULT NULL,
  `severity_level` enum('low','moderate','high','severe') DEFAULT 'moderate',
  `start_date` date DEFAULT NULL,
  `target_end_date` date DEFAULT NULL,
  `current_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `total_clean_days` int(11) DEFAULT 0,
  `relapse_count` int(11) DEFAULT 0,
  `last_relapse_date` date DEFAULT NULL,
  `current_stage` enum('assessment','commitment','detox','recovery','maintenance','graduated') DEFAULT 'assessment',
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `support_group_id` int(11) DEFAULT NULL,
  `daily_checkin_time` time DEFAULT NULL,
  `trigger_alerts_enabled` tinyint(1) DEFAULT 1,
  `progress_shared` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addiction_breaker_programs`
--

INSERT INTO `addiction_breaker_programs` (`id`, `user_id`, `program_type`, `addiction_type`, `severity_level`, `start_date`, `target_end_date`, `current_streak`, `longest_streak`, `total_clean_days`, `relapse_count`, `last_relapse_date`, `current_stage`, `progress_percentage`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`, `support_group_id`, `daily_checkin_time`, `trigger_alerts_enabled`, `progress_shared`, `created_at`, `updated_at`) VALUES
(1, 1, 'healing', 'anxiety', 'high', '2026-01-22', NULL, 1, 1, 3, 2, '2026-01-24', 'commitment', 3.33, 'tk', 'o6778890', NULL, NULL, NULL, 1, 0, '2026-01-22 17:18:47', '2026-01-27 07:58:10'),
(2, 4, 'healing', 'Social media ', 'high', '2026-01-24', '2026-04-24', 0, 0, 0, 1, '2026-01-24', 'assessment', 0.00, 'Tino', '0780737934', NULL, NULL, '19:00:00', 1, 0, '2026-01-24 09:30:42', '2026-01-24 09:31:12');

-- --------------------------------------------------------

--
-- Table structure for table `addiction_coping_strategies`
--

CREATE TABLE `addiction_coping_strategies` (
  `id` int(11) NOT NULL,
  `program_type` enum('substance','focus','healing','all') DEFAULT 'all',
  `strategy_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('distraction','mindfulness','physical','social','spiritual','cognitive','emergency') DEFAULT 'cognitive',
  `duration_minutes` int(11) DEFAULT 5,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `effectiveness_rating` decimal(3,2) DEFAULT 0.00,
  `instructions` text DEFAULT NULL,
  `tips` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addiction_coping_strategies`
--

INSERT INTO `addiction_coping_strategies` (`id`, `program_type`, `strategy_name`, `description`, `category`, `duration_minutes`, `difficulty_level`, `effectiveness_rating`, `instructions`, `tips`, `icon`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'substance', 'Urge Surfing', 'Ride out cravings like a wave without acting on them', 'mindfulness', 10, 'beginner', 0.85, 'Notice the craving without judgment, observe physical sensations, breathe through it, watch it pass', 'Remember: cravings typically last 15-20 minutes', 'fa-water', 1, 1, '2026-01-22 17:09:33'),
(2, 'all', '5-4-3-2-1 Grounding', 'Use senses to ground yourself in the present moment', 'mindfulness', 5, 'beginner', 0.90, 'Name 5 things you can see, 4 things you can touch, 3 things you can hear, 2 things you can smell, 1 thing you can taste', 'Practice even when not in crisis to build the skill', 'fa-mountain', 2, 1, '2026-01-22 17:09:33'),
(3, 'focus', 'Pomodoro Technique', 'Work in focused intervals with regular breaks', 'cognitive', 25, 'beginner', 0.80, 'Set timer for 25 minutes of focused work, then take a 5-minute break. After 4 cycles, take a longer break', 'Use breaks for physical movement or mindfulness', 'fa-clock', 3, 1, '2026-01-22 17:09:33'),
(4, 'substance', 'Call a Support Person', 'Reach out when cravings feel overwhelming', 'social', 15, 'beginner', 0.95, 'Call someone from your support list and talk through the craving', 'Have support numbers saved for quick access', 'fa-phone', 4, 1, '2026-01-22 17:09:33'),
(5, 'healing', 'Journaling Release', 'Write down thoughts and feelings to process emotions', '', 20, 'beginner', 0.75, 'Write without stopping for 15 minutes about whatever comes to mind', 'Don\'t worry about grammar or spelling', 'fa-book', 5, 1, '2026-01-22 17:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `addiction_daily_checkins`
--

CREATE TABLE `addiction_daily_checkins` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `checkin_date` date NOT NULL,
  `checkin_time` time DEFAULT NULL,
  `day_clean` int(11) DEFAULT 0,
  `craving_intensity` enum('none','mild','moderate','strong','overwhelming') DEFAULT 'none',
  `craving_duration_minutes` int(11) DEFAULT 0,
  `coping_strategies_used` text DEFAULT NULL,
  `mood_before` enum('very_happy','happy','neutral','sad','very_sad','anxious') DEFAULT 'neutral',
  `mood_after` enum('very_happy','happy','neutral','sad','very_sad','anxious') DEFAULT 'neutral',
  `substance_free` tinyint(1) DEFAULT 1,
  `relapse_occurred` tinyint(1) DEFAULT 0,
  `relapse_substance` varchar(100) DEFAULT NULL,
  `relapse_amount` varchar(50) DEFAULT NULL,
  `relapse_context` text DEFAULT NULL,
  `support_sought` tinyint(1) DEFAULT 0,
  `support_type` varchar(100) DEFAULT NULL,
  `sleep_hours` decimal(3,1) DEFAULT NULL,
  `water_intake_liters` decimal(3,2) DEFAULT NULL,
  `exercise_minutes` int(11) DEFAULT 0,
  `mindfulness_minutes` int(11) DEFAULT 0,
  `challenges_faced` text DEFAULT NULL,
  `victories_achieved` text DEFAULT NULL,
  `gratitude_entry` text DEFAULT NULL,
  `motivation_quote` text DEFAULT NULL,
  `checkin_streak` int(11) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addiction_daily_checkins`
--

INSERT INTO `addiction_daily_checkins` (`id`, `program_id`, `checkin_date`, `checkin_time`, `day_clean`, `craving_intensity`, `craving_duration_minutes`, `coping_strategies_used`, `mood_before`, `mood_after`, `substance_free`, `relapse_occurred`, `relapse_substance`, `relapse_amount`, `relapse_context`, `support_sought`, `support_type`, `sleep_hours`, `water_intake_liters`, `exercise_minutes`, `mindfulness_minutes`, `challenges_faced`, `victories_achieved`, `gratitude_entry`, `motivation_quote`, `checkin_streak`, `is_completed`, `completed_at`, `created_at`) VALUES
(1, 1, '2026-01-22', '21:21:07', 1, 'none', 0, NULL, 'happy', 'neutral', 1, 0, NULL, NULL, NULL, 0, NULL, 8.0, NULL, 0, 0, 'jk', 'bhh', NULL, NULL, 0, 1, '2026-01-22 21:21:07', '2026-01-22 19:21:07'),
(2, 1, '2026-01-24', '10:46:17', 2, 'overwhelming', 0, NULL, 'anxious', 'neutral', 1, 0, NULL, NULL, NULL, 0, NULL, 5.0, NULL, 0, 0, 'ddx', 'xxxa', NULL, NULL, 0, 1, '2026-01-24 10:46:17', '2026-01-24 08:46:17'),
(3, 2, '2026-01-24', '11:31:12', 0, 'overwhelming', 0, NULL, 'very_sad', 'neutral', 0, 0, NULL, NULL, NULL, 0, NULL, 6.0, NULL, 0, 0, 'Ddd', 'Dd', NULL, NULL, 0, 1, '2026-01-24 11:31:12', '2026-01-24 09:31:12'),
(4, 1, '2026-01-27', '09:58:10', 3, 'overwhelming', 0, NULL, 'very_sad', 'neutral', 1, 0, NULL, NULL, NULL, 0, NULL, 5.0, NULL, 0, 0, 'ggg', 'ggg', NULL, NULL, 0, 1, '2026-01-27 09:58:10', '2026-01-27 07:58:10');

-- --------------------------------------------------------

--
-- Table structure for table `addiction_educational_content`
--

CREATE TABLE `addiction_educational_content` (
  `id` int(11) NOT NULL,
  `program_type` enum('substance','focus','healing','all') DEFAULT 'all',
  `content_type` enum('article','video','audio','worksheet','infographic','quiz') DEFAULT 'article',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `order_index` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addiction_educational_content`
--

INSERT INTO `addiction_educational_content` (`id`, `program_type`, `content_type`, `title`, `description`, `content`, `media_url`, `duration_minutes`, `category`, `difficulty_level`, `order_index`, `is_featured`, `views`, `created_at`) VALUES
(1, 'substance', 'article', 'Understanding Cravings', 'Learn what happens in your brain when you crave substances', 'When you use addictive substances, your brain\'s reward system gets hijacked...', NULL, 10, 'science', 'beginner', 1, 1, 45, '2026-01-22 17:09:33'),
(2, 'focus', 'video', 'Digital Minimalism', 'Practical strategies to reduce digital distractions', NULL, 'https://youtube.com/watch?v=abc123', 15, 'productivity', 'beginner', 2, 1, 78, '2026-01-22 17:09:33'),
(3, 'healing', 'worksheet', 'Emotional Regulation', 'Identify and manage overwhelming emotions', 'This worksheet helps you track emotional triggers and responses...', NULL, 20, 'emotional_health', 'beginner', 3, 1, 32, '2026-01-22 17:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `addiction_motivational_quotes`
--

CREATE TABLE `addiction_motivational_quotes` (
  `id` int(11) NOT NULL,
  `program_type` enum('substance','focus','healing','all') DEFAULT 'all',
  `quote_text` text NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `category` enum('recovery','hope','strength','perseverance','mindfulness','general') DEFAULT 'recovery',
  `days_target` set('1','3','7','14','30','60','90','180','365') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addiction_motivational_quotes`
--

INSERT INTO `addiction_motivational_quotes` (`id`, `program_type`, `quote_text`, `author`, `category`, `days_target`, `is_active`, `created_at`) VALUES
(1, 'all', 'Recovery is not a race. You don\'t have to feel guilty if it takes you longer than you thought it would.', 'Unknown', 'recovery', '1,3,7,30', 1, '2026-01-22 17:09:33'),
(2, 'substance', 'Every day you stay sober is a victory. No matter how small it feels.', 'Unknown', 'strength', '1,7,14,30,90', 1, '2026-01-22 17:09:33'),
(3, 'focus', 'You can\'t stop the waves, but you can learn to surf.', 'Jon Kabat-Zinn', 'mindfulness', '1,3,7,14', 1, '2026-01-22 17:09:33'),
(4, 'healing', 'Healing doesn\'t mean the damage never existed. It means the damage no longer controls your life.', 'Akshay Dubey', 'hope', '7,30,60,90', 1, '2026-01-22 17:09:33'),
(5, 'all', 'One day at a time. One moment at a time. One breath at a time.', 'Unknown', 'perseverance', '1,3,7,14,30,60,90,180,365', 1, '2026-01-22 17:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `addiction_progress_milestones`
--

CREATE TABLE `addiction_progress_milestones` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `milestone_type` enum('days_clean','assessment_score','skill_mastered','goal_achieved','custom') DEFAULT 'days_clean',
  `milestone_name` varchar(100) NOT NULL,
  `target_value` int(11) DEFAULT NULL,
  `current_value` int(11) DEFAULT 0,
  `achieved_date` date DEFAULT NULL,
  `reward` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_achieved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addiction_progress_milestones`
--

INSERT INTO `addiction_progress_milestones` (`id`, `program_id`, `milestone_type`, `milestone_name`, `target_value`, `current_value`, `achieved_date`, `reward`, `notes`, `is_achieved`, `created_at`) VALUES
(1, 2, 'days_clean', 'First 24 Hours', 1, 0, NULL, NULL, NULL, 0, '2026-01-24 09:30:42'),
(2, 2, 'days_clean', 'One Week Clean', 7, 0, NULL, NULL, NULL, 0, '2026-01-24 09:30:42'),
(3, 2, 'days_clean', 'Two Weeks Strong', 14, 0, NULL, NULL, NULL, 0, '2026-01-24 09:30:42'),
(4, 2, 'days_clean', '30-Day Milestone', 30, 0, NULL, NULL, NULL, 0, '2026-01-24 09:30:42'),
(5, 2, 'days_clean', '60 Days Victory', 60, 0, NULL, NULL, NULL, 0, '2026-01-24 09:30:42'),
(6, 2, 'days_clean', '90-Day Complete', 90, 0, NULL, NULL, NULL, 0, '2026-01-24 09:30:42');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-10 08:36:06'),
(2, 3, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-10 08:36:28'),
(3, 1, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-10 08:38:53'),
(4, 1, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-24 09:11:52'),
(5, 1, 'add_prayer', 'Added prayer request: Going into serious operation', NULL, NULL, '2026-01-24 09:18:02'),
(6, 4, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-24 09:26:13'),
(7, 4, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-24 09:26:20'),
(8, 1, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-24 09:26:55'),
(9, 4, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-24 09:27:33'),
(10, 1, 'send_message', 'Sent GYC message', NULL, NULL, '2026-01-24 09:28:08'),
(11, 1, 'add_testimony', 'Added testimony: Im free from spirit of anxiety', NULL, NULL, '2026-01-24 09:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `bible_study_sessions`
--

CREATE TABLE `bible_study_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `book` varchar(50) NOT NULL,
  `chapter` int(11) DEFAULT NULL,
  `verse_from` int(11) DEFAULT NULL,
  `verse_to` int(11) DEFAULT NULL,
  `study_method` enum('inductive','devotional','topical','chapter','verse_by_verse') DEFAULT 'devotional',
  `duration_minutes` int(11) DEFAULT 15,
  `key_verses` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `applications` text DEFAULT NULL,
  `prayer_response` text DEFAULT NULL,
  `session_date` date NOT NULL,
  `insights` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bible_study_sessions`
--

INSERT INTO `bible_study_sessions` (`id`, `user_id`, `program_id`, `book`, `chapter`, `verse_from`, `verse_to`, `study_method`, `duration_minutes`, `key_verses`, `observations`, `applications`, `prayer_response`, `session_date`, `insights`, `created_at`) VALUES
(1, 1, 1, 'Genesis', 1, 1, 12, 'devotional', 15, '', '', '', NULL, '2026-01-22', NULL, '2026-01-22 19:56:28'),
(2, 1, 1, 'Matthew', 1, 1, 6, 'chapter', 15, 'aqa', 'qaq', 'qaaq', NULL, '2026-01-24', NULL, '2026-01-24 08:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `devotionals`
--

CREATE TABLE `devotionals` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `scripture` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `devotion_time` enum('AM','PM') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devotionals`
--

INSERT INTO `devotionals` (`id`, `title`, `scripture`, `content`, `devotion_time`, `created_at`) VALUES
(1, 'what a loving father we have', 'john 3:16', 'dnddvcvcvucc', 'AM', '2026-01-09 14:45:34'),
(2, 'what a loving father we have', 'john 3:16', 'dnddvcvcvucc', 'AM', '2026-01-09 14:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_id` varchar(100) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `message` text DEFAULT NULL,
  `receipt_sent` tinyint(1) DEFAULT 0,
  `receipt_date` date DEFAULT NULL,
  `tax_deductible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `transaction_id` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `user_id`, `campaign_id`, `project_id`, `amount`, `currency`, `payment_method`, `payment_status`, `payment_id`, `is_anonymous`, `message`, `receipt_sent`, `receipt_date`, `tax_deductible`, `created_at`, `status`, `transaction_id`, `phone_number`) VALUES
(6, 1, 5, NULL, 10.00, 'USD', 'ecocash', 'pending', NULL, 0, NULL, 0, NULL, 1, '2026-01-21 18:35:08', 'completed', 'TRX69711C5C9534B', '780737934'),
(7, 1, 2, NULL, 10.00, 'USD', 'bank', 'pending', NULL, 0, NULL, 0, NULL, 1, '2026-01-21 18:35:52', 'completed', 'TRX69711C885D38E', ''),
(8, 1, 5, NULL, 5.00, 'USD', 'ecocash', 'pending', NULL, 0, NULL, 0, NULL, 1, '2026-01-24 09:15:15', 'completed', 'TRX69748DA31CA59', '717210078');

-- --------------------------------------------------------

--
-- Table structure for table `donation_campaigns`
--

CREATE TABLE `donation_campaigns` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `campaign_type` enum('mission','project','emergency','scholarship','general') DEFAULT 'general',
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `organization_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `images` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `donors_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation_campaigns`
--

INSERT INTO `donation_campaigns` (`id`, `title`, `description`, `campaign_type`, `target_amount`, `current_amount`, `currency`, `organization_id`, `project_id`, `start_date`, `end_date`, `status`, `is_featured`, `images`, `video_url`, `donors_count`, `created_at`) VALUES
(1, 'Bible Distribution in Rural Areas', 'Provide Bibles to remote communities with no access to Scripture', 'mission', 5000.00, 1250.50, 'USD', NULL, NULL, '2026-01-14', '2026-04-14', 'active', 1, NULL, NULL, 24, '2026-01-14 15:24:00'),
(2, 'Youth Leadership Conference 2026', 'Annual conference for youth leaders across the nation', 'project', 15000.00, 5240.75, 'USD', NULL, NULL, '2026-01-14', '2026-03-15', 'active', 1, NULL, NULL, 46, '2026-01-14 15:24:00'),
(3, 'Emergency Relief Fund', 'Support families affected by natural disasters', 'emergency', 10000.00, 3210.25, 'USD', NULL, NULL, '2026-01-14', NULL, 'active', 1, NULL, NULL, 67, '2026-01-14 15:24:00'),
(4, 'Theological Scholarship Program', 'Sponsor promising students for Bible school education', 'scholarship', 20000.00, 7890.00, 'USD', NULL, NULL, '2026-01-14', '2026-07-13', 'active', 0, NULL, NULL, 12, '2026-01-14 15:24:00'),
(5, 'General Fund', 'General donation fund for various causes', 'general', 100000.00, 15.00, 'USD', NULL, NULL, NULL, NULL, 'active', 0, NULL, NULL, 2, '2026-01-21 18:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `evangelism_activities`
--

CREATE TABLE `evangelism_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `activity_type` enum('personal_witness','group_outreach','digital_evangelism','prayer_walk','literature_distribution') DEFAULT 'personal_witness',
  `title` varchar(200) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `people_reached` int(11) DEFAULT 0,
  `decisions_made` int(11) DEFAULT 0,
  `followups_needed` int(11) DEFAULT 0,
  `challenges_faced` text DEFAULT NULL,
  `victories_shared` text DEFAULT NULL,
  `testimonies` text DEFAULT NULL,
  `activity_date` date NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `preparation_notes` text DEFAULT NULL,
  `reflection_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evangelism_activities`
--

INSERT INTO `evangelism_activities` (`id`, `user_id`, `program_id`, `activity_type`, `title`, `location`, `people_reached`, `decisions_made`, `followups_needed`, `challenges_faced`, `victories_shared`, `testimonies`, `activity_date`, `duration_minutes`, `preparation_notes`, `reflection_notes`, `created_at`) VALUES
(1, 4, 3, 'digital_evangelism', 'Online sharing', 'Facebook', 5, 1, 0, 'Vv', 'Gg', NULL, '2026-01-24', 60, NULL, NULL, '2026-01-24 18:07:47');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ticket_count` int(11) DEFAULT 1,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `attendance_status` enum('registered','attended','absent') DEFAULT 'registered',
  `checkin_time` datetime DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fundraising_events`
--

CREATE TABLE `fundraising_events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('virtual','in-person','hybrid') DEFAULT 'virtual',
  `organization_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `target_amount` decimal(10,2) DEFAULT 0.00,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `event_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `virtual_link` varchar(255) DEFAULT NULL,
  `ticket_price` decimal(10,2) DEFAULT 0.00,
  `max_attendees` int(11) DEFAULT 0,
  `current_attendees` int(11) DEFAULT 0,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `images` text DEFAULT NULL,
  `speakers` text DEFAULT NULL,
  `sponsors` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fundraising_events`
--

INSERT INTO `fundraising_events` (`id`, `title`, `description`, `event_type`, `organization_id`, `campaign_id`, `target_amount`, `current_amount`, `event_date`, `end_date`, `location`, `virtual_link`, `ticket_price`, `max_attendees`, `current_attendees`, `status`, `images`, `speakers`, `sponsors`, `created_at`) VALUES
(1, 'Annual Benefit Gala', 'Evening of inspiration, music, and fundraising for youth programs', 'in-person', NULL, 2, 50000.00, 12500.00, '2026-02-13 00:00:00', '2026-02-13 00:00:00', 'Grand Hotel, City Center', NULL, 150.00, 300, 85, 'upcoming', NULL, '[\"Pastor John Smith\",\"Rev. Sarah Johnson\",\"Bishop Michael Chen\"]', NULL, '2026-01-14 15:24:01'),
(2, 'Virtual Prayer Marathon', '24-hour prayer and fundraising event for global missions', 'virtual', NULL, 1, 10000.00, 3250.00, '2026-01-21 00:00:00', '2026-01-22 00:00:00', 'Online', NULL, 0.00, 1000, 230, 'upcoming', NULL, '[\"Prayer Warriors Team\",\"Mission Leaders\"]', NULL, '2026-01-14 15:24:01'),
(3, 'Youth Talent Show Fundraiser', 'Showcase youth talents while raising funds for scholarships', 'hybrid', NULL, 4, 5000.00, 1250.00, '2026-01-28 00:00:00', '2026-01-28 00:00:00', 'Community Center & Live Stream', NULL, 10.00, 200, 45, 'upcoming', NULL, '[\"Youth Ministry Leaders\"]', NULL, '2026-01-14 15:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `fybs_achievements`
--

CREATE TABLE `fybs_achievements` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `requirement_type` enum('classes_completed','streak_days','perfect_score','total_time') DEFAULT 'classes_completed',
  `requirement_value` int(11) DEFAULT 1,
  `xp_reward` int(11) DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fybs_achievements`
--

INSERT INTO `fybs_achievements` (`id`, `name`, `description`, `icon`, `requirement_type`, `requirement_value`, `xp_reward`, `is_active`, `created_at`) VALUES
(1, 'First Steps', 'Complete your first Bible study class', 'fa-seedling', 'classes_completed', 1, 25, 1, '2026-01-27 16:34:23'),
(2, 'Learning Streak', 'Complete classes for 3 consecutive days', 'fa-fire', 'streak_days', 3, 50, 1, '2026-01-27 16:34:23'),
(3, 'Beginner Master', 'Complete all beginner level classes', 'fa-trophy', 'classes_completed', 4, 100, 1, '2026-01-27 16:34:23'),
(4, 'Scholar', 'Spend 10+ hours learning', 'fa-book', 'total_time', 600, 150, 1, '2026-01-27 16:34:23'),
(5, 'Perfect Score', 'Score 100% on a class quiz', 'fa-star', 'perfect_score', 1, 75, 1, '2026-01-27 16:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `fybs_classes`
--

CREATE TABLE `fybs_classes` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `content_type` enum('video','reading','audio','study') DEFAULT NULL,
  `content` text DEFAULT NULL,
  `order_index` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fybs_classes`
--

INSERT INTO `fybs_classes` (`id`, `title`, `description`, `level`, `duration_minutes`, `content_type`, `content`, `order_index`) VALUES
(1, 'Introduction to Bible Study', 'Learn basic Bible study methods and tools for effective Scripture reading', 'beginner', 45, 'video', NULL, 1),
(2, 'The Story of Salvation', 'Understanding God\'s plan for humanity from Genesis to Revelation', 'beginner', 60, 'reading', NULL, 2),
(3, 'Who is Jesus?', 'Discover the person and work of Jesus Christ', 'beginner', 50, 'video', NULL, 3),
(4, 'Prayer Basics', 'Learn how to develop a consistent prayer life', 'beginner', 40, 'audio', NULL, 4),
(5, 'The Life of Jesus', 'Exploring teachings and miracles of Christ in depth', 'intermediate', 75, 'video', NULL, 5),
(6, 'Understanding Prayer', 'Deep dive into effective prayer life and spiritual disciplines', 'intermediate', 90, 'audio', NULL, 6),
(7, 'The Holy Spirit', 'Understanding the person and work of the Holy Spirit', 'intermediate', 80, 'study', NULL, 7),
(8, 'Biblical Worldview', 'Developing a Christian perspective on life and culture', 'intermediate', 85, 'video', NULL, 8),
(9, 'Understanding Prophecy', 'Biblical prophecy and end times theology', 'advanced', 120, 'video', NULL, 9),
(10, 'Theology Fundamentals', 'Core Christian doctrines and systematic theology', 'advanced', 150, 'reading', NULL, 10),
(11, 'Biblical Leadership', 'Leadership principles from Scripture for ministry', 'advanced', 110, 'study', NULL, 11),
(12, 'Advanced Biblical Interpretation', 'Hermeneutics and exegetical methods for deep study', 'advanced', 140, 'study', NULL, 12),
(13, 'Introduction to Bible Study', 'Learn basic Bible study methods', 'beginner', 45, 'video', NULL, 1),
(14, 'The Story of Salvation', 'God\'s plan from Genesis to Revelation', 'beginner', 60, 'reading', NULL, 2),
(15, 'Who is Jesus?', 'Discover the person of Jesus Christ', 'beginner', 50, 'video', NULL, 3),
(16, 'Prayer Basics', 'Develop a consistent prayer life', 'beginner', 40, 'audio', NULL, 4),
(17, 'The Life of Jesus', 'Teachings and miracles of Christ', 'intermediate', 75, 'video', NULL, 5),
(18, 'Understanding Prayer', 'Deep dive into prayer life', 'intermediate', 90, 'audio', NULL, 6),
(19, 'The Holy Spirit', 'Person and work of the Holy Spirit', 'intermediate', 80, 'study', NULL, 7),
(20, 'Biblical Worldview', 'Christian perspective on life', 'intermediate', 85, 'video', NULL, 8),
(21, 'Understanding Prophecy', 'Biblical prophecy and end times', 'advanced', 120, 'video', NULL, 9),
(22, 'Theology Fundamentals', 'Core Christian doctrines', 'advanced', 150, 'reading', NULL, 10),
(23, 'Biblical Leadership', 'Leadership principles from Scripture', 'advanced', 110, 'study', NULL, 11),
(24, 'Advanced Biblical Interpretation', 'Hermeneutics and exegetical methods', 'advanced', 140, 'study', NULL, 12);

-- --------------------------------------------------------

--
-- Table structure for table `fybs_progress`
--

CREATE TABLE `fybs_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `time_spent` int(11) DEFAULT 0,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gospel_goals`
--

CREATE TABLE `gospel_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `goal_type` enum('prayer','bible','evangelism','discipleship') DEFAULT 'prayer',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `target_value` int(11) DEFAULT 1,
  `current_value` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('active','completed','abandoned') DEFAULT 'active',
  `is_achieved` tinyint(1) DEFAULT 0,
  `achieved_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gospel_goals`
--

INSERT INTO `gospel_goals` (`id`, `user_id`, `program_id`, `goal_type`, `title`, `description`, `target_value`, `current_value`, `unit`, `deadline`, `status`, `is_achieved`, `achieved_date`, `created_at`) VALUES
(1, 1, 2, 'prayer', 'Pray for 5 minutes daily', NULL, 5, 0, 'minutes', NULL, 'active', 0, NULL, '2026-01-22 19:57:05'),
(2, 1, 2, 'prayer', 'Complete 7 consecutive days', NULL, 7, 0, 'days', NULL, 'active', 0, NULL, '2026-01-22 19:57:05'),
(3, 1, 2, 'prayer', 'Reach 30 days of prayer', NULL, 30, 0, 'days', NULL, 'active', 0, NULL, '2026-01-22 19:57:05'),
(4, 4, 3, 'evangelism', 'Share the gospel with 5 people', NULL, 5, 0, 'people', NULL, 'active', 0, NULL, '2026-01-24 18:07:47');

-- --------------------------------------------------------

--
-- Table structure for table `gospel_programs`
--

CREATE TABLE `gospel_programs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_type` enum('prayer','bible_study','evangelism','discipleship') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency` enum('daily','weekly','biweekly','monthly') DEFAULT 'daily',
  `target_duration_days` int(11) DEFAULT 30,
  `current_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `total_sessions` int(11) DEFAULT 0,
  `completed_sessions` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `target_end_date` date DEFAULT NULL,
  `status` enum('active','paused','completed','abandoned') DEFAULT 'active',
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gospel_programs`
--

INSERT INTO `gospel_programs` (`id`, `user_id`, `program_type`, `title`, `description`, `frequency`, `target_duration_days`, `current_streak`, `longest_streak`, `total_sessions`, `completed_sessions`, `start_date`, `target_end_date`, `status`, `progress_percentage`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 1, 'bible_study', 'Daily Bible Study', 'Regular Bible reading and study', 'daily', 30, 1, 1, 2, 2, '2026-01-22', '2026-02-21', 'active', 6.67, 0, '2026-01-22 19:56:28', '2026-01-24 08:50:01'),
(2, 1, 'prayer', 'rrr', 'mmm', 'daily', 30, 1, 1, 1, 1, '2026-01-22', '2026-02-21', 'active', 3.33, 1, '2026-01-22 19:57:05', '2026-01-24 08:49:30'),
(3, 4, 'evangelism', 'Evangelism Outreach', 'Sharing the gospel with others', 'daily', 30, 1, 1, 1, 1, '2026-01-24', '2026-02-23', 'active', 3.33, 0, '2026-01-24 18:07:47', '2026-01-24 18:07:47');

-- --------------------------------------------------------

--
-- Table structure for table `gospel_resources`
--

CREATE TABLE `gospel_resources` (
  `id` int(11) NOT NULL,
  `resource_type` enum('prayer_guide','bible_study','evangelism_tool','testimony','teaching') DEFAULT 'teaching',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `scripture_reference` varchar(100) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 10,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `tags` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gospel_resources`
--

INSERT INTO `gospel_resources` (`id`, `resource_type`, `title`, `description`, `content`, `scripture_reference`, `author`, `duration_minutes`, `difficulty_level`, `tags`, `views`, `downloads`, `is_featured`, `created_at`) VALUES
(1, 'prayer_guide', 'The Lord\'s Prayer Model', 'Learn to pray using Jesus\' model prayer', 'Our Father in heaven...', 'Matthew 6:9-13', 'Jesus Christ', 15, 'beginner', 'prayer,model,teaching', 0, 0, 0, '2026-01-22 19:41:01'),
(2, 'bible_study', 'How to Study the Bible', 'Basic methods for effective Bible study', '1. Observation\n2. Interpretation\n3. Application', '2 Timothy 3:16-17', 'Paul the Apostle', 20, 'beginner', 'bible study,methods', 0, 0, 0, '2026-01-22 19:41:01'),
(3, 'evangelism_tool', 'The Romans Road', 'Share the gospel using key verses from Romans', 'Romans 3:23, 6:23, 5:8, 10:9-10', 'Romans', 'Paul the Apostle', 10, 'beginner', 'evangelism,gospel,romans', 0, 0, 0, '2026-01-22 19:41:01'),
(4, 'teaching', 'The Great Commission', 'Understanding our call to make disciples', 'Go and make disciples of all nations...', 'Matthew 28:19-20', 'Jesus Christ', 25, 'beginner', 'discipleship,mission,commission', 0, 0, 0, '2026-01-22 19:41:01'),
(5, 'prayer_guide', '30 Days of Prayer for the Lost', 'Daily prayer points for evangelism', 'Day 1: Pray for open hearts\nDay 2: Pray for boldness...', '1 Timothy 2:1-4', NULL, 5, 'beginner', 'prayer,evangelism,30days', 0, 0, 0, '2026-01-22 19:41:01'),
(6, 'prayer_guide', 'Prayer for Spiritual Growth', 'Prayers for personal spiritual development', '1. Lord, increase my faith\n2. Help me love Your Word...', 'Colossians 1:9-12', NULL, 10, 'beginner', 'prayer,growth,spiritual', 0, 0, 0, '2026-01-22 19:41:01'),
(7, 'bible_study', '30-Day New Testament Challenge', 'Read through key NT passages in 30 days', 'Day 1: Matthew 1-4\nDay 2: Matthew 5-7...', 'New Testament', NULL, 15, 'beginner', 'bible,reading,challenge', 0, 0, 0, '2026-01-22 19:41:01'),
(8, 'bible_study', 'Psalms for Every Emotion', 'Find psalms that match your current feelings', 'Joy: Psalm 100\nFear: Psalm 23\nAnger: Psalm 4...', 'Psalms', NULL, 10, 'beginner', 'psalms,emotions,comfort', 0, 0, 0, '2026-01-22 19:41:01');

-- --------------------------------------------------------

--
-- Table structure for table `gyc_messages`
--

CREATE TABLE `gyc_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `room` enum('general','prayer','testimony') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gyc_messages`
--

INSERT INTO `gyc_messages` (`id`, `user_id`, `message`, `room`, `created_at`) VALUES
(7, 2, 'hi', 'general', '2026-01-06 19:50:03'),
(8, 1, 'hi how are you', 'general', '2026-01-06 19:50:28'),
(9, 1, 'zvikufaya here out there', 'general', '2026-01-06 19:54:05'),
(10, 3, 'how are we here', 'general', '2026-01-10 08:36:06'),
(11, 3, 'lord is Good lets connect', 'general', '2026-01-10 08:36:28'),
(12, 1, 'we are good thank the all mighty', 'general', '2026-01-10 08:38:53'),
(13, 1, 'Hie guys', 'general', '2026-01-24 09:11:52'),
(14, 4, 'Hello', 'general', '2026-01-24 09:26:13'),
(15, 4, 'I\'m new here', 'general', '2026-01-24 09:26:20'),
(16, 1, 'So what😅😅', 'general', '2026-01-24 09:26:54'),
(17, 4, 'Hey be nice 🖐️🤣', 'general', '2026-01-24 09:27:32'),
(18, 1, 'Im joking please', 'general', '2026-01-24 09:28:08');

-- --------------------------------------------------------

--
-- Table structure for table `habit_logs`
--

CREATE TABLE `habit_logs` (
  `id` int(11) NOT NULL,
  `habit_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `completed_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_logs`
--

INSERT INTO `habit_logs` (`id`, `habit_id`, `user_id`, `completed_date`, `notes`, `created_at`) VALUES
(1, 11, 1, '2026-01-06', NULL, '2026-01-06 20:41:33'),
(2, 11, 1, '2026-01-07', NULL, '2026-01-07 14:47:50'),
(3, 11, 1, '2026-01-09', NULL, '2026-01-09 19:09:49'),
(4, 12, 1, '2026-01-09', NULL, '2026-01-09 19:32:42'),
(5, 12, 1, '2026-01-10', NULL, '2026-01-10 08:28:51'),
(6, 11, 1, '2026-01-10', NULL, '2026-01-10 08:29:15'),
(7, 12, 1, '2026-01-11', NULL, '2026-01-11 14:42:56'),
(8, 12, 1, '2026-01-21', NULL, '2026-01-21 18:03:17'),
(9, 12, 1, '2026-01-22', NULL, '2026-01-22 20:01:44'),
(10, 12, 1, '2026-01-24', NULL, '2026-01-24 09:36:13'),
(11, 13, 4, '2026-01-24', NULL, '2026-01-24 18:01:13'),
(12, 12, 1, '2026-01-27', NULL, '2026-01-27 16:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `life_business_tips`
--

CREATE TABLE `life_business_tips` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('startup','marketing','finance','productivity','mindset') NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `life_business_tips`
--

INSERT INTO `life_business_tips` (`id`, `title`, `content`, `category`, `author`, `is_featured`, `created_at`) VALUES
(1, 'Start Small, Think Big', 'Begin with a minimum viable product and validate your idea before scaling. Focus on solving one problem exceptionally well.', 'startup', 'Business Mentor', 1, '2026-01-06 19:57:15'),
(2, 'The Power of Networking', 'Build genuine relationships in your industry. Attend events, connect on LinkedIn, and offer value before asking for anything.', 'marketing', 'Network Pro', 1, '2026-01-06 19:57:15'),
(3, 'Financial Discipline for Entrepreneurs', 'Keep personal and business finances separate. Track every expense and maintain a cash reserve for emergencies.', 'finance', 'Financial Expert', 1, '2026-01-06 19:57:15');

-- --------------------------------------------------------

--
-- Table structure for table `life_habits`
--

CREATE TABLE `life_habits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `habit_name` varchar(100) NOT NULL,
  `category` enum('health','productivity','spiritual','business','finance') NOT NULL,
  `frequency` enum('daily','weekly','monthly') DEFAULT 'daily',
  `target_value` int(11) DEFAULT 1,
  `current_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `life_habits`
--

INSERT INTO `life_habits` (`id`, `user_id`, `name`, `habit_name`, `category`, `frequency`, `target_value`, `current_streak`, `longest_streak`, `is_active`, `created_at`) VALUES
(11, 1, '', 'reading', 'productivity', 'daily', 1, 4, 5, 1, '2026-01-06 20:41:09'),
(12, 1, 'reading', '', 'productivity', 'daily', 3, 7, 8, 1, '2026-01-07 18:29:39'),
(13, 4, '', 'Running', 'health', 'daily', 1, 1, 2, 1, '2026-01-24 18:01:06');

-- --------------------------------------------------------

--
-- Table structure for table `life_health_tips`
--

CREATE TABLE `life_health_tips` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('nutrition','exercise','mental','sleep','wellness') NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `life_health_tips`
--

INSERT INTO `life_health_tips` (`id`, `title`, `content`, `category`, `author`, `is_featured`, `created_at`) VALUES
(1, 'Morning Hydration', 'Drink 500ml of water within 30 minutes of waking up. This kickstarts metabolism and improves mental clarity.', 'nutrition', 'Health Coach', 1, '2026-01-06 19:57:15'),
(2, '10-Minute Daily Exercise', 'A short daily workout is better than occasional long sessions. Consistency builds lasting health benefits.', 'exercise', 'Fitness Expert', 1, '2026-01-06 19:57:15'),
(3, 'Digital Detox Before Bed', 'Avoid screens 1 hour before sleep. Read a book or practice meditation for better sleep quality.', 'mental', 'Sleep Specialist', 1, '2026-01-06 19:57:15');

-- --------------------------------------------------------

--
-- Table structure for table `life_knowledge_base`
--

CREATE TABLE `life_knowledge_base` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('business','health','productivity','finance','spiritual') NOT NULL,
  `tags` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `life_knowledge_base`
--

INSERT INTO `life_knowledge_base` (`id`, `title`, `content`, `category`, `tags`, `views`, `created_at`) VALUES
(1, 'The Pomodoro Technique', 'Work for 25 minutes, then take a 5-minute break. After 4 cycles, take a longer 15-30 minute break. This improves focus and prevents burnout.', 'productivity', 'time management,focus,productivity', 0, '2026-01-06 19:57:15'),
(2, 'SMART Goals Framework', 'Specific, Measurable, Achievable, Relevant, Time-bound. This framework helps create clear and actionable goals.', 'business', 'goals,planning,business', 0, '2026-01-06 19:57:15'),
(3, 'Mindful Eating Practices', 'Eat slowly, savor each bite, and listen to your body\'s hunger signals. This improves digestion and prevents overeating.', 'health', 'nutrition,mindfulness,health', 0, '2026-01-06 19:57:15');

-- --------------------------------------------------------

--
-- Table structure for table `life_time_tracking`
--

CREATE TABLE `life_time_tracking` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_name` varchar(100) NOT NULL,
  `category` enum('work','study','exercise','prayer','leisure','business') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `life_time_tracking`
--

INSERT INTO `life_time_tracking` (`id`, `user_id`, `activity_name`, `category`, `start_time`, `end_time`, `duration_minutes`, `notes`, `created_at`) VALUES
(1, 1, 'General Activity', 'work', '2026-01-06 20:20:51', '2026-01-06 20:20:53', 0, '', '2026-01-06 20:20:53'),
(2, 1, 'coding', 'exercise', '2026-01-06 00:00:00', '2026-01-06 23:59:59', 60, '', '2026-01-06 20:42:12'),
(3, 1, 'General Activity', 'work', '2026-01-06 20:42:21', '2026-01-06 20:42:27', 0, '', '2026-01-06 20:42:27');

-- --------------------------------------------------------

--
-- Table structure for table `membership_benefits`
--

CREATE TABLE `membership_benefits` (
  `id` int(11) NOT NULL,
  `tier_id` int(11) DEFAULT NULL,
  `benefit` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_benefits`
--

INSERT INTO `membership_benefits` (`id`, `tier_id`, `benefit`, `description`, `icon`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 2, 'Project Leadership', 'Lead and manage community projects', 'fa-flag', 1, 1, '2026-01-14 15:24:01'),
(2, 2, 'Monthly Reports', 'Detailed reports on project impact', 'fa-chart-line', 2, 1, '2026-01-14 15:24:01'),
(3, 2, 'Priority Notifications', 'Get early access to new opportunities', 'fa-bell', 3, 1, '2026-01-14 15:24:01'),
(4, 3, 'Advanced Analytics', 'Access to detailed project analytics', 'fa-chart-bar', 1, 1, '2026-01-14 15:24:01'),
(5, 3, 'Project Management Tools', 'Tools to manage projects efficiently', 'fa-tasks', 2, 1, '2026-01-14 15:24:01'),
(6, 3, 'Quarterly Meetings', 'Direct meetings with leadership', 'fa-users', 3, 1, '2026-01-14 15:24:01'),
(7, 4, 'Custom Project Creation', 'Create your own funded projects', 'fa-lightbulb', 1, 1, '2026-01-14 15:24:01'),
(8, 4, 'One-on-One Mentoring', 'Personal mentorship from leaders', 'fa-user-graduate', 2, 1, '2026-01-14 15:24:01'),
(9, 4, 'Advisory Board Access', 'Participate in advisory decisions', 'fa-comments', 3, 1, '2026-01-14 15:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `membership_payments`
--

CREATE TABLE `membership_payments` (
  `id` int(11) NOT NULL,
  `membership_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_id` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_tiers`
--

CREATE TABLE `membership_tiers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `monthly_price` decimal(10,2) DEFAULT 0.00,
  `yearly_price` decimal(10,2) DEFAULT 0.00,
  `features` text DEFAULT NULL,
  `max_projects` int(11) DEFAULT 0,
  `max_storage_mb` int(11) DEFAULT 0,
  `priority_support` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_tiers`
--

INSERT INTO `membership_tiers` (`id`, `name`, `description`, `monthly_price`, `yearly_price`, `features`, `max_projects`, `max_storage_mb`, `priority_support`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Free Member', 'Basic access to community and limited projects', 0.00, 0.00, '[\"Access to community forums\",\"Basic project participation\",\"Weekly newsletter\"]', 2, 100, 0, 1, 1, '2026-01-14 15:24:00'),
(2, 'Supporter', 'Support our mission with monthly contributions', 5.00, 50.00, '[\"All Free features\",\"Project leadership\",\"Priority notifications\",\"Monthly reports\"]', 5, 500, 0, 1, 2, '2026-01-14 15:24:00'),
(3, 'Partner', 'Active partnership with enhanced benefits', 25.00, 250.00, '[\"All Supporter features\",\"Project management tools\",\"Advanced analytics\",\"Quarterly meetings\"]', 10, 2000, 1, 1, 3, '2026-01-14 15:24:00'),
(4, 'Strategic Partner', 'Full partnership with all benefits', 100.00, 1000.00, '[\"All Partner features\",\"Custom project creation\",\"One-on-one mentoring\",\"Advisory board access\"]', 50, 10000, 1, 1, 4, '2026-01-14 15:24:00');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('church','ministry','ngo','business') DEFAULT 'ministry',
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `mission_statement` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `total_funds_raised` decimal(10,2) DEFAULT 0.00,
  `active_projects` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partnership_projects`
--

CREATE TABLE `partnership_projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('evangelism','education','community','media','other') DEFAULT 'other',
  `target_amount` decimal(10,2) DEFAULT 0.00,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('planning','active','completed','cancelled') DEFAULT 'planning',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `team_size` int(11) DEFAULT 0,
  `skills_needed` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `volunteers_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prayer_interactions`
--

CREATE TABLE `prayer_interactions` (
  `id` int(11) NOT NULL,
  `prayer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `interaction_type` enum('prayed','encouraged') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prayer_interactions`
--

INSERT INTO `prayer_interactions` (`id`, `prayer_id`, `user_id`, `interaction_type`, `created_at`) VALUES
(1, 1, 2, 'encouraged', '2026-01-06 19:46:06'),
(2, 1, 2, 'prayed', '2026-01-06 19:46:08'),
(3, 1, 1, 'encouraged', '2026-01-09 06:51:23'),
(4, 2, 2, 'prayed', '2026-01-09 07:46:02'),
(5, 2, 2, 'encouraged', '2026-01-09 07:46:04'),
(6, 1, 1, 'prayed', '2026-01-24 09:21:47'),
(7, 3, 1, 'prayed', '2026-01-24 09:39:21'),
(8, 3, 1, 'encouraged', '2026-01-24 09:39:23'),
(9, 3, 4, 'encouraged', '2026-01-24 09:49:59'),
(10, 3, 4, 'prayed', '2026-01-24 09:50:03'),
(11, 2, 4, 'encouraged', '2026-01-24 09:50:05');

-- --------------------------------------------------------

--
-- Table structure for table `prayer_requests`
--

CREATE TABLE `prayer_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `status` enum('pending','answered') DEFAULT 'pending',
  `praise_report` text DEFAULT NULL,
  `prayer_count` int(11) DEFAULT 0,
  `encourage_count` int(11) DEFAULT 0,
  `comment_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prayer_requests`
--

INSERT INTO `prayer_requests` (`id`, `user_id`, `title`, `content`, `is_anonymous`, `status`, `praise_report`, `prayer_count`, `encourage_count`, `comment_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'Prayer for  Exams', 'prayer for exam success', 1, 'pending', NULL, 2, 2, 0, '2026-01-06 19:45:41', '2026-01-24 09:21:47'),
(2, 1, 'prayer for health', 'pray for me going into surgery', 1, 'pending', NULL, 1, 2, 0, '2026-01-09 07:44:52', '2026-01-24 09:50:05'),
(3, 1, 'Going into serious operation', 'Need your prayers .THank you makakoshaa', 0, 'pending', NULL, 2, 2, 0, '2026-01-24 09:18:02', '2026-01-24 09:50:03');

-- --------------------------------------------------------

--
-- Table structure for table `prayer_sessions`
--

CREATE TABLE `prayer_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `prayer_type` enum('personal','intercessory','thanksgiving','petition','worship') DEFAULT 'personal',
  `topic` varchar(200) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 5,
  `scripture_reference` varchar(100) DEFAULT NULL,
  `prayer_points` text DEFAULT NULL,
  `answered_prayers` text DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `mood_before` enum('peaceful','anxious','joyful','burdened','neutral') DEFAULT 'neutral',
  `mood_after` enum('peaceful','anxious','joyful','burdened','neutral') DEFAULT 'neutral',
  `notes` text DEFAULT NULL,
  `is_answered` tinyint(1) DEFAULT 0,
  `answered_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prayer_sessions`
--

INSERT INTO `prayer_sessions` (`id`, `user_id`, `program_id`, `prayer_type`, `topic`, `duration_minutes`, `scripture_reference`, `prayer_points`, `answered_prayers`, `session_date`, `start_time`, `end_time`, `mood_before`, `mood_after`, `notes`, `is_answered`, `answered_date`, `created_at`) VALUES
(1, 1, 2, 'intercessory', 'Grace & Mercy', 10, 'Ephesians 2:8-9', 'ssa', NULL, '2026-01-24', '09:49:30', NULL, 'neutral', 'neutral', 'asas', 0, NULL, '2026-01-24 08:49:30');

-- --------------------------------------------------------

--
-- Table structure for table `project_updates`
--

CREATE TABLE `project_updates` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `update_type` enum('progress','milestone','need','story','general') DEFAULT 'general',
  `images` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_volunteers`
--

CREATE TABLE `project_volunteers` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('leader','coordinator','volunteer','supporter') DEFAULT 'volunteer',
  `status` enum('pending','approved','rejected','withdrawn') DEFAULT 'pending',
  `skills` text DEFAULT NULL,
  `availability` text DEFAULT NULL,
  `commitment_hours` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sermons`
--

CREATE TABLE `sermons` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `preacher` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sermons`
--

INSERT INTO `sermons` (`id`, `title`, `preacher`, `description`, `audio_url`, `created_at`) VALUES
(1, 'lveec', 'ccjddj', 'ththtkvkv', 'https://www.sermonaudio.com/sermons/123251033552142/a?autoplay=1', '2026-01-09 14:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_devotionals`
--

CREATE TABLE `spiritual_devotionals` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `verse` varchar(100) DEFAULT NULL,
  `reflection` text DEFAULT NULL,
  `prayer` text DEFAULT NULL,
  `devotional_type` enum('morning','afternoon','evening') DEFAULT 'morning',
  `scripture_reference` varchar(100) DEFAULT NULL,
  `scripture_text` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 5,
  `author` varchar(100) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_devotionals`
--

INSERT INTO `spiritual_devotionals` (`id`, `title`, `content`, `verse`, `reflection`, `prayer`, `devotional_type`, `scripture_reference`, `scripture_text`, `image_url`, `audio_url`, `duration_minutes`, `author`, `scheduled_date`, `is_published`, `views`, `created_at`, `updated_at`) VALUES
(1, 'The Power of Grace', '', '', '', '', NULL, 'Lamentations 3:22-23', 'The steadfast love of the LORD never ceases; his mercies never come to an end; they are new every morning; great is your faithfulness.', NULL, NULL, 45, '', '0000-00-00', 1, 0, '2026-01-11 17:51:12', '2026-01-24 08:40:24'),
(2, 'Peace That Surpasses Understanding', 'Do not be anxious about anything, but in every situation, by prayer and petition, with thanksgiving, present your requests to God.', 'Philippians 4:6-7', 'Anxiety is natural, but prayer is supernatural. God\'s peace is available when we bring our concerns to Him.', 'Father, I bring my worries to you today. Guard my heart and mind with your perfect peace.', 'evening', 'Philippians 4:6-7', 'Do not be anxious about anything, but in every situation, by prayer and petition, with thanksgiving, present your requests to God. And the peace of God, which transcends all understanding, will guard your hearts and your minds in Christ Jesus.', NULL, NULL, 7, 'Rev. Sarah', '2026-01-11', 1, 1, '2026-01-11 17:51:12', '2026-01-11 18:16:52'),
(3, 'The Power of God\'s Word', 'For the word of God is alive and active. Sharper than any double-edged sword, it penetrates even to dividing soul and spirit.', 'Hebrews 4:12', 'Scripture is not just ancient text - it\'s living, powerful, and relevant to your life today.', 'Holy Spirit, speak to me through your Word today. Let it transform my thoughts and attitudes.', 'morning', 'Hebrews 4:12', 'For the word of God is alive and active. Sharper than any double-edged sword, it penetrates even to dividing soul and spirit, joints and marrow; it judges the thoughts and attitudes of the heart.', NULL, NULL, 6, 'Bishop Michael', '2026-01-12', 1, 1, '2026-01-11 17:51:12', '2026-01-13 11:26:18'),
(4, 'hello', 'ddffdfd', 'john 3:16', 'ccccssa', 'adaddaxx', 'evening', NULL, NULL, NULL, NULL, 5, 'addff', '2026-01-14', 1, 0, '2026-01-14 14:32:33', '2026-01-14 14:32:33');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_devotional_logs`
--

CREATE TABLE `spiritual_devotional_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `devotional_id` int(11) DEFAULT NULL,
  `completed_date` date NOT NULL,
  `reading_time_minutes` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_devotional_logs`
--

INSERT INTO `spiritual_devotional_logs` (`id`, `user_id`, `devotional_id`, `completed_date`, `reading_time_minutes`, `notes`, `is_completed`, `created_at`) VALUES
(1, 1, 2, '2026-01-11', NULL, NULL, 1, '2026-01-11 18:16:52'),
(2, 1, 3, '2026-01-13', NULL, NULL, 1, '2026-01-13 11:26:18');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_following`
--

CREATE TABLE `spiritual_following` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `preacher_id` int(11) DEFAULT NULL,
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_mentoring`
--

CREATE TABLE `spiritual_mentoring` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `mentee_id` int(11) DEFAULT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `topic` varchar(100) DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `frequency` enum('weekly','biweekly','monthly') DEFAULT 'weekly',
  `next_session` datetime DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_mentoring_sessions`
--

CREATE TABLE `spiritual_mentoring_sessions` (
  `id` int(11) NOT NULL,
  `mentoring_id` int(11) DEFAULT NULL,
  `session_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `format` enum('in-person','video','phone','text') DEFAULT 'video',
  `meeting_link` varchar(255) DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `homework` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_mentors`
--

CREATE TABLE `spiritual_mentors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `areas_of_expertise` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `availability` text DEFAULT NULL,
  `max_mentees` int(11) DEFAULT 5,
  `current_mentees` int(11) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `rating` decimal(3,2) DEFAULT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_plan_daily_readings`
--

CREATE TABLE `spiritual_plan_daily_readings` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `day_number` int(11) NOT NULL,
  `scripture_reference` varchar(100) NOT NULL,
  `reflection_question` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_plan_progress`
--

CREATE TABLE `spiritual_plan_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `current_day` int(11) DEFAULT 1,
  `total_days_completed` int(11) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_plan_progress`
--

INSERT INTO `spiritual_plan_progress` (`id`, `user_id`, `plan_id`, `current_day`, `total_days_completed`, `is_completed`, `start_date`, `end_date`, `last_activity`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 0, 0, '2026-01-13', NULL, '2026-01-13 11:26:32', '2026-01-13 11:26:32', '2026-01-13 11:26:32'),
(2, 1, 2, 1, 0, 0, '2026-01-14', NULL, '2026-01-14 14:39:41', '2026-01-14 14:39:41', '2026-01-14 14:39:41');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_preachers`
--

CREATE TABLE `spiritual_preachers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `social_media` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_recommended` tinyint(1) DEFAULT 0,
  `sermon_count` int(11) DEFAULT 0,
  `followers_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_preachers`
--

INSERT INTO `spiritual_preachers` (`id`, `name`, `title`, `bio`, `specialization`, `profile_image`, `website`, `social_media`, `is_active`, `is_recommended`, `sermon_count`, `followers_count`, `created_at`) VALUES
(1, 'Pastor John Smith', 'Senior Pastor', '20+ years in ministry, author of 5 books on Christian living', 'Grace & Mercy, Faith', NULL, 'https://pastorjohn.com', '{\"twitter\": \"@pastorjohn\", \"facebook\": \"pastorjohnsmith\"}', 1, 1, 45, 12500, '2026-01-11 17:51:12'),
(2, 'Rev. Sarah Johnson', 'Youth Pastor', 'Passionate about youth ministry and spiritual growth', 'Prayer, Youth Ministry', NULL, 'https://revsarah.org', '{\"instagram\": \"@revsarah\", \"youtube\": \"RevSarahMinistries\"}', 1, 1, 32, 8900, '2026-01-11 17:51:12'),
(3, 'Bishop Michael Chen', 'Bishop', 'International speaker and church planter', 'Kingdom Living, Leadership', NULL, 'https://bishopchen.org', '{\"facebook\": \"bishopmchen\", \"linkedin\": \"michaelchen\"}', 1, 1, 67, 23400, '2026-01-11 17:51:12'),
(4, 'Dr. Elizabeth Williams', 'Professor', 'Theology professor and author of several scholarly works', 'Spiritual Gifts, Theology', NULL, 'https://drwilliams.edu', '{\"twitter\": \"@drliz\", \"website\": \"drwilliams.edu\"}', 1, 1, 28, 15600, '2026-01-11 17:51:12'),
(5, 'Tafadzwa Chavarika', 'Pastor', 'ssdsdds', 'preaching', NULL, '', 'tc', 1, 1, 0, 0, '2026-01-14 14:37:51');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_reading_plans`
--

CREATE TABLE `spiritual_reading_plans` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_days` int(11) DEFAULT 30,
  `bible_books` text DEFAULT NULL,
  `daily_verses` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `participants_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_reading_plans`
--

INSERT INTO `spiritual_reading_plans` (`id`, `title`, `description`, `duration_days`, `bible_books`, `daily_verses`, `is_featured`, `participants_count`, `created_at`) VALUES
(1, '30 Days in Psalms', 'A journey through the Psalms for comfort and strength', 30, 'Psalms', NULL, 1, 451, '2026-01-11 17:51:13'),
(2, 'New Testament in 90 Days', 'Read through the entire New Testament', 90, 'Matthew,Mark,Luke,John,Acts,Romans,1 Corinthians,2 Corinthians,Galatians,Ephesians,Philippians,Colossians,1 Thessalonians,2 Thessalonians,1 Timothy,2 Timothy,Titus,Philemon,Hebrews,James,1 Peter,2 Peter,1 John,2 John,3 John,Jude,Revelation', NULL, 1, 321, '2026-01-11 17:51:13'),
(3, 'Proverbs for Daily Wisdom', 'One chapter of Proverbs each day', 31, 'Proverbs', NULL, 0, 210, '2026-01-11 17:51:13');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_sermons`
--

CREATE TABLE `spiritual_sermons` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `preacher_id` int(11) DEFAULT NULL,
  `preacher_name` varchar(100) DEFAULT NULL,
  `scripture_reference` varchar(100) DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `media_type` enum('video','audio','text') DEFAULT 'video',
  `video_url` varchar(255) DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `series_name` varchar(100) DEFAULT NULL,
  `series_part` int(11) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `published_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_sermons`
--

INSERT INTO `spiritual_sermons` (`id`, `title`, `description`, `preacher_id`, `preacher_name`, `scripture_reference`, `topic`, `media_type`, `video_url`, `audio_url`, `thumbnail_url`, `duration_minutes`, `series_name`, `series_part`, `views`, `likes`, `is_featured`, `published_date`, `created_at`) VALUES
(1, 'The Power of Grace', 'Understanding God\'s unmerited favor in our daily lives', 1, 'Pastor John Smith', 'Ephesians 2:8-9', 'Grace & Mercy', 'video', 'https://example.com/video1.mp4', 'https://example.com/audio1.mp3', NULL, 45, 'Foundations of Faith', 1, 1250, 89, 1, '2026-01-11', '2026-01-11 17:51:12'),
(2, 'Developing a Strong Prayer Life', 'Practical steps to deepen your prayer journey', 2, 'Rev. Sarah Johnson', '1 Thessalonians 5:16-18', 'Prayer', 'audio', NULL, 'https://example.com/audio2.mp3', NULL, 38, 'Spiritual Disciplines', 3, 890, 45, 1, '2026-01-11', '2026-01-11 17:51:12'),
(3, 'Living as Kingdom Citizens', 'How to live out your faith in everyday situations', 3, 'Bishop Michael Chen', 'Matthew 6:33', 'Kingdom Living', 'video', 'https://example.com/video3.mp4', 'https://example.com/audio3.mp3', NULL, 52, NULL, NULL, 2100, 120, 0, '2026-01-09', '2026-01-11 17:51:12');

-- --------------------------------------------------------

--
-- Table structure for table `spiritual_user_stats`
--

CREATE TABLE `spiritual_user_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `streak_days` int(11) DEFAULT 0,
  `total_devotionals` int(11) DEFAULT 0,
  `total_sermons_watched` int(11) DEFAULT 0,
  `total_prayer_time` int(11) DEFAULT 0,
  `scriptures_read` int(11) DEFAULT 0,
  `mentoring_sessions` int(11) DEFAULT 0,
  `last_active_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spiritual_user_stats`
--

INSERT INTO `spiritual_user_stats` (`id`, `user_id`, `streak_days`, `total_devotionals`, `total_sermons_watched`, `total_prayer_time`, `scriptures_read`, `mentoring_sessions`, `last_active_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 0, 0, 2, 0, '2026-01-13', '2026-01-11 18:16:52', '2026-01-13 11:26:18');

-- --------------------------------------------------------

--
-- Table structure for table `testimonies`
--

CREATE TABLE `testimonies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('healing','provision','deliverance','guidance','other') DEFAULT 'other',
  `like_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonies`
--

INSERT INTO `testimonies` (`id`, `user_id`, `title`, `content`, `category`, `like_count`, `created_at`) VALUES
(1, 1, 'Im free from spirit of anxiety', 'God freed me from spirit of anxiety', 'deliverance', 2, '2026-01-24 09:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `testimony_likes`
--

CREATE TABLE `testimony_likes` (
  `id` int(11) NOT NULL,
  `testimony_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimony_likes`
--

INSERT INTO `testimony_likes` (`id`, `testimony_id`, `user_id`, `created_at`) VALUES
(1, 1, 1, '2026-01-24 09:49:02'),
(2, 1, 4, '2026-01-24 09:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `donation_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('admin','editor','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `created_at`, `role`) VALUES
(1, ' tino butsa', 'tk@gmail.com', '$2y$10$tq7Xp0p4DsGkJNtLFsZCu.j9Ih3laLI8eqVN15eXfMBOAsGKM4eZC', '2026-01-06 18:14:40', 'user'),
(2, ' delicate sibanda', 'deli@gmail.com', '$2y$10$iPsWF9Yt20VOVUyYcACR9euUkHNNOkuZUdDmmiZWPgM8DCrSKDhZa', '2026-01-06 19:17:10', 'admin'),
(3, 'Fay Butsa', 'John@gmail.com', '$2y$10$MiYv0N8LzDoaRdHfIn8gXeD7FhBWx9.MUDR1oUmkwj639wETANuse', '2026-01-10 08:35:26', 'user'),
(4, 'Cathy Butsa', 'cathy@gmail.com', '$2y$10$aX3yqG.ErQC.6Mjr2rwK8.TTUR5GZ.eGudHXAX5wy8kEbiDwOrwDG', '2026-01-24 09:25:13', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `user_donation_stats`
--

CREATE TABLE `user_donation_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_donated` decimal(10,2) DEFAULT 0.00,
  `total_campaigns` int(11) DEFAULT 0,
  `last_donation_date` date DEFAULT NULL,
  `largest_donation` decimal(10,2) DEFAULT 0.00,
  `recurring_donations` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_memberships`
--

CREATE TABLE `user_memberships` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tier_id` int(11) DEFAULT NULL,
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `status` enum('active','pending','cancelled','expired') DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_memberships`
--

INSERT INTO `user_memberships` (`id`, `user_id`, `tier_id`, `billing_cycle`, `status`, `start_date`, `end_date`, `renewal_date`, `auto_renew`, `payment_method`, `last_payment_date`, `next_payment_date`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'monthly', 'active', '2026-01-14', NULL, '2026-02-13', 1, NULL, NULL, NULL, '2026-01-14 15:39:08', '2026-01-14 15:39:08');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addiction_breaker_programs`
--
ALTER TABLE `addiction_breaker_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_program` (`user_id`,`program_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_type` (`program_type`),
  ADD KEY `severity_level` (`severity_level`),
  ADD KEY `current_stage` (`current_stage`);

--
-- Indexes for table `addiction_coping_strategies`
--
ALTER TABLE `addiction_coping_strategies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_type` (`program_type`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `addiction_daily_checkins`
--
ALTER TABLE `addiction_daily_checkins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `daily_program_checkin` (`program_id`,`checkin_date`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `checkin_date` (`checkin_date`),
  ADD KEY `substance_free` (`substance_free`),
  ADD KEY `relapse_occurred` (`relapse_occurred`);

--
-- Indexes for table `addiction_educational_content`
--
ALTER TABLE `addiction_educational_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_type` (`program_type`),
  ADD KEY `content_type` (`content_type`);

--
-- Indexes for table `addiction_motivational_quotes`
--
ALTER TABLE `addiction_motivational_quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_type` (`program_type`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `addiction_progress_milestones`
--
ALTER TABLE `addiction_progress_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `milestone_type` (`milestone_type`),
  ADD KEY `is_achieved` (`is_achieved`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `bible_study_sessions`
--
ALTER TABLE `bible_study_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `session_date` (`session_date`);

--
-- Indexes for table `devotionals`
--
ALTER TABLE `devotionals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `payment_status` (`payment_status`);

--
-- Indexes for table `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_type` (`campaign_type`),
  ADD KEY `status` (`status`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `evangelism_activities`
--
ALTER TABLE `evangelism_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `activity_date` (`activity_date`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_user` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `fundraising_events`
--
ALTER TABLE `fundraising_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `status` (`status`),
  ADD KEY `campaign_id` (`campaign_id`);

--
-- Indexes for table `fybs_achievements`
--
ALTER TABLE `fybs_achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fybs_classes`
--
ALTER TABLE `fybs_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fybs_progress`
--
ALTER TABLE `fybs_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `gospel_goals`
--
ALTER TABLE `gospel_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `goal_type` (`goal_type`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `gospel_programs`
--
ALTER TABLE `gospel_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_type` (`program_type`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `gospel_resources`
--
ALTER TABLE `gospel_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_type` (`resource_type`),
  ADD KEY `is_featured` (`is_featured`);

--
-- Indexes for table `gyc_messages`
--
ALTER TABLE `gyc_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `habit_logs`
--
ALTER TABLE `habit_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_habit_log` (`habit_id`,`completed_date`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `life_business_tips`
--
ALTER TABLE `life_business_tips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `life_habits`
--
ALTER TABLE `life_habits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `life_health_tips`
--
ALTER TABLE `life_health_tips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `life_knowledge_base`
--
ALTER TABLE `life_knowledge_base`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `life_time_tracking`
--
ALTER TABLE `life_time_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `membership_benefits`
--
ALTER TABLE `membership_benefits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tier_id` (`tier_id`);

--
-- Indexes for table `membership_payments`
--
ALTER TABLE `membership_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `membership_id` (`membership_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_status` (`payment_status`);

--
-- Indexes for table `membership_tiers`
--
ALTER TABLE `membership_tiers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partnership_projects`
--
ALTER TABLE `partnership_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `is_featured` (`is_featured`);

--
-- Indexes for table `prayer_interactions`
--
ALTER TABLE `prayer_interactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interaction` (`prayer_id`,`user_id`,`interaction_type`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prayer_requests`
--
ALTER TABLE `prayer_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prayer_sessions`
--
ALTER TABLE `prayer_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `session_date` (`session_date`);

--
-- Indexes for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `project_volunteers`
--
ALTER TABLE `project_volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_user` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sermons`
--
ALTER TABLE `sermons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spiritual_devotionals`
--
ALTER TABLE `spiritual_devotionals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheduled_date` (`scheduled_date`),
  ADD KEY `devotional_type` (`devotional_type`),
  ADD KEY `is_published` (`is_published`);

--
-- Indexes for table `spiritual_devotional_logs`
--
ALTER TABLE `spiritual_devotional_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_devotional_log` (`user_id`,`devotional_id`,`completed_date`),
  ADD KEY `devotional_id` (`devotional_id`);

--
-- Indexes for table `spiritual_following`
--
ALTER TABLE `spiritual_following`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_following` (`user_id`,`preacher_id`),
  ADD KEY `preacher_id` (`preacher_id`);

--
-- Indexes for table `spiritual_mentoring`
--
ALTER TABLE `spiritual_mentoring`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `spiritual_mentoring_sessions`
--
ALTER TABLE `spiritual_mentoring_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentoring_id` (`mentoring_id`),
  ADD KEY `session_date` (`session_date`);

--
-- Indexes for table `spiritual_mentors`
--
ALTER TABLE `spiritual_mentors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `is_available` (`is_available`);

--
-- Indexes for table `spiritual_plan_daily_readings`
--
ALTER TABLE `spiritual_plan_daily_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `day_number` (`day_number`);

--
-- Indexes for table `spiritual_plan_progress`
--
ALTER TABLE `spiritual_plan_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_plan_progress` (`user_id`,`plan_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `spiritual_preachers`
--
ALTER TABLE `spiritual_preachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_recommended` (`is_recommended`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `spiritual_reading_plans`
--
ALTER TABLE `spiritual_reading_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_featured` (`is_featured`);

--
-- Indexes for table `spiritual_sermons`
--
ALTER TABLE `spiritual_sermons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `preacher_id` (`preacher_id`),
  ADD KEY `media_type` (`media_type`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `published_date` (`published_date`);

--
-- Indexes for table `spiritual_user_stats`
--
ALTER TABLE `spiritual_user_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `last_active_date` (`last_active_date`);

--
-- Indexes for table `testimonies`
--
ALTER TABLE `testimonies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `testimony_likes`
--
ALTER TABLE `testimony_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`testimony_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_donation_stats`
--
ALTER TABLE `user_donation_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_memberships`
--
ALTER TABLE `user_memberships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `tier_id` (`tier_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addiction_breaker_programs`
--
ALTER TABLE `addiction_breaker_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `addiction_coping_strategies`
--
ALTER TABLE `addiction_coping_strategies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `addiction_daily_checkins`
--
ALTER TABLE `addiction_daily_checkins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `addiction_educational_content`
--
ALTER TABLE `addiction_educational_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `addiction_motivational_quotes`
--
ALTER TABLE `addiction_motivational_quotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `addiction_progress_milestones`
--
ALTER TABLE `addiction_progress_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bible_study_sessions`
--
ALTER TABLE `bible_study_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `devotionals`
--
ALTER TABLE `devotionals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `evangelism_activities`
--
ALTER TABLE `evangelism_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fundraising_events`
--
ALTER TABLE `fundraising_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fybs_achievements`
--
ALTER TABLE `fybs_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fybs_classes`
--
ALTER TABLE `fybs_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `fybs_progress`
--
ALTER TABLE `fybs_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gospel_goals`
--
ALTER TABLE `gospel_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gospel_programs`
--
ALTER TABLE `gospel_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gospel_resources`
--
ALTER TABLE `gospel_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gyc_messages`
--
ALTER TABLE `gyc_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `habit_logs`
--
ALTER TABLE `habit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `life_business_tips`
--
ALTER TABLE `life_business_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `life_habits`
--
ALTER TABLE `life_habits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `life_health_tips`
--
ALTER TABLE `life_health_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `life_knowledge_base`
--
ALTER TABLE `life_knowledge_base`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `life_time_tracking`
--
ALTER TABLE `life_time_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `membership_benefits`
--
ALTER TABLE `membership_benefits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `membership_payments`
--
ALTER TABLE `membership_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_tiers`
--
ALTER TABLE `membership_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partnership_projects`
--
ALTER TABLE `partnership_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prayer_interactions`
--
ALTER TABLE `prayer_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `prayer_requests`
--
ALTER TABLE `prayer_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prayer_sessions`
--
ALTER TABLE `prayer_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_updates`
--
ALTER TABLE `project_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_volunteers`
--
ALTER TABLE `project_volunteers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sermons`
--
ALTER TABLE `sermons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `spiritual_devotionals`
--
ALTER TABLE `spiritual_devotionals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `spiritual_devotional_logs`
--
ALTER TABLE `spiritual_devotional_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `spiritual_following`
--
ALTER TABLE `spiritual_following`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `spiritual_mentoring`
--
ALTER TABLE `spiritual_mentoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spiritual_mentoring_sessions`
--
ALTER TABLE `spiritual_mentoring_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spiritual_mentors`
--
ALTER TABLE `spiritual_mentors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spiritual_plan_daily_readings`
--
ALTER TABLE `spiritual_plan_daily_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spiritual_plan_progress`
--
ALTER TABLE `spiritual_plan_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `spiritual_preachers`
--
ALTER TABLE `spiritual_preachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `spiritual_reading_plans`
--
ALTER TABLE `spiritual_reading_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `spiritual_sermons`
--
ALTER TABLE `spiritual_sermons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `spiritual_user_stats`
--
ALTER TABLE `spiritual_user_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `testimonies`
--
ALTER TABLE `testimonies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `testimony_likes`
--
ALTER TABLE `testimony_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_donation_stats`
--
ALTER TABLE `user_donation_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_memberships`
--
ALTER TABLE `user_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addiction_breaker_programs`
--
ALTER TABLE `addiction_breaker_programs`
  ADD CONSTRAINT `addiction_breaker_programs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `addiction_daily_checkins`
--
ALTER TABLE `addiction_daily_checkins`
  ADD CONSTRAINT `addiction_daily_checkins_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `addiction_breaker_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `addiction_progress_milestones`
--
ALTER TABLE `addiction_progress_milestones`
  ADD CONSTRAINT `addiction_progress_milestones_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `addiction_breaker_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `bible_study_sessions`
--
ALTER TABLE `bible_study_sessions`
  ADD CONSTRAINT `bible_study_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bible_study_sessions_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `gospel_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  ADD CONSTRAINT `donation_campaigns_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `partnership_projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `evangelism_activities`
--
ALTER TABLE `evangelism_activities`
  ADD CONSTRAINT `evangelism_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evangelism_activities_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `gospel_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `fundraising_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fundraising_events`
--
ALTER TABLE `fundraising_events`
  ADD CONSTRAINT `fundraising_events_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fybs_progress`
--
ALTER TABLE `fybs_progress`
  ADD CONSTRAINT `fybs_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fybs_progress_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `fybs_classes` (`id`);

--
-- Constraints for table `gospel_goals`
--
ALTER TABLE `gospel_goals`
  ADD CONSTRAINT `gospel_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gospel_goals_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `gospel_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gospel_programs`
--
ALTER TABLE `gospel_programs`
  ADD CONSTRAINT `gospel_programs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gyc_messages`
--
ALTER TABLE `gyc_messages`
  ADD CONSTRAINT `gyc_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `habit_logs`
--
ALTER TABLE `habit_logs`
  ADD CONSTRAINT `habit_logs_ibfk_1` FOREIGN KEY (`habit_id`) REFERENCES `life_habits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `habit_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `life_habits`
--
ALTER TABLE `life_habits`
  ADD CONSTRAINT `life_habits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `life_time_tracking`
--
ALTER TABLE `life_time_tracking`
  ADD CONSTRAINT `life_time_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `membership_benefits`
--
ALTER TABLE `membership_benefits`
  ADD CONSTRAINT `membership_benefits_ibfk_1` FOREIGN KEY (`tier_id`) REFERENCES `membership_tiers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `membership_payments`
--
ALTER TABLE `membership_payments`
  ADD CONSTRAINT `membership_payments_ibfk_1` FOREIGN KEY (`membership_id`) REFERENCES `user_memberships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membership_payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partnership_projects`
--
ALTER TABLE `partnership_projects`
  ADD CONSTRAINT `partnership_projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prayer_interactions`
--
ALTER TABLE `prayer_interactions`
  ADD CONSTRAINT `prayer_interactions_ibfk_1` FOREIGN KEY (`prayer_id`) REFERENCES `prayer_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prayer_interactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prayer_requests`
--
ALTER TABLE `prayer_requests`
  ADD CONSTRAINT `prayer_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prayer_sessions`
--
ALTER TABLE `prayer_sessions`
  ADD CONSTRAINT `prayer_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prayer_sessions_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `gospel_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD CONSTRAINT `project_updates_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `partnership_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_updates_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_volunteers`
--
ALTER TABLE `project_volunteers`
  ADD CONSTRAINT `project_volunteers_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `partnership_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_volunteers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_devotional_logs`
--
ALTER TABLE `spiritual_devotional_logs`
  ADD CONSTRAINT `spiritual_devotional_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spiritual_devotional_logs_ibfk_2` FOREIGN KEY (`devotional_id`) REFERENCES `spiritual_devotionals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_following`
--
ALTER TABLE `spiritual_following`
  ADD CONSTRAINT `spiritual_following_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spiritual_following_ibfk_2` FOREIGN KEY (`preacher_id`) REFERENCES `spiritual_preachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_mentoring`
--
ALTER TABLE `spiritual_mentoring`
  ADD CONSTRAINT `spiritual_mentoring_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `spiritual_mentors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spiritual_mentoring_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_mentoring_sessions`
--
ALTER TABLE `spiritual_mentoring_sessions`
  ADD CONSTRAINT `spiritual_mentoring_sessions_ibfk_1` FOREIGN KEY (`mentoring_id`) REFERENCES `spiritual_mentoring` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_mentors`
--
ALTER TABLE `spiritual_mentors`
  ADD CONSTRAINT `spiritual_mentors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_plan_daily_readings`
--
ALTER TABLE `spiritual_plan_daily_readings`
  ADD CONSTRAINT `spiritual_plan_daily_readings_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `spiritual_reading_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_plan_progress`
--
ALTER TABLE `spiritual_plan_progress`
  ADD CONSTRAINT `spiritual_plan_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spiritual_plan_progress_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `spiritual_reading_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spiritual_sermons`
--
ALTER TABLE `spiritual_sermons`
  ADD CONSTRAINT `spiritual_sermons_ibfk_1` FOREIGN KEY (`preacher_id`) REFERENCES `spiritual_preachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `spiritual_user_stats`
--
ALTER TABLE `spiritual_user_stats`
  ADD CONSTRAINT `spiritual_user_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `testimonies`
--
ALTER TABLE `testimonies`
  ADD CONSTRAINT `testimonies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `testimony_likes`
--
ALTER TABLE `testimony_likes`
  ADD CONSTRAINT `testimony_likes_ibfk_1` FOREIGN KEY (`testimony_id`) REFERENCES `testimonies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `testimony_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_donation_stats`
--
ALTER TABLE `user_donation_stats`
  ADD CONSTRAINT `user_donation_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_memberships`
--
ALTER TABLE `user_memberships`
  ADD CONSTRAINT `user_memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_memberships_ibfk_2` FOREIGN KEY (`tier_id`) REFERENCES `membership_tiers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
