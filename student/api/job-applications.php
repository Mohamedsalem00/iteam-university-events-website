<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php'; // Fixed relative path

// Set headers
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['student_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to submit an application.'
    ]);
    exit;
}

// Handle withdraw action via GET with action parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'withdraw') {
    $applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $studentId = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 0;
    
    if (!$applicationId || !$studentId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required information. Please ensure you\'re logged in as a student.'
        ]);
        exit;
    }
    
    try {
        // Verify the application belongs to the student
        $checkQuery = "SELECT application_id FROM job_applications 
                       WHERE application_id = :application_id AND student_id = :student_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
        $checkStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to withdraw this application.'
            ]);
            exit;
        }
        
        // Delete the application
        $deleteQuery = "DELETE FROM job_applications WHERE application_id = :application_id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
        $deleteStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application withdrawn successfully.'
        ]);
    } catch (PDOException $e) {
        error_log("Error withdrawing application: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again later.'
        ]);
    }
    exit;
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Submit new application
    $jobId = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $studentId = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 0;
    $coverLetter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
    
    // Validate required fields
    if (!$jobId || !$studentId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required information. Please ensure you\'re logged in as a student.'
        ]);
        exit;
    }
    
    if (empty($coverLetter)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a cover letter.'
        ]);
        exit;
    }
    
    // Check if student has already applied to this job
    try {
        $checkQuery = "SELECT application_id FROM job_applications 
                      WHERE job_offer_id = :job_id AND student_id = :student_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $checkStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'You have already applied for this job.'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error checking existing application: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again later.'
        ]);
        exit;
    }
    
    // Handle resume upload
    $resumePath = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($fileInfo, $_FILES['resume']['tmp_name']);
        finfo_close($fileInfo);
        
        // Also check file extension as fallback
        $fileExt = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        $validExtensions = ['pdf', 'doc', 'docx'];
        
        if (!in_array($fileType, $allowedTypes) && !in_array($fileExt, $validExtensions)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Please upload a PDF, DOC, or DOCX file.'
            ]);
            exit;
        }
        
        // Check file size (5MB max)
        if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'File size exceeds the maximum limit of 5MB.'
            ]);
            exit;
        }
        
        // Create directory if it doesn't exist
        $rootDir = $_SERVER['DOCUMENT_ROOT']; // This gives the physical path to your web root
        $uploadDir = $rootDir . '/iteam-university-website/frontend/uploads/resumes/';
        
        // Make sure parent directories exist
        if (!file_exists(dirname($uploadDir))) {
            if (!mkdir(dirname($uploadDir), 0755, true)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create parent upload directory. Please contact support.'
                ]);
                exit;
            }
        }
        
        // Create the resumes folder if needed
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create upload directory. Please contact support.'
                ]);
                exit;
            }
        }
        
        // Generate unique filename with sanitized original name
        $safeFilename = preg_replace("/[^a-zA-Z0-9_.-]/", "_", $_FILES['resume']['name']);
        $filename = uniqid('resume_') . '_' . $safeFilename;
        $fullPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $fullPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload resume. Please ensure the file is valid and try again.'
            ]);
            exit;
        }
        
        // Store relative path in database
        $resumePath = '/iteam-university-website/frontend/uploads/resumes/' . $filename;
    } else {
        // Handle various upload errors
        if (isset($_FILES['resume'])) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded. Please select a resume file.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder for file uploads.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            
            $errorCode = $_FILES['resume']['error'];
            $errorMessage = isset($errorMessages[$errorCode]) 
                ? $errorMessages[$errorCode] 
                : 'Unknown upload error.';
                
            echo json_encode([
                'success' => false,
                'message' => $errorMessage
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Resume file is required. Please select a PDF, DOC, or DOCX file.'
            ]);
            exit;
        }
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Modified INSERT statement to match your database schema from init_db.sql
        // Your schema has application_date as DEFAULT CURRENT_TIMESTAMP
        $query = "INSERT INTO job_applications (job_offer_id, student_id, cover_letter, resume_path, status) 
                 VALUES (:job_id, :student_id, :cover_letter, :resume_path, 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindParam(':cover_letter', $coverLetter, PDO::PARAM_STR);
        $stmt->bindParam(':resume_path', $resumePath, PDO::PARAM_STR);
        $stmt->execute();
        
        $applicationId = $conn->lastInsertId();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully! You can track its status in "My Applications".',
            'application_id' => $applicationId
        ]);
        
    } catch (PDOException $e) {
        // Roll back transaction on error
        $conn->rollBack();
        error_log("Error submitting application: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit application. Database error: ' . $e->getMessage()
        ]);
    }
    
} else {
    // GET request - retrieve applications
    $studentId = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 0;
    
    if (!$studentId) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in as a student to view your applications.'
        ]);
        exit;
    }
    
    try {
        // Modified to match the schema in init_db.sql
        // Using profile_picture instead of logo for organizations
        $query = "SELECT ja.*, jo.title AS job_title, o.name AS organization_name, o.profile_picture AS organization_logo
                  FROM job_applications ja
                  INNER JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
                  INNER JOIN organizations o ON jo.organization_id = o.organization_id
                  WHERE ja.student_id = :student_id
                  ORDER BY ja.application_date DESC";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'applications' => $applications
        ]);
    } catch (PDOException $e) {
        error_log("Error loading applications: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load applications. Database error: ' . $e->getMessage()
        ]);
    }
}
?>