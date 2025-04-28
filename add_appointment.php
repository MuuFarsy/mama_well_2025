<?php
session_start();
include 'db_conn.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

// Validate and sanitize input
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$provider_id = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);
$appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
$appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);

// Validate required fields
if (!$user_id || !$provider_id || !$appointment_date || !$appointment_time) {
    header("Location: manage_appointments.php?error=All fields are required");
    exit();
}

// Validate date format and ensure it's not in the past
$appointment_datetime = strtotime($appointment_date . ' ' . $appointment_time);
if ($appointment_datetime < time()) {
    header("Location: manage_appointments.php?error=Appointment date and time must be in the future");
    exit();
}

// Check if the user exists and is a patient
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role_id = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
if (!$stmt->get_result()->num_rows) {
    header("Location: manage_appointments.php?error=Invalid patient selected");
    exit();
}

// Check if the provider exists and is a healthcare provider
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role_id = 2");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
if (!$stmt->get_result()->num_rows) {
    header("Location: manage_appointments.php?error=Invalid healthcare provider selected");
    exit();
}

// Check for scheduling conflicts
$stmt = $conn->prepare("
    SELECT id FROM appointments 
    WHERE provider_id = ? 
    AND appointment_date = ? 
    AND appointment_time = ? 
    AND status != 'cancelled'
");
$stmt->bind_param("iss", $provider_id, $appointment_date, $appointment_time);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: manage_appointments.php?error=Time slot already booked");
    exit();
}

// Insert the new appointment
$stmt = $conn->prepare("
    INSERT INTO appointments (user_id, provider_id, appointment_date, appointment_time, status) 
    VALUES (?, ?, ?, ?, 'pending')
");
$stmt->bind_param("iiss", $user_id, $provider_id, $appointment_date, $appointment_time);

if ($stmt->execute()) {
    // Log the activity
    $admin_id = $_SESSION['user_id'];
    $action = "Created new appointment for patient ID: " . $user_id . " with provider ID: " . $provider_id;
    $stmt = $conn->prepare("INSERT INTO system_activity (admin_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $admin_id, $action);
    $stmt->execute();
    
    header("Location: manage_appointments.php?success=Appointment created successfully");
} else {
    header("Location: manage_appointments.php?error=Failed to create appointment");
}

$conn->close();
?> 