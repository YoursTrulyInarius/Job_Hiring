<?php
/**
 * Update Job API
 * Allows employers to update their jobs
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        exit;
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Verify ownership
    $check = $conn->prepare("SELECT id FROM jobs WHERE id = :id AND employer_id = :employer_id");
    $check->execute([
        ':id' => $input['id'],
        ':employer_id' => $_SESSION['user_id']
    ]);

    if ($check->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied or job not found']);
        exit;
    }

    // Update query fields construction
    $allowed_fields = ['title', 'description', 'location', 'salary_range', 'job_type', 'requirements', 'status'];
    $updates = [];
    $params = [':id' => $input['id']];

    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $query = "UPDATE jobs SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($query);

    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Job updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update job']);
    }

} catch (Exception $e) {
    error_log("Update Job Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
