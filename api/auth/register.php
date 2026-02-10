<?php
/**
 * User Registration API
 * Handles new user registration with validation and password hashing
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

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $input = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data provided']);
        exit;
    }

    // Validate required fields
    $required_fields = ['email', 'password', 'full_name', 'user_type'];
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

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Validate user type
    if (!in_array($input['user_type'], ['employer', 'jobseeker'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user type']);
        exit;
    }

    // Validate password strength
    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit;
    }

    // Connect to database
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':email', $input['email']);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);

    // Insert new user
    $insert_query = "INSERT INTO users (email, password, full_name, user_type) 
                     VALUES (:email, :password, :full_name, :user_type)";
    $insert_stmt = $conn->prepare($insert_query);

    $insert_stmt->bindParam(':email', $input['email']);
    $insert_stmt->bindParam(':password', $hashed_password);
    $insert_stmt->bindParam(':full_name', $input['full_name']);
    $insert_stmt->bindParam(':user_type', $input['user_type']);

    if ($insert_stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $conn->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }

} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
