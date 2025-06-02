<?php
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

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get job offers with filtering and pagination or get a specific job
        if (isset($_GET['job_offer_id'])) {
            getJobById($conn, $organizationId, $_GET['job_offer_id']);
        } else {
            getJobs($conn, $organizationId);
        }
        break;
    case 'POST':
        // Create a new job offer
        createJob($conn, $organizationId);
        break;
    case 'PUT':
        // Update existing job offer
        updateJob($conn, $organizationId);
        break;
    case 'DELETE':
        // Delete a job offer
        deleteJob($conn, $organizationId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Function to get job offers with filtering and pagination
function getJobs($conn, $organizationId) {
    try {
        // Get pagination parameters
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        // Get filter parameters
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Base query
        $baseQuery = "FROM job_offers WHERE organization_id = :organization_id";
        $countQuery = "SELECT COUNT(*) AS total " . $baseQuery;
        $dataQuery = "SELECT job_offer_id, organization_id, title, description, posted_date, 
                      job_type, expiry_date, status, created_at, updated_at,
                      (SELECT COUNT(*) FROM job_applications WHERE job_offer_id = job_offers.job_offer_id) AS application_count " . $baseQuery;
        
        // Add filters
        $params = [':organization_id' => $organizationId];
        
        if ($filter !== 'all') {
            switch ($filter) {
                case 'active':
                    $baseQuery .= " AND status = 'active' AND expiry_date >= CURDATE()";
                    break;
                case 'draft':
                    $baseQuery .= " AND status = 'draft'";
                    break;
                case 'expired':
                    $baseQuery .= " AND (status = 'expired' OR (status = 'active' AND expiry_date < CURDATE()))";
                    break;
                case 'filled':
                    $baseQuery .= " AND status = 'filled'";
                    break;
                case 'full-time':
                case 'part-time':
                case 'internship':
                case 'contract':
                    $baseQuery .= " AND job_type = :job_type";
                    $params[':job_type'] = $filter;
                    break;
            }
        }
        
        // Add search
        if (!empty($search)) {
            $baseQuery .= " AND (title LIKE :search OR description LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Update queries with filters
        $countQuery = "SELECT COUNT(*) AS total " . $baseQuery;
        $dataQuery = "SELECT job_offer_id, organization_id, title, description, posted_date, 
                      job_type, expiry_date, status, created_at, updated_at,
                      (SELECT COUNT(*) FROM job_applications WHERE job_offer_id = job_offers.job_offer_id) AS application_count " . $baseQuery;
        
        // Add order and limit
        $dataQuery .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        // Get total count
        $stmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get jobs data with pagination
        $stmt = $conn->prepare($dataQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get organization email for job contact
        $orgStmt = $conn->prepare("SELECT email FROM organizations WHERE organization_id = :organization_id");
        $orgStmt->bindParam(':organization_id', $organizationId);
        $orgStmt->execute();
        $orgData = $orgStmt->fetch(PDO::FETCH_ASSOC);
        $orgEmail = $orgData ? $orgData['email'] : '';
        
        // Add organization email to each job
        foreach ($jobs as &$job) {
            $job['contact_email'] = $orgEmail;
        }
        
        // Calculate total pages
        $totalPages = ceil($totalCount / $limit);
        
        echo json_encode([
            'success' => true,
            'jobs' => $jobs,
            'total' => $totalCount,
            'pages' => $totalPages,
            'current_page' => $page
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to get a single job by ID
function getJobById($conn, $organizationId, $jobId) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM job_offers 
            WHERE job_offer_id = :job_offer_id AND organization_id = :organization_id
        ");
        $stmt->bindParam(':job_offer_id', $jobId);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($job) {
            // Get application count
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS count 
                FROM job_applications 
                WHERE job_offer_id = :job_offer_id
            ");
            $stmt->bindParam(':job_offer_id', $jobId);
            $stmt->execute();
            
            $applicationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $job['application_count'] = $applicationCount;
            
            // Get organization email
            $orgStmt = $conn->prepare("SELECT email FROM organizations WHERE organization_id = :organization_id");
            $orgStmt->bindParam(':organization_id', $organizationId);
            $orgStmt->execute();
            $orgData = $orgStmt->fetch(PDO::FETCH_ASSOC);
            $job['contact_email'] = $orgData ? $orgData['email'] : '';
            
            echo json_encode(['success' => true, 'job' => $job]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Job not found or unauthorized access']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to create a new job offer
function createJob($conn, $organizationId) {
    try {
        // Get JSON data from request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid request data']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['title', 'description', 'job_type', 'expiry_date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Set default status if not provided
        $status = isset($data['status']) ? $data['status'] : 'active';
        
        // Insert job offer
        $stmt = $conn->prepare("
            INSERT INTO job_offers (
                organization_id, title, description, job_type, 
                expiry_date, status, created_at, updated_at
            ) VALUES (
                :organization_id, :title, :description, :job_type,
                :expiry_date, :status, NOW(), NOW()
            )
        ");
        
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':job_type', $data['job_type']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        $stmt->bindParam(':status', $status);
        
        $stmt->execute();
        $jobId = $conn->lastInsertId();
        
        echo json_encode(['success' => true, 'message' => 'Job offer created successfully', 'job_offer_id' => $jobId]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to update existing job offer
function updateJob($conn, $organizationId) {
    try {
        // Get JSON data from request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['job_offer_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request data or missing job ID']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['title', 'description', 'job_type', 'expiry_date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Verify job belongs to organization
        $stmt = $conn->prepare("
            SELECT job_offer_id FROM job_offers 
            WHERE job_offer_id = :job_offer_id AND organization_id = :organization_id
        ");
        $stmt->bindParam(':job_offer_id', $data['job_offer_id']);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Job not found or unauthorized access']);
            return;
        }
        
        // Update job offer
        $stmt = $conn->prepare("
            UPDATE job_offers SET 
                title = :title,
                description = :description,
                job_type = :job_type,
                expiry_date = :expiry_date,
                status = :status,
                updated_at = NOW()
            WHERE job_offer_id = :job_offer_id AND organization_id = :organization_id
        ");
        
        $stmt->bindParam(':job_offer_id', $data['job_offer_id']);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':job_type', $data['job_type']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        $stmt->bindParam(':status', $data['status']);
        
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Job offer updated successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to delete a job offer
function deleteJob($conn, $organizationId) {
    try {
        $jobId = isset($_GET['job_offer_id']) ? $_GET['job_offer_id'] : null;
        
        if (!$jobId) {
            echo json_encode(['success' => false, 'message' => 'Job ID is required']);
            return;
        }
        
        // Verify job belongs to organization
        $stmt = $conn->prepare("
            SELECT job_offer_id FROM job_offers 
            WHERE job_offer_id = :job_offer_id AND organization_id = :organization_id
        ");
        $stmt->bindParam(':job_offer_id', $jobId);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Job not found or unauthorized access']);
            return;
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete associated applications first
        $stmt = $conn->prepare("
            DELETE FROM job_applications 
            WHERE job_offer_id = :job_offer_id
        ");
        $stmt->bindParam(':job_offer_id', $jobId);
        $stmt->execute();
        
        // Delete the job offer
        $stmt = $conn->prepare("
            DELETE FROM job_offers 
            WHERE job_offer_id = :job_offer_id AND organization_id = :organization_id
        ");
        $stmt->bindParam(':job_offer_id', $jobId);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Job offer and related applications deleted successfully']);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>