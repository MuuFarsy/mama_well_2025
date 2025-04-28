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
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_id = (int)$_POST['role_id'];
    $password = $_POST['password'];
    
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
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssssi", $name, $email, $phone, $hashed_password, $role_id);
        
        if ($stmt->execute()) {
            // Log the activity
            $admin_id = $_SESSION['user_id'];
            $action = "Added new user: " . $name;
            $stmt = $conn->prepare("INSERT INTO system_activity (admin_id, action) VALUES (?, ?)");
            $stmt->bind_param("is", $admin_id, $action);
            $stmt->execute();
            
            header("Location: manage_users.php?success=User added successfully");
            exit();
        } else {
            $errors[] = "Error adding user: " . $conn->error;
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