<?php
include 'db_conn.php';

// Read and execute the SQL file
$sql = file_get_contents('add_status_column.sql');

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "Status column added successfully to users table!";
} else {
    echo "Error adding status column: " . $conn->error;
}

$conn->close();
?> 