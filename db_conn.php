<?php
$host = "localhost";
$username = "root";
$password = ""; // or your MySQL password
$database = "maternal_system";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
