<?php
include 'db_conn.php';

// Read the SQL file
$sql = file_get_contents('health_tracking.sql');

// Execute the SQL
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "Health tracking table created successfully!";
} else {
    echo "Error creating health tracking table: " . $conn->error;
}

$conn->close();
?> 