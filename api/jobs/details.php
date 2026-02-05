<?php
/**
 * Job Details API
 * Fetches single job details
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $query = "SELECT j.*, u.full_name as employer_name, u.email as employer_email 
              FROM jobs j 
              JOIN users u ON j.employer_id = u.id 
              WHERE j.id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }

    $job = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => $job
    ]);

} catch (Exception $e) {
    error_log("Job Details Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
