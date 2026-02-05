<?php
/**
 * Submit Application API
 * Handles job application submission with resume upload
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
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only job seekers can apply.']);
    exit;
}

try {
    // Check if files and post data exist
    if (empty($_POST['job_id']) || empty($_FILES['resume'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID and resume are required']);
        exit;
    }

    $job_id = $_POST['job_id'];
    $file = $_FILES['resume'];

    // Database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Check if already applied
    $check = $conn->prepare("SELECT id FROM applications WHERE job_id = :job_id AND applicant_id = :applicant_id");
    $check->execute([
        ':job_id' => $job_id,
        ':applicant_id' => $_SESSION['user_id']
    ]);

    if ($check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
        exit;
    }

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error code: ' . $file['error']);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File is too large. Max size is 5MB']);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_FILE_TYPES)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX']);
        exit;
    }

    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid() . '_' . $_SESSION['user_id'] . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    
    // Relative path for database
    $db_path = 'assets/uploads/resumes/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Insert application record
        $query = "INSERT INTO applications (job_id, applicant_id, resume_path, cover_letter) 
                  VALUES (:job_id, :applicant_id, :resume_path, :cover_letter)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':job_id', $job_id);
        $stmt->bindValue(':applicant_id', $_SESSION['user_id']);
        $stmt->bindValue(':resume_path', $db_path);
        
        $cover_letter = $_POST['cover_letter'] ?? null;
        $stmt->bindValue(':cover_letter', $cover_letter);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
        } else {
            // Remove uploaded file if database insert fails
            unlink($filepath);
            throw new Exception('Database insert failed');
        }
    } else {
        throw new Exception('Failed to move uploaded file');
    }

} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred submitting application']);
}
