<?php
/**
 * List Applications API
 * Fetches applications based on user role
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($_SESSION['user_type'] === 'employer') {
        // For employers: fetches applications for their jobs
        // Optional job_id filter
        $query = "SELECT a.*, j.title as job_title, u.full_name as applicant_name, u.email as applicant_email, u.phone 
                  FROM applications a 
                  JOIN jobs j ON a.job_id = j.id 
                  JOIN users u ON a.applicant_id = u.id 
                  WHERE j.employer_id = :user_id";
        
        $params = [':user_id' => $_SESSION['user_id']];

        if (isset($_GET['job_id'])) {
            $query .= " AND a.job_id = :job_id";
            $params[':job_id'] = $_GET['job_id'];
        }

        $query .= " ORDER BY a.applied_at DESC";

    } else {
        // For job seekers: fetch their submitted applications
        $query = "SELECT a.*, j.title as job_title, j.location, u.full_name as employer_name 
                  FROM applications a 
                  JOIN jobs j ON a.job_id = j.id 
                  JOIN users u ON j.employer_id = u.id 
                  WHERE a.applicant_id = :user_id 
                  ORDER BY a.applied_at DESC";
        
        $params = [':user_id' => $_SESSION['user_id']];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($applications),
        'data' => $applications
    ]);

} catch (Exception $e) {
    error_log("List Applications Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
