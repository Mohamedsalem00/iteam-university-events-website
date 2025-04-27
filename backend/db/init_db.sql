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
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  profile_picture VARCHAR(255),
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active', 'inactive') DEFAULT 'active'
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
  status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Admins table
CREATE TABLE admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
  FOREIGN KEY (organizer_id) REFERENCES organizations(organization_id) ON DELETE SET NULL
);

-- Event Registrations table
CREATE TABLE event_registrations (
  registration_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  user_id INT,
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Event Gallery table
CREATE TABLE event_gallery (
  image_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  image_url VARCHAR(255) NOT NULL,
  upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  caption VARCHAR(255),
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  event_id INT,
  notification_type ENUM('confirmation', 'reminder', 'cancellation') NOT NULL,
  message TEXT NOT NULL,
  send_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- Job Offers table
CREATE TABLE job_offers (
  job_offer_id INT AUTO_INCREMENT PRIMARY KEY,
  organization_id INT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  posted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_user_id_registration ON event_registrations(user_id);
CREATE INDEX idx_event_id_registration ON event_registrations(event_id);
CREATE INDEX idx_event_id_gallery ON event_gallery(event_id);
CREATE INDEX idx_user_id_notification ON notifications(user_id);
CREATE INDEX idx_event_id_notification ON notifications(event_id);
CREATE INDEX idx_organization_id_job ON job_offers(organization_id);

-- Insert sample data

-- Sample users with hashed passwords (password is "password123")
INSERT INTO users (first_name, last_name, email, password, status) VALUES
('John', 'Doe', 'john.doe@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'active'),
('Jane', 'Smith', 'jane.smith@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'active'),
('Robert', 'Johnson', 'robert.johnson@example.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'inactive');

-- Sample organizations with hashed passwords (password is "password123")
INSERT INTO organizations (name, email, password, description, status) VALUES
('Tech Innovators', 'contact@techinnovators.com', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Leading technology innovation company specializing in events and workshops', 'active'),
('Event Masters', 'info@eventmasters.org', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Professional event management organization', 'active'),
('Digital Solutions', 'contact@digitalsolutions.net', '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm', 'Digital solutions and training provider', 'inactive');

-- Sample admin with hashed password (password is "adminpass123")
INSERT INTO admins (username, email, password) VALUES
('admin', 'admin@iteamuniversity.com', '$2y$10$CcvOzYXPHBKT8tZ1i1LUmeYRngL9U2OKvMlZ4ExfO0QiXFJ2A0AFO');

-- Sample events
INSERT INTO events (title, description, start_date, end_date, location, event_type, max_capacity, organizer_id) VALUES
('Web Development Workshop', 'Learn the basics of web development with HTML, CSS, and JavaScript', '2025-05-15 09:00:00', '2025-05-15 16:00:00', 'Room 101, Technology Building', 'workshop', 30, 1),
('Digital Marketing Conference', 'Annual conference on digital marketing trends and strategies', '2025-06-20 08:30:00', '2025-06-21 17:00:00', 'Grand Conference Hall', 'conference', 200, 2),
('Tech Career Fair', 'Connect with top employers in the technology sector', '2025-07-10 10:00:00', '2025-07-10 15:00:00', 'University Main Campus', 'fair', 500, 1),
('Data Science Webinar', 'Introduction to data science and machine learning', '2025-05-05 14:00:00', '2025-05-05 16:00:00', 'Online', 'webinar', 100, 2);

-- Sample event registrations
INSERT INTO event_registrations (event_id, user_id, status) VALUES
(1, 1, 'confirmed'),
(2, 1, 'confirmed'),
(3, 1, 'pending'),
(1, 2, 'confirmed'),
(2, 2, 'cancelled');

-- Sample event gallery
INSERT INTO event_gallery (event_id, image_url, caption) VALUES
(1, 'assets/images/gallery/workshop1.jpg', 'Participants working on coding exercises'),
(1, 'assets/images/gallery/workshop2.jpg', 'Group discussion on web development best practices'),
(2, 'assets/images/gallery/conference1.jpg', 'Opening keynote speech');

-- Sample notifications
INSERT INTO notifications (user_id, event_id, notification_type, message) VALUES
(1, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.'),
(1, 2, 'confirmation', 'Your registration for Digital Marketing Conference has been confirmed.'),
(2, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.'),
(2, 2, 'cancellation', 'You have cancelled your registration for Digital Marketing Conference.');

-- Sample job offers
INSERT INTO job_offers (organization_id, title, description) VALUES
(1, 'Web Developer', 'We are looking for a skilled web developer to join our team.'),
(1, 'UX Designer', 'Seeking creative UX designer with 2+ years of experience.'),
(2, 'Event Coordinator', 'Event management position for an organized individual.');
