<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_records.php");
    exit();
}

$record_id = $_GET['id'];

// Fetch record details
$stmt = $conn->prepare("SELECT mr.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone 
    FROM medical_records mr 
    JOIN users u ON mr.user_id = u.id 
    WHERE mr.id = ? AND mr.provider_id = ?");
$stmt->bind_param("ii", $record_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_records.php");
    exit();
}

$record = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Medical Record - Provider Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .record-section {
            margin-bottom: 2rem;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="provider_dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        <div>
            <a href="edit_record.php?id=<?= $record_id ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-2"></i>Edit Record
            </a>
            <a href="manage_records.php" class="btn btn-secondary">
                <i class="fas fa-list me-2"></i>All Records
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Medical Record Details</h4>
        </div>
        <div class="card-body">
            <!-- Patient Information -->
            <div class="record-section">
                <h5>Patient Information</h5>
                <div class="patient-info">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Name:</strong><br>
                            <?= htmlspecialchars($record['patient_name']) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Email:</strong><br>
                            <?= htmlspecialchars($record['patient_email']) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Phone:</strong><br>
                            <?= htmlspecialchars($record['patient_phone']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Information -->
            <div class="record-section">
                <h5>Visit Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Visit Date:</strong><br>
                        <?= $record['visit_date'] ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Record Created:</strong><br>
                        <?= $record['created_at'] ?>
                    </div>
                </div>
            </div>

            <!-- Diagnosis -->
            <div class="record-section">
                <h5>Diagnosis</h5>
                <div class="p-3 bg-light rounded">
                    <?= nl2br(htmlspecialchars($record['diagnosis'])) ?>
                </div>
            </div>

            <!-- Treatment -->
            <div class="record-section">
                <h5>Treatment</h5>
                <div class="p-3 bg-light rounded">
                    <?= nl2br(htmlspecialchars($record['treatment'])) ?>
                </div>
            </div>

            <!-- Notes -->
            <?php if ($record['notes']): ?>
            <div class="record-section">
                <h5>Additional Notes</h5>
                <div class="p-3 bg-light rounded">
                    <?= nl2br(htmlspecialchars($record['notes'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 