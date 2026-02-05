<?php
/**
 * Fetch Jobs API
 * Fetches all jobs with optional filtering
 * Located in api/ root to avoid folder permission issues
 */

require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Build query with filters
    $query = "SELECT j.*, u.full_name as employer_name, u.profile_picture 
              FROM jobs j 
              JOIN users u ON j.employer_id = u.id 
              WHERE j.status = 'active'";
    
    $params = [];

    if (isset($_GET['job_type']) && !empty($_GET['job_type'])) {
        $query .= " AND j.job_type = :job_type";
        $params[':job_type'] = $_GET['job_type'];
    }

    if (isset($_GET['location']) && !empty($_GET['location'])) {
        $query .= " AND j.location LIKE :location";
        $params[':location'] = '%' . $_GET['location'] . '%';
    }

    $query .= " ORDER BY j.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $jobs = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($jobs),
        'data' => $jobs
    ]);

} catch (Exception $e) {
    error_log("Fetch Jobs Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
