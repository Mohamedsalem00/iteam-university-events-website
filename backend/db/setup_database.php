<?php
// Database setup script
// This will create necessary tables if they don't exist based on init_db.sql structure

// Include database connection
require_once 'db_connection.php';

// Set content type
header('Content-Type: text/html');

echo "<html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;line-height:1.6}
.success{color:green;font-weight:bold}.error{color:red;font-weight:bold}.warning{color:#e67e22;font-weight:bold}
pre{background:#f5f5f5;padding:10px;overflow:auto;border-radius:4px}
h1{border-bottom:1px solid #ddd;padding-bottom:10px}
.card{background:#f9f9f9;border-radius:5px;padding:15px;margin:20px 0;box-shadow:0 2px 5px rgba(0,0,0,0.1)}
</style></head><body>";
echo "<h1>iTeam University Website - Database Setup</h1>";

// Function to execute SQL and display results
function executeSql($conn, $sql, $description) {
    echo "<div class='card'>";
    echo "<h3>$description</h3>";
    echo "<pre>$sql</pre>";
    
    try {
        $stmt = $conn->exec($sql);
        echo "<p class='success'>Success! Query executed successfully.</p>";
    } catch (PDOException $e) {
        // Check if the error is that the table already exists
        if(strpos($e->getMessage(), "already exists") !== false) {
            echo "<p class='warning'>Table already exists. Skipping creation.</p>";
        } else {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
}

// Check if database already has tables
try {
    $tablesStmt = $conn->query("SHOW TABLES");
    $existingTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(count($existingTables) > 0) {
        echo "<div class='card'>";
        echo "<h3>Database Check</h3>";
        echo "<p class='warning'>Database already contains tables: " . implode(", ", $existingTables) . "</p>";
        echo "<p>This script will only create tables that don't exist and add sample data if tables are empty.</p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div class='card'>";
    echo "<h3>Database Check</h3>";
    echo "<p class='error'>Error checking existing tables: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// USERS TABLE
// Check if users table exists
$userTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE users");
    $userTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Users Table Check</h3>";
    echo "<p class='warning'>Existing users table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $userTableExists = false;
}

// Create users table based on init_db.sql
if(!$userTableExists) {
    $sql_create_users = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        profile_picture VARCHAR(255),
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    executeSql($conn, $sql_create_users, "Creating users table");
}

// ORGANIZATIONS TABLE
// Check if organizations table exists
$orgTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE organizations");
    $orgTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Organizations Table Check</h3>";
    echo "<p class='warning'>Existing organizations table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $orgTableExists = false;
}

// Create organizations table based on init_db.sql
if(!$orgTableExists) {
    $sql_create_organizations = "CREATE TABLE IF NOT EXISTS organizations (
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
    )";
    
    executeSql($conn, $sql_create_organizations, "Creating organizations table");
}

// ADMINS TABLE
// Check if admins table exists
$adminTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE admins");
    $adminTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Admins Table Check</h3>";
    echo "<p class='warning'>Existing admins table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $adminTableExists = false;
}

// Create admins table based on init_db.sql
if(!$adminTableExists) {
    $sql_create_admins = "CREATE TABLE IF NOT EXISTS admins (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    executeSql($conn, $sql_create_admins, "Creating admins table");
}

// ACCOUNTS TABLE
// Check if accounts table exists
$accountsTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE accounts");
    $accountsTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Accounts Table Check</h3>";
    echo "<p class='warning'>Existing accounts table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $accountsTableExists = false;
}

// Create accounts table based on init_db.sql
if(!$accountsTableExists) {
    $sql_create_accounts = "CREATE TABLE IF NOT EXISTS accounts (
        account_id INT AUTO_INCREMENT PRIMARY KEY,
        account_type ENUM('user', 'organization', 'admin') NOT NULL,
        reference_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE (account_type, reference_id)
    )";
    
    executeSql($conn, $sql_create_accounts, "Creating accounts table");
}

// EVENTS TABLE
// Check if events table exists
$eventsTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE events");
    $eventsTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Events Table Check</h3>";
    echo "<p class='warning'>Existing events table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $eventsTableExists = false;
}

// Create events table based on init_db.sql
if(!$eventsTableExists) {
    $sql_create_events = "CREATE TABLE IF NOT EXISTS events (
        event_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        location VARCHAR(255) NOT NULL,
        event_type ENUM('workshop', 'conference', 'fair', 'webinar') NOT NULL,
        max_capacity INT,
        organizer_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organizer_id) REFERENCES organizations(organization_id) ON DELETE SET NULL
    )";
    
    executeSql($conn, $sql_create_events, "Creating events table");
}

// EVENT_REGISTRATIONS TABLE
// Check if event_registrations table exists
$regTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE event_registrations");
    $regTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Event Registrations Table Check</h3>";
    echo "<p class='warning'>Existing event_registrations table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $regTableExists = false;
}

// Create event_registrations table based on init_db.sql
if(!$regTableExists) {
    $sql_create_registrations = "CREATE TABLE IF NOT EXISTS event_registrations (
        registration_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        user_id INT,
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    executeSql($conn, $sql_create_registrations, "Creating event_registrations table");
}

// EVENT_GALLERY TABLE
// Check if event_gallery table exists
$galleryTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE event_gallery");
    $galleryTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Event Gallery Table Check</h3>";
    echo "<p class='warning'>Existing event_gallery table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $galleryTableExists = false;
}

// Create event_gallery table based on init_db.sql
if(!$galleryTableExists) {
    $sql_create_gallery = "CREATE TABLE IF NOT EXISTS event_gallery (
        image_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        image_url VARCHAR(255) NOT NULL,
        upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        caption VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
    )";
    
    executeSql($conn, $sql_create_gallery, "Creating event_gallery table");
}

// NOTIFICATIONS TABLE
// Check if notifications table exists
$notifTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE notifications");
    $notifTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Notifications Table Check</h3>";
    echo "<p class='warning'>Existing notifications table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $notifTableExists = false;
}

// Create notifications table based on init_db.sql
if(!$notifTableExists) {
    $sql_create_notifications = "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        event_id INT,
        notification_type ENUM('confirmation', 'reminder', 'cancellation') NOT NULL,
        message TEXT NOT NULL,
        send_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
    )";
    
    executeSql($conn, $sql_create_notifications, "Creating notifications table");
}

// JOB_OFFERS TABLE
// Check if job_offers table exists
$jobsTableExists = false;
try {
    $stmt = $conn->query("DESCRIBE job_offers");
    $jobsTableExists = true;
    echo "<div class='card'>";
    echo "<h3>Job Offers Table Check</h3>";
    echo "<p class='warning'>Existing job_offers table found.</p>";
    echo "</div>";
} catch (PDOException $e) {
    $jobsTableExists = false;
}

// Create job_offers table based on init_db.sql
if(!$jobsTableExists) {
    $sql_create_jobs = "CREATE TABLE IF NOT EXISTS job_offers (
        job_offer_id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        posted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE CASCADE
    )";
    
    executeSql($conn, $sql_create_jobs, "Creating job_offers table");
}

// CREATE INDEXES
echo "<div class='card'>";
echo "<h3>Creating Indexes</h3>";

// Define indexes to create based on init_db.sql
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_user_id_registration ON event_registrations(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_event_id_registration ON event_registrations(event_id)",
    "CREATE INDEX IF NOT EXISTS idx_event_id_gallery ON event_gallery(event_id)",
    "CREATE INDEX IF NOT EXISTS idx_user_id_notification ON notifications(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_event_id_notification ON notifications(event_id)",
    "CREATE INDEX IF NOT EXISTS idx_organization_id_job ON job_offers(organization_id)"
];

// Create each index
foreach ($indexes as $indexSql) {
    try {
        $conn->exec($indexSql);
        echo "<p class='success'>Created index: " . $indexSql . "</p>";
    } catch (PDOException $e) {
        // Ignore duplicate index errors
        if(strpos($e->getMessage(), "already exists") !== false) {
            echo "<p class='warning'>Index already exists: " . $indexSql . "</p>";
        } else {
            echo "<p class='error'>Error creating index: " . $e->getMessage() . "</p>";
        }
    }
}
echo "</div>";

// ADD SAMPLE DATA IF TABLES ARE EMPTY
echo "<div class='card'>";
echo "<h3>Sample Data</h3>";

// Check if users table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if($userCount == 0) {
        echo "<p>Adding sample data to users table...</p>";
        
        // Sample users with bcrypt hashed password (password: "password123")
        // Using the exact hash from your init_db.sql
        $passwordHash = '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm';
        
        $sampleUsersSql = "INSERT INTO users (first_name, last_name, email, password, status, created_at, updated_at) VALUES
            ('John', 'Doe', 'john.doe@example.com', '$passwordHash', 'active', NOW(), NOW()),
            ('Jane', 'Smith', 'jane.smith@example.com', '$passwordHash', 'active', NOW(), NOW()),
            ('Robert', 'Johnson', 'robert.johnson@example.com', '$passwordHash', 'inactive', NOW(), NOW())";
        
        try {
            $conn->exec($sampleUsersSql);
            echo "<p class='success'>Added sample users.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample users: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>Users table already has data ($userCount records).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking users table: " . $e->getMessage() . "</p>";
}

// Check if organizations table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM organizations");
    $orgCount = $stmt->fetchColumn();
    
    if($orgCount == 0) {
        echo "<p>Adding sample data to organizations table...</p>";
        
        // Sample organizations with bcrypt hashed password (password: "password123")
        // Using the exact hash from your init_db.sql
        $passwordHash = '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm';
        
        $sampleOrgsSql = "INSERT INTO organizations (name, email, password, description, status, created_at, updated_at) VALUES
            ('Tech Innovators', 'contact@techinnovators.com', '$passwordHash', 'Leading technology innovation company specializing in events and workshops', 'active', NOW(), NOW()),
            ('Event Masters', 'info@eventmasters.org', '$passwordHash', 'Professional event management organization', 'active', NOW(), NOW()),
            ('Digital Solutions', 'contact@digitalsolutions.net', '$passwordHash', 'Digital solutions and training provider', 'inactive', NOW(), NOW())";
        
        try {
            $conn->exec($sampleOrgsSql);
            echo "<p class='success'>Added sample organizations.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample organizations: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>Organizations table already has data ($orgCount records).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking organizations table: " . $e->getMessage() . "</p>";
}

// Check if admins table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM admins");
    $adminCount = $stmt->fetchColumn();
    
    if($adminCount == 0) {
        echo "<p>Adding sample data to admins table...</p>";
        
        // Admin user with bcrypt hashed password (password: "adminpass123")
        // Using the exact hash from your init_db.sql
        $adminPassword = '$2y$10$CcvOzYXPHBKT8tZ1i1LUmeYRngL9U2OKvMlZ4ExfO0QiXFJ2A0AFO';
        
        $sampleAdminsSql = "INSERT INTO admins (username, email, password, created_at, updated_at) VALUES
            ('admin', 'admin@iteamuniversity.com', '$adminPassword', NOW(), NOW())";
        
        try {
            $conn->exec($sampleAdminsSql);
            echo "<p class='success'>Added admin user.</p>";
            echo "<p>Admin email: admin@iteamuniversity.com<br>Password: adminpass123</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding admin user: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>Admins table already has data ($adminCount records).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking admins table: " . $e->getMessage() . "</p>";
}

// Check if events table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM events");
    $eventCount = $stmt->fetchColumn();
    
    if($eventCount == 0 && $orgCount > 0) { // Only add events if we have organizations
        echo "<p>Adding sample data to events table...</p>";
        
        $sampleEventsSql = "INSERT INTO events (title, description, start_date, end_date, location, event_type, max_capacity, organizer_id, created_at, updated_at) VALUES
            ('Web Development Workshop', 'Learn the basics of web development with HTML, CSS, and JavaScript', '2025-05-15 09:00:00', '2025-05-15 16:00:00', 'Room 101, Technology Building', 'workshop', 30, 1, NOW(), NOW()),
            ('Digital Marketing Conference', 'Annual conference on digital marketing trends and strategies', '2025-06-20 08:30:00', '2025-06-21 17:00:00', 'Grand Conference Hall', 'conference', 200, 2, NOW(), NOW()),
            ('Tech Career Fair', 'Connect with top employers in the technology sector', '2025-07-10 10:00:00', '2025-07-10 15:00:00', 'University Main Campus', 'fair', 500, 1, NOW(), NOW()),
            ('Data Science Webinar', 'Introduction to data science and machine learning', '2025-05-05 14:00:00', '2025-05-05 16:00:00', 'Online', 'webinar', 100, 2, NOW(), NOW())";
        
        try {
            $conn->exec($sampleEventsSql);
            echo "<p class='success'>Added sample events.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample events: " . $e->getMessage() . "</p>";
        }
    } elseif($eventCount > 0) {
        echo "<p class='success'>Events table already has data ($eventCount records).</p>";
    } else {
        echo "<p class='warning'>Skipping events sample data (organizations required).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking events table: " . $e->getMessage() . "</p>";
}

// Check if event_registrations table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM event_registrations");
    $regCount = $stmt->fetchColumn();
    
    if($regCount == 0 && $eventCount > 0 && $userCount > 0) { // Only add if we have events and users
        echo "<p>Adding sample data to event_registrations table...</p>";
        
        $sampleRegsSql = "INSERT INTO event_registrations (event_id, user_id, status, created_at, updated_at) VALUES
            (1, 1, 'confirmed', NOW(), NOW()),
            (2, 1, 'confirmed', NOW(), NOW()),
            (3, 1, 'pending', NOW(), NOW()),
            (1, 2, 'confirmed', NOW(), NOW()),
            (2, 2, 'cancelled', NOW(), NOW())";
        
        try {
            $conn->exec($sampleRegsSql);
            echo "<p class='success'>Added sample event registrations.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample event registrations: " . $e->getMessage() . "</p>";
        }
    } elseif($regCount > 0) {
        echo "<p class='success'>Event registrations table already has data ($regCount records).</p>";
    } else {
        echo "<p class='warning'>Skipping event registrations sample data (events and users required).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking event_registrations table: " . $e->getMessage() . "</p>";
}

// Check if event_gallery table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM event_gallery");
    $galleryCount = $stmt->fetchColumn();
    
    if($galleryCount == 0 && $eventCount > 0) { // Only add if we have events
        echo "<p>Adding sample data to event_gallery table...</p>";
        
        $sampleGallerySql = "INSERT INTO event_gallery (event_id, image_url, caption, created_at, updated_at) VALUES
            (1, 'assets/images/gallery/workshop1.jpg', 'Participants working on coding exercises', NOW(), NOW()),
            (1, 'assets/images/gallery/workshop2.jpg', 'Group discussion on web development best practices', NOW(), NOW()),
            (2, 'assets/images/gallery/conference1.jpg', 'Opening keynote speech', NOW(), NOW())";
        
        try {
            $conn->exec($sampleGallerySql);
            echo "<p class='success'>Added sample event gallery images.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample event gallery images: " . $e->getMessage() . "</p>";
        }
    } elseif($galleryCount > 0) {
        echo "<p class='success'>Event gallery table already has data ($galleryCount records).</p>";
    } else {
        echo "<p class='warning'>Skipping event gallery sample data (events required).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking event_gallery table: " . $e->getMessage() . "</p>";
}

// Check if notifications table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM notifications");
    $notifCount = $stmt->fetchColumn();
    
    if($notifCount == 0 && $eventCount > 0 && $userCount > 0) { // Only add if we have events and users
        echo "<p>Adding sample data to notifications table...</p>";
        
        $sampleNotifsSql = "INSERT INTO notifications (user_id, event_id, notification_type, message, created_at, updated_at) VALUES
            (1, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', NOW(), NOW()),
            (1, 2, 'confirmation', 'Your registration for Digital Marketing Conference has been confirmed.', NOW(), NOW()),
            (2, 1, 'confirmation', 'Your registration for Web Development Workshop has been confirmed.', NOW(), NOW()),
            (2, 2, 'cancellation', 'You have cancelled your registration for Digital Marketing Conference.', NOW(), NOW())";
        
        try {
            $conn->exec($sampleNotifsSql);
            echo "<p class='success'>Added sample notifications.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample notifications: " . $e->getMessage() . "</p>";
        }
    } elseif($notifCount > 0) {
        echo "<p class='success'>Notifications table already has data ($notifCount records).</p>";
    } else {
        echo "<p class='warning'>Skipping notifications sample data (events and users required).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking notifications table: " . $e->getMessage() . "</p>";
}

// Check if job_offers table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM job_offers");
    $jobCount = $stmt->fetchColumn();
    
    if($jobCount == 0 && $orgCount > 0) { // Only add if we have organizations
        echo "<p>Adding sample data to job_offers table...</p>";
        
        $sampleJobsSql = "INSERT INTO job_offers (organization_id, title, description, created_at, updated_at) VALUES
            (1, 'Web Developer', 'We are looking for a skilled web developer to join our team.', NOW(), NOW()),
            (1, 'UX Designer', 'Seeking creative UX designer with 2+ years of experience.', NOW(), NOW()),
            (2, 'Event Coordinator', 'Event management position for an organized individual.', NOW(), NOW())";
        
        try {
            $conn->exec($sampleJobsSql);
            echo "<p class='success'>Added sample job offers.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample job offers: " . $e->getMessage() . "</p>";
        }
    } elseif($jobCount > 0) {
        echo "<p class='success'>Job offers table already has data ($jobCount records).</p>";
    } else {
        echo "<p class='warning'>Skipping job offers sample data (organizations required).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking job_offers table: " . $e->getMessage() . "</p>";
}

// Check if accounts table has data
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM accounts");
    $accountsCount = $stmt->fetchColumn();
    
    if($accountsCount == 0) {
        echo "<p>Adding sample data to accounts table...</p>";
        
        $sampleAccountsSql = "INSERT INTO accounts (account_type, reference_id, created_at, updated_at) VALUES
            ('user', 1, NOW(), NOW()),
            ('user', 2, NOW(), NOW()),
            ('user', 3, NOW(), NOW()),
            ('organization', 1, NOW(), NOW()),
            ('organization', 2, NOW(), NOW()),
            ('organization', 3, NOW(), NOW()),
            ('admin', 1, NOW(), NOW())";
        
        try {
            $conn->exec($sampleAccountsSql);
            echo "<p class='success'>Added sample accounts.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error adding sample accounts: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>Accounts table already has data ($accountsCount records).</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking accounts table: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Final message
echo "<div class='card'>";
echo "<h2>Database Setup Complete</h2>";
echo "<p>If there were no errors above, your database has been set up successfully.</p>";
echo "<p>You can now <a href='/iteam-university-website/frontend/auth/login.html'>login</a> to the website with the following credentials:</p>";
echo "<ul>";
echo "<li><strong>User:</strong> john.doe@example.com / password123</li>";
echo "<li><strong>Organization:</strong> contact@techinnovators.com / password123</li>";
echo "<li><strong>Admin:</strong> admin@iteamuniversity.com / adminpass123</li>";
echo "</ul>";
echo "<p><a href='/iteam-university-website/test/db-test.html' target='_blank'>Open the database test tool</a> to view the complete structure.</p>";
echo "</div>";

echo "</body></html>";
?>