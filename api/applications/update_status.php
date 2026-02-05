<?php
/**
 * Update Application Status API
 * Allows employers to accept/reject applications
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id']) || empty($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Application ID and status are required']);
        exit;
    }

    if (!in_array($input['status'], ['pending', 'reviewed', 'accepted', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Verify ownership (the application belongs to a job posted by this employer)
    $check = $conn->prepare("
        SELECT a.id 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE a.id = :id AND j.employer_id = :employer_id
    ");

    $check->execute([
        ':id' => $input['id'],
        ':employer_id' => $_SESSION['user_id']
    ]);

    if ($check->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied or application not found']);
        exit;
    }

    // Update status
    $update = $conn->prepare("UPDATE applications SET status = :status WHERE id = :id");
    
    if ($update->execute([':status' => $input['status'], ':id' => $input['id']])) {
        echo json_encode(['success' => true, 'message' => 'Application status updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

} catch (Exception $e) {
    error_log("Update Application Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
