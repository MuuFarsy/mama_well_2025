<?php
session_start();
include 'db_conn.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_id = (int)$_POST['role_id'];
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    if (empty($errors)) {
        // Update user
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $email, $phone, $role_id, $user_id);
        
        if ($stmt->execute()) {
            // Log the activity
            $admin_id = $_SESSION['user_id'];
            $action = "Updated user information for ID: " . $user_id;
            $stmt = $conn->prepare("INSERT INTO system_activity (admin_id, action) VALUES (?, ?)");
            $stmt->bind_param("is", $admin_id, $action);
            $stmt->execute();
            
            header("Location: manage_users.php?success=User updated successfully");
            exit();
        } else {
            $errors[] = "Error updating user: " . $conn->error;
        }
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $error_string = implode(",", $errors);
        header("Location: manage_users.php?error=" . urlencode($error_string));
        exit();
    }
} else {
    header("Location: manage_users.php");
    exit();
}
?> 