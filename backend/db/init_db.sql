-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 05, 2025 at 08:47 PM
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
-- Database: `event_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int(11) NOT NULL,
  `account_type` enum('student','organization','admin') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `account_type`, `reference_id`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'student', 1, '2025-06-04 14:31:50', '2025-05-26 21:04:50', '2025-06-04 14:31:50'),
(2, 'student', 2, '2025-05-29 19:11:12', '2025-05-26 21:04:50', '2025-05-29 19:11:12'),
(3, 'student', 3, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(4, 'organization', 1, '2025-06-02 15:23:12', '2025-05-26 21:04:50', '2025-06-02 15:23:12'),
(5, 'organization', 2, '2025-06-04 10:45:53', '2025-05-26 21:04:50', '2025-06-04 10:45:53'),
(6, 'organization', 3, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(7, 'admin', 1, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(8, 'student', 4, '2025-05-31 18:27:05', '2025-05-31 18:26:49', '2025-05-31 18:27:05'),
(9, 'organization', 4, '2025-05-31 20:42:33', '2025-05-31 18:30:03', '2025-05-31 20:42:33');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@iteamuniversity.com', '$2y$10$n8C65K9BPblL1rMAghbDxuQsolBJRwGiB29KFH5t6OvIzhFsww7nq', '2025-05-26 21:04:50', '2025-05-26 23:22:27');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `event_type` enum('workshop','conference','fair','webinar') NOT NULL,
  `max_capacity` int(11) DEFAULT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `requires_approval` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `start_date`, `end_date`, `location`, `event_type`, `max_capacity`, `organizer_id`, `thumbnail_url`, `created_at`, `updated_at`, `requires_approval`) VALUES
(1, 'Web Dev Workshop', 'Intro to HTML, CSS, JS', '2025-05-15 09:00:00', '2025-05-15 12:00:00', 'Room A1', 'workshop', 25, 1, NULL, '2025-05-26 21:04:50', '2025-05-31 22:49:34', 1),
(2, 'Cybersecurity Webinar', 'Latest attack vectors & mitigations', '2025-05-15 10:00:00', '2025-05-15 11:30:00', 'Online', 'webinar', 100, 2, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50', 0),
(3, 'Tech Career Fair', 'Meet companies hiring in tech', '2025-05-15 13:00:00', '2025-05-15 17:00:00', 'Main Hall', 'fair', 300, 3, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50', 0),
(4, 'AI Conference', 'How AI is changing the world', '2025-05-15 14:00:00', '2025-05-15 18:00:00', 'Auditorium', 'conference', 200, 2, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50', 0),
(5, 'Cloud Computing Worksho', 'AWS & Azure deep dive', '2025-06-10 09:00:00', '2025-06-10 12:00:00', 'Room B2', 'workshop', 40, 1, NULL, '2025-05-26 21:04:50', '2025-05-27 01:27:36', 0),
(6, 'Data Science Webinar', 'Intro to data analysis and ML', '2024-12-01 15:00:00', '2024-12-01 17:00:00', 'Online', 'webinar', 150, 2, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50', 0),
(7, 'Intro to DevOps', 'Workshop on CI/CD pipelines', '2025-05-15 10:00:00', '2025-05-15 12:00:00', 'Room A1', 'workshop', 30, 1, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50', 0),
(8, 'Machine Learning Conference', 'ML in healthcare', '2025-05-15 10:30:00', '2025-05-15 13:00:00', 'Room A1', 'conference', 100, 2, NULL, '2025-05-26 21:04:50', '2025-05-26 21:04:50', 0),
(11, 'myev', NULL, '2025-05-29 08:00:00', '2025-05-29 09:54:00', 'lafayatte', 'conference', 50, 1, NULL, '2025-05-27 01:27:22', '2025-05-29 19:17:08', 0),
(15, 'mosalem', NULL, '2025-05-30 08:00:00', '2025-05-30 09:00:00', 'Room B2', 'webinar', 10, 1, NULL, '2025-05-27 01:39:16', '2025-05-29 19:17:13', 0),
(16, 'errr', NULL, '2025-06-26 08:00:00', '2025-06-26 09:00:00', 'lafayatte', 'fair', 30, 1, NULL, '2025-05-27 01:40:23', '2025-05-29 19:17:33', 0),
(17, 'hi', 'gooooo', '2025-06-10 12:00:00', '2025-06-19 12:00:00', 'achram', 'conference', 100, 1, NULL, '2025-05-29 15:15:28', '2025-06-02 02:36:36', 0),
(18, 'Frontend Fundamentals', 'Learn HTML, CSS, and JavaScript basics', '2025-06-10 09:00:00', '2025-06-10 12:00:00', 'Room A1', 'workshop', 25, 1, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(19, 'Defensive Security Tactics', 'Overview of current cyber threats and defenses', '2025-06-10 13:00:00', '2025-06-10 14:30:00', 'Online', 'webinar', 100, 2, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(20, 'Tech Opportunities Expo', 'Network with recruiters and explore job offers', '2025-06-11 10:00:00', '2025-06-11 17:00:00', 'Main Hall', 'fair', 300, 3, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(21, 'AI Horizons Summit', 'Exploring the impact of AI on industry and society', '2025-06-12 09:00:00', '2025-06-12 18:00:00', 'Auditorium', 'conference', 200, 2, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(22, 'Next-Gen Cloud Architectures', 'Advanced AWS and Azure solutions', '2025-07-05 09:00:00', '2025-07-05 12:00:00', 'Room B2', 'workshop', 40, 1, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(23, 'Data Insights Online', 'Data analysis and machine learning primer', '2025-06-01 15:00:00', '2025-06-01 17:00:00', 'Online', 'webinar', 150, 2, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(24, 'CI/CD Essentials', 'Hands-on DevOps with Jenkins and GitHub Actions', '2025-08-01 10:00:00', '2025-08-01 12:00:00', 'Room A1', 'workshop', 30, 1, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(25, 'Smart Health with ML', 'Applications of machine learning in medicine', '2025-08-01 10:30:00', '2025-08-01 13:00:00', 'Room A1', 'conference', 100, 2, NULL, '2025-05-31 19:06:20', '2025-05-31 19:06:20', 0),
(27, '3221', '                                    3244r', '2025-06-11 12:00:00', '2025-06-19 12:00:00', 'fffff', 'workshop', 33232, 1, NULL, '2025-06-02 02:31:30', '2025-06-02 02:31:30', 0),
(28, 'koool', '            uuuuu                        ', '2025-06-19 12:00:00', '2025-06-19 14:00:00', 'lafayatte', 'workshop', 45, 2, NULL, '2025-06-04 13:32:52', '2025-06-04 13:32:52', 0);

-- --------------------------------------------------------

--
-- Table structure for table `event_gallery`
--

CREATE TABLE `event_gallery` (
  `image_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `caption` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_gallery`
--

INSERT INTO `event_gallery` (`image_id`, `event_id`, `image_url`, `upload_date`, `caption`, `created_at`, `updated_at`) VALUES
(1, 1, 'assets/images/gallery/gallery-1.jpeg', '2025-05-26 21:04:50', 'Participants working on coding exercises', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(2, 1, 'assets/images/gallery/gallery-2.jpeg', '2025-05-26 21:04:50', 'Group discussion on web development best practices', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(3, 1, 'assets/images/gallery/gallery-3.jpeg', '2025-05-26 21:04:50', 'Interactive session with live coding', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(4, 2, 'assets/images/gallery/gallery-4.jpeg', '2025-05-26 21:04:50', 'Opening keynote speech', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(5, 2, 'assets/images/gallery/gallery-5.jpeg', '2025-05-26 21:04:50', 'Panel discussion with industry leaders', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(6, 2, 'assets/images/gallery/gallery-6.jpeg', '2025-05-26 21:04:50', 'Audience at the main auditorium', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(7, 3, 'assets/images/gallery/gallery-7.jpeg', '2025-05-26 21:04:50', 'Teams collaborating during the hackathon', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(8, 3, 'assets/images/gallery/gallery-8.jpeg', '2025-05-26 21:04:50', 'Pitching ideas to the judges', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(9, 3, 'assets/images/gallery/gallery-9.jpeg', '2025-05-26 21:04:50', 'Winning team receiving award', '2025-05-26 21:04:50', '2025-05-26 21:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `student_id`, `registration_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 1, '2025-05-26 21:04:50', 'confirmed', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(4, 1, 2, '2025-05-26 21:04:50', 'confirmed', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(5, 2, 2, '2025-05-26 21:04:50', 'confirmed', '2025-05-26 21:04:50', '2025-05-29 19:15:01'),
(6, 11, 2, '2025-05-27 20:31:14', 'confirmed', '2025-05-27 20:31:14', '2025-05-28 01:08:03'),
(7, 15, 2, '2025-05-27 22:29:09', 'confirmed', '2025-05-27 22:29:09', '2025-05-27 22:44:48'),
(15, 17, 2, '2025-05-29 19:17:45', 'confirmed', '2025-05-29 19:17:45', '2025-05-29 19:17:45'),
(16, 15, 1, '2025-05-29 21:21:16', 'confirmed', '2025-05-29 21:21:16', '2025-05-29 21:21:16'),
(20, 5, 1, '2025-06-01 20:50:18', 'confirmed', '2025-06-01 20:50:18', '2025-06-01 20:50:18'),
(22, 18, 1, '2025-06-02 21:27:02', 'confirmed', '2025-06-02 21:27:02', '2025-06-02 21:27:02'),
(23, 21, 1, '2025-06-02 21:56:41', 'confirmed', '2025-06-02 21:56:41', '2025-06-02 21:56:41'),
(24, 16, 1, '2025-06-04 13:29:43', 'confirmed', '2025-06-04 13:29:43', '2025-06-04 13:29:43');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `job_offer_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `application_date` datetime DEFAULT current_timestamp(),
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected','accepted') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`application_id`, `job_offer_id`, `student_id`, `application_date`, `cover_letter`, `resume_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(26, 5, 1, '2025-06-02 21:08:15', 'Interested in AI, especially generative models.', '/iteam-university-website/frontend/uploads/resumes/resume_683e04af5c54a_Calendrier_examens_Ing_Jour__S2_session_principale.pdf', 'pending', NULL, '2025-06-02 21:08:15', '2025-06-02 21:08:15'),
(27, 4, 1, '2025-06-02 21:15:09', 'I want to deepen my frontend experience.', '/iteam-university-website/frontend/uploads/resumes/resume_683e064d4fe0f_cv.pdf', 'reviewed', '', '2025-06-02 21:15:09', '2025-06-02 22:17:34');

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--

CREATE TABLE `job_offers` (
  `job_offer_id` int(11) NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `posted_date` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expiry_date` datetime DEFAULT NULL,
  `status` enum('active','filled','expired','draft') NOT NULL DEFAULT 'active',
  `job_type` enum('full-time','part-time','contract','internship') NOT NULL DEFAULT 'full-time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_offers`
--

INSERT INTO `job_offers` (`job_offer_id`, `organization_id`, `title`, `description`, `posted_date`, `created_at`, `updated_at`, `expiry_date`, `status`, `job_type`) VALUES
(1, 1, 'Web Developer', 'We are looking for a skilled web developer to join our team.', '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50', NULL, 'active', 'full-time'),
(2, 1, 'UX Designer', 'Seeking creative UX designer with 2+ years of experience.', '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50', NULL, 'active', 'full-time'),
(3, 2, 'Event Coordinator', 'Event management position for an organized individual.', '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50', NULL, 'active', 'full-time'),
(4, 1, 'Frontend Developer Intern', 'Assist in designing responsive interfaces using React.js and TailwindCSS.', '2025-05-30 14:21:22', '2025-05-01 09:00:00', '2025-05-01 09:00:00', NULL, 'active', 'full-time'),
(5, 2, 'Cybersecurity Analyst Intern', 'Participate in security auditing and threat modeling exercises.', '2025-05-30 14:21:22', '2025-05-02 10:30:00', '2025-05-02 10:30:00', NULL, 'active', 'full-time'),
(6, 3, 'AI Research Assistant', 'Contribute to ongoing research projects in NLP and computer vision.', '2025-05-30 14:21:22', '2025-05-03 11:45:00', '2025-05-03 11:45:00', NULL, 'active', 'full-time'),
(7, 2, 'DevOps Trainee', 'Learn CI/CD pipelines, containerization, and cloud deployments using Docker and Jenkins.', '2025-05-30 14:21:22', '2025-05-04 08:15:00', '2025-05-04 08:15:00', NULL, 'active', 'full-time'),
(8, 3, 'Mobile Developer Intern', 'Work on building cross-platform mobile apps using Flutter and Dart.', '2025-05-30 14:21:22', '2025-05-05 13:00:00', '2025-05-05 13:00:00', NULL, 'active', 'full-time');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `notification_type` enum('confirmation','reminder','cancellation','event_update','new_event_nearby','registration_open','feedback_request') NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `send_date` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `account_id`, `event_id`, `notification_type`, `message`, `is_read`, `send_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(2, 1, 2, 'confirmation', 'Your registration for Digital Marketing Conference has been confirmed.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(3, 2, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(4, 2, 2, 'reminder', 'Reminder: Digital Marketing Conference is tomorrow.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(5, 1, 3, 'cancellation', 'Your registration for Tech Career Fair has been cancelled.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(6, 1, NULL, 'new_event_nearby', 'A new event has been added near you: Data Science Webinar.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(7, 1, NULL, 'registration_open', 'Registration is now open for the upcoming Tech Career Fair.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(8, 1, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(9, 4, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(10, 5, 2, 'reminder', 'Reminder: Digital Marketing Conference is tomorrow.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(11, 2, 3, 'cancellation', 'Your registration for Tech Career Fair has been cancelled.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(12, 2, NULL, 'new_event_nearby', 'A new event has been added near you: Data Science Webinar.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(13, 2, NULL, 'registration_open', 'Registration is now open for the upcoming Tech Career Fair.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(14, 6, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(15, 1, 2, 'event_update', 'The Digital Marketing Conference has been rescheduled.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(16, 4, 1, 'event_update', 'The Web Development Workshop will start at 10 AM instead of 9 AM.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(17, 1, 2, 'event_update', 'The Digital Marketing Conference has been rescheduled.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(18, 2, 1, 'event_update', 'The Web Development Workshop will start at 10 AM instead of 9 AM.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(19, 1, 2, 'event_update', 'The system will undergo maintenance tonight.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(20, 4, 2, 'event_update', 'The system will undergo maintenance tonight.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(21, 6, 3, 'event_update', 'The system will undergo maintenance tonight.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(22, 2, 3, 'event_update', 'The system will undergo maintenance tonight.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(23, 7, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(24, 7, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(25, 3, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(26, 7, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', 0, '2025-05-26 21:04:50', '2025-05-26 21:04:50', '2025-05-26 21:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`organization_id`, `name`, `email`, `password`, `description`, `profile_picture`, `registration_date`, `status`, `created_at`, `updated_at`, `address`, `phone`) VALUES
(1, 'Tech Innovators', 'contact@techinnovators.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Leading technology innovation company specializing in events and workshops', NULL, '2025-05-26 21:04:50', 'active', '2025-05-26 21:04:50', '2025-05-26 21:04:50', NULL, NULL),
(2, 'Event Masters', 'info@eventmasters.org', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Professional event management organization', NULL, '2025-05-26 21:04:50', 'active', '2025-05-26 21:04:50', '2025-05-26 21:04:50', NULL, NULL),
(3, 'Digital Solutions', 'contact@digitalsolutions.net', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Digital solutions and training provider', NULL, '2025-05-26 21:04:50', 'inactive', '2025-05-26 21:04:50', '2025-05-26 21:04:50', NULL, NULL),
(4, 'Mattel', 'Mo@gmail.com', '$2y$10$4vSYYplEIBjJYjjl0MhECO8xhwMyiB1BlmDFaHtcOZl7TARa14rwC', 'The Tunisian-Mauritanian telecommunications company, Matel, is the leading mobile operator in Mauritania.', NULL, '2025-05-31 18:30:03', 'active', '2025-05-31 18:30:03', '2025-05-31 18:30:03', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `password`, `profile_picture`, `registration_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', 'john.doe@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', NULL, '2025-05-26 21:04:50', 'active', '2025-05-26 21:04:50', '2025-05-30 17:58:37'),
(2, 'Jane', 'Smith00', 'jane.smith@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', NULL, '2025-05-26 21:04:50', 'active', '2025-05-26 21:04:50', '2025-05-29 19:52:12'),
(3, 'Robert', 'Johnson', 'robert.johnson@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', NULL, '2025-05-26 21:04:50', 'inactive', '2025-05-26 21:04:50', '2025-05-26 21:04:50'),
(4, 'med', 'salem', 'ahmed@gmail.com', '$2y$10$lnGEkuAgE8vxq/ULCMr6v.5KfX168/FjqmGKVdK8UAmlLfV3X5GmW', NULL, '2025-05-31 18:26:49', 'active', '2025-05-31 18:26:49', '2025-05-31 18:26:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `account_type` (`account_type`,`reference_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `event_gallery`
--
ALTER TABLE `event_gallery`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_event_id_gallery` (`event_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `idx_user_id_registration` (`student_id`),
  ADD KEY `idx_event_id_registration` (`event_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `job_offer_id` (`job_offer_id`,`student_id`),
  ADD KEY `idx_ja_student` (`student_id`),
  ADD KEY `idx_ja_job` (`job_offer_id`),
  ADD KEY `idx_ja_status` (`status`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`job_offer_id`),
  ADD KEY `idx_organization_id_job` (`organization_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_account_id_notification` (`account_id`),
  ADD KEY `idx_event_id_notification` (`event_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`organization_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `event_gallery`
--
ALTER TABLE `event_gallery`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `job_offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `organizations` (`organization_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_gallery`
--
ALTER TABLE `event_gallery`
  ADD CONSTRAINT `event_gallery_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_offer_id`) REFERENCES `job_offers` (`job_offer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
