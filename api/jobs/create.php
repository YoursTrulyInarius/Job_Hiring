<?php
/**
 * Create Job API
 * Allows employers to post new jobs
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Only accept POST requests
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['title', 'description', 'location', 'job_type'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Connect to database
    $database = new Database();
    $conn = $database->getConnection();

    // Insert job
    $query = "INSERT INTO jobs (employer_id, title, description, location, salary_range, job_type, requirements) 
              VALUES (:employer_id, :title, :description, :location, :salary_range, :job_type, :requirements)";
    
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(':employer_id', $_SESSION['user_id']);
    $stmt->bindParam(':title', $input['title']);
    $stmt->bindParam(':description', $input['description']);
    $stmt->bindParam(':location', $input['location']);
    
    $salary = $input['salary_range'] ?? null;
    $stmt->bindParam(':salary_range', $salary);
    
    $stmt->bindParam(':job_type', $input['job_type']);
    
    $requirements = $input['requirements'] ?? null;
    $stmt->bindParam(':requirements', $requirements);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Job posted successfully',
            'job_id' => $conn->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to post job']);
    }

} catch (Exception $e) {
    error_log("Post Job Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
