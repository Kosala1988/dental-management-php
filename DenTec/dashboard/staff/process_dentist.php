<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_dentist') {
        // Validate input
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }
        
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        // Check for duplicate username
        $username_check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $username_check->bind_param("s", $username);
        $username_check->execute();
        if ($username_check->get_result()->num_rows > 0) {
            $errors[] = "Username already exists.";
        }
        
        // Check for duplicate email
        $email_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $email_check->bind_param("s", $email);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $errors[] = "Email address already exists.";
        }
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new dentist
        $insert_query = "INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'dentist', ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssssi", $username, $password_hash, $full_name, $email, $is_active);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dentist added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add dentist. Please try again.']);
        }
        
    } elseif ($action === 'toggle_status') {
        $dentist_id = intval($_POST['dentist_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        if ($dentist_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid dentist ID.']);
            exit();
        }
        
        $update_query = "UPDATE users SET is_active = ? WHERE user_id = ? AND role = 'dentist'";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $is_active, $dentist_id);
        
        if ($stmt->execute()) {
            $status_text = $is_active ? 'activated' : 'deactivated';
            echo json_encode(['success' => true, 'message' => "Dentist {$status_text} successfully!"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update dentist status.']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>