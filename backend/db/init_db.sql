-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS event_management;
USE event_management;

-- Drop tables if they exist to ensure clean setup
DROP TABLE IF EXISTS job_offers;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS event_gallery;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS job_applications;
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS students;

-- students table
CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  profile_picture VARCHAR(255),
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Organizations table
CREATE TABLE organizations (
  organization_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  description TEXT,
  profile_picture VARCHAR(255),
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Admins table
CREATE TABLE admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE accounts (
  account_id INT AUTO_INCREMENT PRIMARY KEY,
  account_type ENUM('student', 'organization', 'admin') NOT NULL,
  reference_id INT NOT NULL,
  last_login DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE (account_type, reference_id)
);



-- Events table
CREATE TABLE events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  start_date DATETIME NOT NULL,
  end_date DATETIME NOT NULL,
  location VARCHAR(255) NOT NULL,
  event_type ENUM('workshop', 'conference', 'fair', 'webinar') NOT NULL,
  max_capacity INT,
  organizer_id INT,
  requires_approval BOOLEAN DEFAULT FALSE,
  thumbnail_url VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES organizations(organization_id) ON DELETE SET NULL
);

-- Event Registrations table
CREATE TABLE event_registrations (
  registration_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  student_id INT,
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);


-- Event Gallery table
CREATE TABLE event_gallery (
  image_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  image_url VARCHAR(255) NOT NULL,
  upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  caption VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);


-- Notifications table
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT,
  event_id INT,
  notification_type ENUM('confirmation', 'reminder', 'cancellation', 'event_update', 'new_event_nearby', 'registration_open', 'feedback_request') NOT NULL, -- Added more types
  message VARCHAR(500) NOT NULL,
  is_read BOOLEAN DEFAULT FALSE, -- <<< ADDED THIS LINE
  send_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);


-- Job Offers table
CREATE TABLE job_offers (
  job_offer_id INT AUTO_INCREMENT PRIMARY KEY,
  organization_id INT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  posted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  job_type ENUM('full-time', 'part-time', 'internship', 'contract') DEFAULT 'full-time',
  expiry_date DATETIME,
  status ENUM('active', 'expired', 'filled', 'draft') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE CASCADE
);

-- Create job applications table to track student applications to job offers
CREATE TABLE job_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_offer_id INT NOT NULL,
    student_id INT NOT NULL,
    application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    cover_letter TEXT,
    resume_path VARCHAR(255),
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'accepted') DEFAULT 'pending',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (job_offer_id) REFERENCES job_offers(job_offer_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    
    -- Prevent duplicate applications from the same student for the same job
    UNIQUE KEY (job_offer_id, student_id)
);



-- Indexes for optimization
CREATE INDEX idx_student_id_registration ON event_registrations(student_id);
CREATE INDEX idx_event_id_registration ON event_registrations(event_id);
CREATE INDEX idx_event_id_gallery ON event_gallery(event_id);
CREATE INDEX idx_account_id_notification ON notifications(account_id);
CREATE INDEX idx_event_id_notification ON notifications(event_id);
CREATE INDEX idx_organization_id_job ON job_offers(organization_id);

-- Sample students
INSERT INTO students (first_name, last_name, email, password, status, created_at, updated_at) VALUES
('John', 'Doe', 'john.doe@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'active', NOW(), NOW()),
('Jane', 'Smith', 'jane.smith@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'active', NOW(), NOW()),
('Robert', 'Johnson', 'robert.johnson@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'inactive', NOW(), NOW());

-- Sample organizations
INSERT INTO organizations (name, email, password, description, status, created_at, updated_at) VALUES
('Tech Innovators', 'contact@techinnovators.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Leading technology innovation company specializing in events and workshops', 'active', NOW(), NOW()),
('Event Masters', 'info@eventmasters.org', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Professional event management organization', 'active', NOW(), NOW()),
('Digital Solutions', 'contact@digitalsolutions.net', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Digital solutions and training provider', 'inactive', NOW(), NOW());

-- Sample admin
INSERT INTO admins (username, email, password, created_at, updated_at) VALUES
('admin', 'admin@iteamuniversity.com', '$2y$10$CcvOzYXPHBKT8tZ1i1LUmeYRngL9U2OKvMlZ4ExfO0QiXFJ2A0AFO', NOW(), NOW());

INSERT INTO events (
  title, description, start_date, end_date, location, event_type,
  max_capacity, organizer_id, thumbnail_url, created_at, updated_at
) VALUES
-- 🔁 يوم فيه عدة أحداث بأنواع وأماكن مختلفة
('Frontend Fundamentals', 'Learn HTML, CSS, and JavaScript basics', 
 '2025-06-10 09:00:00', '2025-06-10 12:00:00', 
 'Room A1', 'workshop', 25, 1, NULL, NOW(), NOW()),

('Defensive Security Tactics', 'Overview of current cyber threats and defenses', 
 '2025-06-10 13:00:00', '2025-06-10 14:30:00', 
 'Online', 'webinar', 100, 2, NULL, NOW(), NOW()),

('Tech Opportunities Expo', 'Network with recruiters and explore job offers', 
 '2025-06-11 10:00:00', '2025-06-11 17:00:00', 
 'Main Hall', 'fair', 300, 3, NULL, NOW(), NOW()),

('AI Horizons Summit', 'Exploring the impact of AI on industry and society', 
 '2025-06-12 09:00:00', '2025-06-12 18:00:00', 
 'Auditorium', 'conference', 200, 2, NULL, NOW(), NOW()),

-- 🌐 حدث مستقبلي
('Next-Gen Cloud Architectures', 'Advanced AWS and Azure solutions', 
 '2025-07-05 09:00:00', '2025-07-05 12:00:00', 
 'Room B2', 'workshop', 40, 1, NULL, NOW(), NOW()),

-- 📆 حدث في الماضي القريب لاختبار عرض الأحداث السابقة
('Data Insights Online', 'Data analysis and machine learning primer', 
 '2025-06-01 15:00:00', '2025-06-01 17:00:00', 
 'Online', 'webinar', 150, 2, NULL, NOW(), NOW()),

-- 💥 حدثين في نفس الوقت ونفس القاعة لاختبار تضارب الموارد
('CI/CD Essentials', 'Hands-on DevOps with Jenkins and GitHub Actions', 
 '2025-08-01 10:00:00', '2025-08-01 12:00:00', 
 'Room A1', 'workshop', 30, 1, NULL, NOW(), NOW()),

('Smart Health with ML', 'Applications of machine learning in medicine', 
 '2025-08-01 10:30:00', '2025-08-01 13:00:00', 
 'Room A1', 'conference', 100, 2, NULL, NOW(), NOW());



-- Sample registrations
INSERT INTO event_registrations (event_id, student_id, status, created_at, updated_at) VALUES
(1, 1, 'confirmed', NOW(), NOW()),
(2, 1, 'confirmed', NOW(), NOW()),
(3, 1, 'pending', NOW(), NOW()),
(1, 2, 'confirmed', NOW(), NOW()),
(2, 2, 'cancelled', NOW(), NOW());

INSERT INTO event_gallery (event_id, image_url, caption, created_at, updated_at) VALUES
(1, 'assets/images/gallery/gallery-1.jpeg', 'Participants working on coding exercises', NOW(), NOW()),
(1, 'assets/images/gallery/gallery-2.jpeg', 'Group discussion on web development best practices', NOW(), NOW()),
(1, 'assets/images/gallery/gallery-3.jpeg', 'Interactive session with live coding', NOW(), NOW()),

(2, 'assets/images/gallery/gallery-4.jpeg', 'Opening keynote speech', NOW(), NOW()),
(2, 'assets/images/gallery/gallery-5.jpeg', 'Panel discussion with industry leaders', NOW(), NOW()),
(2, 'assets/images/gallery/gallery-6.jpeg', 'Audience at the main auditorium', NOW(), NOW()),

(3, 'assets/images/gallery/gallery-7.jpeg', 'Teams collaborating during the hackathon', NOW(), NOW()),
(3, 'assets/images/gallery/gallery-8.jpeg', 'Pitching ideas to the judges', NOW(), NOW()),
(3, 'assets/images/gallery/gallery-9.jpeg', 'Winning team receiving award', NOW(), NOW());




-- Accounts for students (assuming student_id = 1, 2, 3)
INSERT INTO accounts (account_type, reference_id, created_at, updated_at) VALUES
('student', 1, NOW(), NOW()),
('student', 2, NOW(), NOW()),
('student', 3, NOW(), NOW());

-- Accounts for organizations (assuming organization_id = 1, 2, 3)
INSERT INTO accounts (account_type, reference_id, created_at, updated_at) VALUES
('organization', 1, NOW(), NOW()),
('organization', 2, NOW(), NOW()),
('organization', 3, NOW(), NOW());

-- Account for admin (assuming admin_id = 1)
INSERT INTO accounts (account_type, reference_id, created_at, updated_at) VALUES
('admin', 1, NOW(), NOW());



-- Sample notifications (FIXED)
INSERT INTO notifications (account_id, event_id, notification_type, message, is_read, created_at, updated_at) VALUES
(1, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', FALSE, NOW(), NOW()),
(1, 2, 'confirmation', 'Your registration for Digital Marketing Conference has been confirmed.', FALSE, NOW(), NOW()),

(2, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', FALSE, NOW(), NOW()),
(2, 2, 'reminder', 'Reminder: Digital Marketing Conference is tomorrow.', FALSE, NOW(), NOW()),
(1, 3, 'cancellation', 'Your registration for Tech Career Fair has been cancelled.', FALSE, NOW(), NOW()),
(1, NULL, 'new_event_nearby', 'A new event has been added near you: Data Science Webinar.', FALSE, NOW(), NOW()),
(1, NULL, 'registration_open', 'Registration is now open for the upcoming Tech Career Fair.', FALSE, NOW(), NOW()),
(1, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', FALSE, NOW(), NOW()),
(4, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', FALSE, NOW(), NOW()),
(5, 2, 'reminder', 'Reminder: Digital Marketing Conference is tomorrow.', FALSE, NOW(), NOW()),
(2, 3, 'cancellation', 'Your registration for Tech Career Fair has been cancelled.', FALSE, NOW(), NOW()),
(2, NULL, 'new_event_nearby', 'A new event has been added near you: Data Science Webinar.', FALSE, NOW(), NOW()),
(2, NULL, 'registration_open', 'Registration is now open for the upcoming Tech Career Fair.', FALSE, NOW(), NOW()),
(6, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', FALSE, NOW(), NOW()),
(1, 2, 'event_update', 'The Digital Marketing Conference has been rescheduled.', FALSE, NOW(), NOW()),
(4, 1, 'event_update', 'The Web Development Workshop will start at 10 AM instead of 9 AM.', FALSE, NOW(), NOW()),
(1, 2, 'event_update', 'The Digital Marketing Conference has been rescheduled.', FALSE, NOW(), NOW()),
(2, 1, 'event_update', 'The Web Development Workshop will start at 10 AM instead of 9 AM.', FALSE, NOW(), NOW()),
(1, 2, 'event_update', 'The system will undergo maintenance tonight.', FALSE, NOW(), NOW()),
(4, 2, 'event_update', 'The system will undergo maintenance tonight.', FALSE, NOW(), NOW()),
(6, 3, 'event_update', 'The system will undergo maintenance tonight.', FALSE, NOW(), NOW()),
(2, 3, 'event_update', 'The system will undergo maintenance tonight.', FALSE, NOW(), NOW()),
(7, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', FALSE, NOW(), NOW()),
(7, 1, 'feedback_request', 'Please provide feedback for the Web Development Workshop.', FALSE, NOW(), NOW()),
(3, 1,'feedback_request','Please provide feedback for the Web Development Workshop.',FALSE,NOW(),NOW()),
(7,1,'feedback_request','Please provide feedback for the Web Development Workshop.',FALSE,NOW(),NOW());



-- Sample job offers
INSERT INTO job_offers (organization_id, title, description, created_at, updated_at) VALUES
(1, 'Web Developer', 'We are looking for a skilled web developer to join our team.', NOW(), NOW()),
(1, 'UX Designer', 'Seeking creative UX designer with 2+ years of experience.', NOW(), NOW()),
(2, 'Event Coordinator', 'Event management position for an organized individual.', NOW(), NOW()),
(3, 'Data Analyst', 'Join our data team to analyze and visualize complex datasets.', NOW(), NOW()),
(1, 'Software Engineer', 'Full-stack developer with expertise in Node.js and React.', NOW(), NOW());
(1, 'Frontend Developer Intern', 'Assist in designing responsive interfaces using React.js and TailwindCSS.', '2025-05-01 09:00:00', '2025-05-01 09:00:00'),
(2, 'Cybersecurity Analyst Intern', 'Participate in security auditing and threat modeling exercises.', '2025-05-02 10:30:00', '2025-05-02 10:30:00'),
(3, 'AI Research Assistant', 'Contribute to ongoing research projects in NLP and computer vision.', '2025-05-03 11:45:00', '2025-05-03 11:45:00'),
(2, 'DevOps Trainee', 'Learn CI/CD pipelines, containerization, and cloud deployments using Docker and Jenkins.', '2025-05-04 08:15:00', '2025-05-04 08:15:00'),
(3, 'Mobile Developer Intern', 'Work on building cross-platform mobile apps using Flutter and Dart.', '2025-05-05 13:00:00', '2025-05-05 13:00:00');


