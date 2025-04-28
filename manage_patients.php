<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Fetch all patients with their latest appointment and record
$patients = $conn->query("
    SELECT 
        u.id, u.name, u.email, u.phone, u.created_at as registered_date,
        MAX(a.appointment_date) as last_appointment,
        MAX(mr.visit_date) as last_record
    FROM users u
    LEFT JOIN appointments a ON u.id = a.user_id AND a.provider_id = " . $_SESSION['user_id'] . "
    LEFT JOIN medical_records mr ON u.id = mr.user_id AND mr.provider_id = " . $_SESSION['user_id'] . "
    WHERE u.role_id = 1
    GROUP BY u.id, u.name, u.email, u.phone, u.created_at
    ORDER BY u.name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Patients - Provider Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .patient-card {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="provider_dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Manage Patients</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Last Appointment</th>
                            <th>Last Record</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($patient['name']) ?></strong><br>
                                <small class="text-muted">Registered: <?= $patient['registered_date'] ?></small>
                            </td>
                            <td>
                                <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($patient['email']) ?><br>
                                <i class="fas fa-phone me-1"></i> <?= htmlspecialchars($patient['phone']) ?>
                            </td>
                            <td>
                                <?= $patient['last_appointment'] ? $patient['last_appointment'] : 'No appointments' ?>
                            </td>
                            <td>
                                <?= $patient['last_record'] ? $patient['last_record'] : 'No records' ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_patient.php?id=<?= $patient['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="add_medical_record.php?user_id=<?= $patient['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-file-medical"></i> Add Record
                                    </a>
                                    <a href="schedule_appointment.php?user_id=<?= $patient['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-calendar-plus"></i> Schedule
                                    </a>
                                </div>
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