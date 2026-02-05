<?php
/**
 * Update Profile API
 * Allows users to update their profile picture
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP allowed.');
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception('File size too large (max 5MB)');
    }

    // Create unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
    $uploadDir = '../../assets/uploads/profile_pictures/';
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save file');
    }

    // Update database
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("UPDATE users SET profile_picture = :pic WHERE id = :id");
    $stmt->execute([
        ':pic' => $filename,
        ':id' => $_SESSION['user_id']
    ]);

    // Return new user data (mocking existing session data updated)
    $profileUrl = BASE_URL . '/assets/uploads/profile_pictures/' . $filename;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully',
        'profile_picture' => $filename,
        'profile_picture_url' => $profileUrl
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
