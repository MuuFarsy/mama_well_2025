<?php
include 'db_conn.php';

// Read the SQL file
$sql = file_get_contents('health_tips.sql');

// Execute the SQL commands
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "Health tips table created successfully!";
} else {
    echo "Error creating health tips table: " . $conn->error;
}

$conn->close();
?> 