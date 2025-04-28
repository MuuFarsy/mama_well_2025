<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Fetch provider's upcoming appointments
$upcoming_appointments = $conn->query("SELECT a.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.provider_id = " . $_SESSION['user_id'] . " 
    AND a.appointment_date >= CURDATE() 
    ORDER BY a.appointment_date ASC, a.appointment_time ASC");

// Fetch recent medical records
$recent_records = $conn->query("SELECT mr.*, u.name as patient_name 
    FROM medical_records mr 
    JOIN users u ON mr.user_id = u.id 
    WHERE mr.provider_id = " . $_SESSION['user_id'] . " 
    ORDER BY mr.visit_date DESC 
    LIMIT 5");

// Fetch total patients
$total_patients = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM appointments WHERE provider_id = " . $_SESSION['user_id'])->fetch_assoc()['count'];

// Fetch today's appointments
$today_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments 
    WHERE provider_id = " . $_SESSION['user_id'] . " 
    AND appointment_date = CURDATE()")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Healthcare Provider Dashboard - Maternal Health</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <style>
        .appointment-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 10px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
<!-- Desktop Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark desktop-nav">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-user-md me-2"></i>Provider Dashboard</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="profile.php" class="nav-link"><i class="fas fa-user me-1"></i>Profile</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title">Welcome, Dr. <?= htmlspecialchars($_SESSION['name']) ?></h3>
                                <p class="text-muted">Your Maternal Health Practice</p>
                            </div>
                            <div class="text-end">
                                <h5>Today's Appointments</h5>
                                <h2 class="mb-0"><?= $today_appointments ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Patients</h5>
                        <h2 class="mb-0"><?= $total_patients ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming Appointments</h5>
                        <h2 class="mb-0"><?= $upcoming_appointments->num_rows ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Records</h5>
                        <h2 class="mb-0"><?= $recent_records->num_rows ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Upcoming Appointments -->
            <div class="col-md-6">
                <div class="card dashboard-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Upcoming Appointments</h4>
                            <a href="manage_appointments.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($appointment['patient_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($appointment['patient_email']) ?></small>
                                        </td>
                                        <td><?= $appointment['appointment_date'] ?></td>
                                        <td><?= $appointment['appointment_time'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $appointment['status'] == 'pending' ? 'warning' : 'success' ?>">
                                                <?= $appointment['status'] ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-success" onclick="confirmAppointment(<?= $appointment['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?= $appointment['id'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <a href="add_medical_record.php?appointment_id=<?= $appointment['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-file-medical"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Medical Records -->
            <div class="col-md-6">
                <div class="card dashboard-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Recent Medical Records</h4>
                            <a href="manage_records.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Date</th>
                                        <th>Diagnosis</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while($record = $recent_records->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['patient_name']) ?></td>
                                        <td><?= $record['visit_date'] ?></td>
                                        <td><?= substr(htmlspecialchars($record['diagnosis']), 0, 50) ?>...</td>
                                        <td>
                                            <a href="view_record.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_record.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h4 class="card-title">Quick Actions</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <a href="add_medical_record.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-file-medical me-2"></i>Add Medical Record
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_appointments.php" class="btn btn-success w-100 mb-2">
                                    <i class="fas fa-calendar-check me-2"></i>Manage Appointments
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_patients.php" class="btn btn-info w-100 mb-2">
                                    <i class="fas fa-users me-2"></i>Manage Patients
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="health_tips.php" class="btn btn-warning w-100 mb-2">
                                    <i class="fas fa-heartbeat me-2"></i>Health Tips
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav mobile-nav">
    <div class="row text-center">
        <div class="col-3">
            <a href="#" class="mobile-nav-item active">
                <i class="fas fa-home"></i>
                <div>Home</div>
            </a>
        </div>
        <div class="col-3">
            <a href="manage_appointments.php" class="mobile-nav-item">
                <i class="fas fa-calendar-check"></i>
                <div>Appointments</div>
            </a>
        </div>
        <div class="col-3">
            <a href="manage_records.php" class="mobile-nav-item">
                <i class="fas fa-file-medical"></i>
                <div>Records</div>
            </a>
        </div>
        <div class="col-3">
            <a href="profile.php" class="mobile-nav-item">
                <i class="fas fa-user"></i>
                <div>Profile</div>
            </a>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmAppointment(id) {
        if (confirm('Are you sure you want to confirm this appointment?')) {
            window.location.href = 'confirm_appointment.php?id=' + id;
        }
    }

    function cancelAppointment(id) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            window.location.href = 'cancel_appointment.php?id=' + id;
        }
    }

    // Add active class to current nav item
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.mobile-nav-item');
        
        navItems.forEach(item => {
            if (item.getAttribute('href') === currentPath) {
                item.classList.add('active');
            }
        });
    });
</script>
</body>
</html>
