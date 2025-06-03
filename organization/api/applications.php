<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/organization/api/applications.php

// Include database connection
require_once 'db_connection.php';
session_start();

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is authenticated and is an organization
if (!isset($_SESSION['account_id']) || $_SESSION['account_type'] !== 'organization') {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Get organization ID from session
$organizationId = $_SESSION['organization_id'];

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Export to CSV if requested
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            exportApplicationsCSV($conn, $organizationId);
            exit();
        }
        
        // Fetch applications
        if (isset($_GET['application_id'])) {
            // Get a single application
            getApplicationById($conn, $organizationId, $_GET['application_id']);
        } else {
            // Get all applications for the organization
            getApplications($conn, $organizationId);
        }
        break;
        
    case 'PUT':
        // Update application status
        updateApplicationStatus($conn, $organizationId);
        break;
        
    case 'DELETE':
        // Delete application
        if (isset($_GET['application_id'])) {
            deleteApplication($conn, $organizationId, $_GET['application_id']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Application ID is required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Function to get all applications for an organization
function getApplications($conn, $organizationId) {
    try {
        // Get query parameters for pagination and filtering
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        $jobId = isset($_GET['job_id']) && $_GET['job_id'] !== 'undefined' && $_GET['job_id'] !== 'null' 
            ? intval($_GET['job_id']) 
            : null;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Build query conditions
        $conditions = ["jo.organization_id = :organization_id"];
        $params = [':organization_id' => $organizationId];
        
        if ($jobId) {
            $conditions[] = "ja.job_offer_id = :job_id";
            $params[':job_id'] = $jobId;
        }
        
        if ($status && $status !== 'all') {
            $conditions[] = "ja.status = :status";
            $params[':status'] = $status;
        }
        
        if ($search) {
            $conditions[] = "(s.first_name LIKE :search OR s.last_name LIKE :search OR s.email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Count total applications matching the filters
        $countQuery = "
            SELECT COUNT(*) as total
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            JOIN students s ON ja.student_id = s.student_id
            WHERE $whereClause
        ";
        
        $stmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalApplications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get applications data with pagination - FIX COLUMN NAMES HERE
        $query = "
            SELECT 
                ja.application_id,
                ja.job_offer_id,
                ja.student_id,
                ja.cover_letter,
                ja.resume_path,
                ja.status,
                ja.notes,
                ja.application_date,
                ja.created_at,
                ja.updated_at,
                jo.title as job_title,
                s.first_name,
                s.last_name,
                s.email,
                s.profile_picture
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            JOIN students s ON ja.student_id = s.student_id
            WHERE $whereClause
            ORDER BY ja.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for the frontend
        foreach ($applications as &$app) {
            $app['resume_url'] = $app['resume_path']; // Maintain compatibility with frontend
            $app['profile_image_url'] = $app['profile_picture']; // Maintain compatibility with frontend
        }
        
        // Get application statistics
        $statsQuery = "
            SELECT 
                COUNT(*) as total_applications,
                SUM(CASE WHEN ja.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                SUM(CASE WHEN ja.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_applications,
                SUM(CASE WHEN ja.status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
                SUM(CASE WHEN ja.status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            WHERE jo.organization_id = :organization_id
        ";
        
        $stmt = $conn->prepare($statsQuery);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get all job offers for the filter dropdown
        $jobsQuery = "
            SELECT job_offer_id, title
            FROM job_offers 
            WHERE organization_id = :organization_id
            ORDER BY title
        ";
        
        $stmt = $conn->prepare($jobsQuery);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the response
        $response = [
            'success' => true,
            'applications' => $applications,
            'stats' => $stats,
            'jobs' => $jobs,
            'pagination' => [
                'total' => intval($totalApplications),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalApplications / $limit)
            ]
        ];
        
        echo json_encode($response);
        
    } catch (PDOException $e) {
        error_log("Database error in getApplications: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to get a single application by ID
function getApplicationById($conn, $organizationId, $applicationId) {
    try {
        // Fix column names here too
        $query = "
            SELECT 
                ja.application_id,
                ja.job_offer_id,
                ja.student_id,
                ja.cover_letter,
                ja.resume_path,
                ja.status,
                ja.notes,
                ja.application_date,
                ja.created_at,
                ja.updated_at,
                jo.title as job_title,
                jo.description as job_description,
                s.first_name,
                s.last_name,
                s.email,
                s.profile_picture,
                s.created_at as student_created_at,
                s.status as student_status
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            JOIN students s ON ja.student_id = s.student_id
            WHERE ja.application_id = :application_id
            AND jo.organization_id = :organization_id
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Application not found or you do not have permission to view it']);
            exit();
        }
        
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add fields expected by frontend
        $application['resume_url'] = $application['resume_path'];
        $application['profile_image_url'] = $application['profile_picture'];
        
        echo json_encode(['success' => true, 'application' => $application]);
        
    } catch (PDOException $e) {
        error_log("Database error in getApplicationById: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to update application status
function updateApplicationStatus($conn, $organizationId) {
    try {
        // Get JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if application ID is provided
        if (!isset($data['application_id']) || empty($data['application_id'])) {
            echo json_encode(['success' => false, 'message' => 'Application ID is required']);
            exit();
        }
        
        // Check if application belongs to this organization
        $stmt = $conn->prepare("
            SELECT ja.application_id
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            WHERE ja.application_id = :application_id
            AND jo.organization_id = :organization_id
        ");
        $stmt->bindParam(':application_id', $data['application_id']);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Application not found or you do not have permission to update it']);
            exit();
        }
        
        // Update the application status and notes
        $status = isset($data['status']) ? $data['status'] : null;
        $notes = isset($data['notes']) ? $data['notes'] : null;
        
        $updateFields = [];
        $params = [
            ':application_id' => $data['application_id']
        ];
        
        if ($status) {
            // Validate that status is one of the allowed enum values
            $validStatuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'accepted'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status value']);
                exit();
            }
            
            $updateFields[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if ($notes !== null) {
            $updateFields[] = "notes = :notes";
            $params[':notes'] = $notes;
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            exit();
        }
        
        $updateQuery = "
            UPDATE job_applications
            SET " . implode(", ", $updateFields) . ",
                updated_at = NOW()
            WHERE application_id = :application_id
        ";
        
        $stmt = $conn->prepare($updateQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Application updated successfully',
            'application_id' => $data['application_id']
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in updateApplicationStatus: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to delete an application
function deleteApplication($conn, $organizationId, $applicationId) {
    try {
        // Check if application belongs to this organization
        $stmt = $conn->prepare("
            SELECT ja.application_id
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            WHERE ja.application_id = :application_id
            AND jo.organization_id = :organization_id
        ");
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Application not found or you do not have permission to delete it']);
            exit();
        }
        
        // Delete the application
        $stmt = $conn->prepare("DELETE FROM job_applications WHERE application_id = :application_id");
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Application deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error in deleteApplication: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to export applications as CSV
function exportApplicationsCSV($conn, $organizationId) {
    try {
        $jobId = isset($_GET['job_id']) ? $_GET['job_id'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Build query conditions
        $conditions = ["jo.organization_id = :organization_id"];
        $params = [':organization_id' => $organizationId];
        
        if ($jobId && $jobId !== 'all') {
            $conditions[] = "ja.job_offer_id = :job_id";
            $params[':job_id'] = $jobId;
        }
        
        if ($status && $status !== 'all') {
            $conditions[] = "ja.status = :status";
            $params[':status'] = $status;
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Get applications data
        $query = "
            SELECT 
                ja.application_id,
                jo.title as job_title,
                s.first_name,
                s.last_name,
                s.email,
                ja.status,
                ja.application_date,
                ja.updated_at
            FROM job_applications ja
            JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
            JOIN students s ON ja.student_id = s.student_id
            WHERE $whereClause
            ORDER BY ja.created_at DESC
        ";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="applications_export_' . date('Y-m-d') . '.csv"');
        
        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        
        // Write the column headers
        fputcsv($output, [
            'Application ID', 
            'Job Title', 
            'First Name', 
            'Last Name', 
            'Email', 
            'Status', 
            'Applied On', 
            'Last Updated'
        ]);
        
        // Write each row of data
        foreach ($applications as $application) {
            fputcsv($output, $application);
        }
        
        fclose($output);
        exit();
        
    } catch (PDOException $e) {
        error_log("Database error in exportApplicationsCSV: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}
?>