<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Handle record deletion
if (isset($_POST['delete_record'])) {
    $record_id = $_POST['record_id'];
    $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ? AND provider_id = ?");
    $stmt->bind_param("ii", $record_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = "Record deleted successfully!";
    } else {
        $error = "Error deleting record: " . $conn->error;
    }
}

// Fetch all medical records
$records = $conn->query("SELECT mr.*, u.name as patient_name 
    FROM medical_records mr 
    JOIN users u ON mr.user_id = u.id 
    WHERE mr.provider_id = " . $_SESSION['user_id'] . " 
    ORDER BY mr.visit_date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Medical Records - Provider Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .record-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="provider_dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        <a href="add_medical_record.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Record
        </a>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Medical Records</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Visit Date</th>
                            <th>Diagnosis</th>
                            <th>Treatment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($record = $records->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($record['patient_name']) ?></td>
                            <td><?= $record['visit_date'] ?></td>
                            <td><?= substr(htmlspecialchars($record['diagnosis']), 0, 50) ?>...</td>
                            <td><?= substr(htmlspecialchars($record['treatment']), 0, 50) ?>...</td>
                            <td>
                                <a href="view_record.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_record.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                                    <button type="submit" name="delete_record" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 