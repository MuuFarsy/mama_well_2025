<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch user's upcoming appointments
$upcoming_appointments = $conn->query("SELECT a.*, u.name as provider_name 
    FROM appointments a 
    JOIN users u ON a.provider_id = u.id 
    WHERE a.user_id = " . $_SESSION['user_id'] . " 
    AND a.appointment_date >= CURDATE() 
    ORDER BY a.appointment_date ASC 
    LIMIT 3");

// Fetch recent health records
$health_records = $conn->query("SELECT * FROM medical_records 
    WHERE user_id = " . $_SESSION['user_id'] . " 
    ORDER BY visit_date DESC 
    LIMIT 3");

// Fetch recommended health tips
$health_tips = $conn->query("SELECT * FROM health_tips 
    ORDER BY created_at DESC 
    LIMIT 3");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard - Maternal Health</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="CSS/dashboard.css" rel="stylesheet">
</head>
<body>
<!-- Desktop Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark desktop-nav">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-heartbeat me-2"></i>Maternal Health</a>
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
                                <h3 class="card-title">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h3>
                                <p class="text-muted">Your Maternal Health Journey</p>
                            </div>
                            <div class="pregnancy-timeline">
                                <div class="timeline-marker"></div>
                                <p class="text-center mt-3">Week 24</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-3">
                <a href="schedule_appointment.php" class="btn btn-primary quick-action-btn w-100">
                    <i class="fas fa-calendar-plus fa-2x mb-2"></i><br>
                    Schedule Appointment
                </a>
            </div>
            <div class="col-md-3">
                <a href="health_tracking.php" class="btn btn-success quick-action-btn w-100">
                    <i class="fas fa-heartbeat fa-2x mb-2"></i><br>
                    Health Tracking
                </a>
            </div>
            <div class="col-md-3">
                <a href="educational_resources.php" class="btn btn-info quick-action-btn w-100">
                    <i class="fas fa-book-medical fa-2x mb-2"></i><br>
                    Educational Resources
                </a>
            </div>
            <div class="col-md-3">
                <a href="emergency_contacts.php" class="btn btn-danger quick-action-btn w-100">
                    <i class="fas fa-phone-alt fa-2x mb-2"></i><br>
                    Emergency Contacts
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Upcoming Appointments -->
            <div class="col-md-6">
                <div class="card dashboard-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Upcoming Appointments</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Provider</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($appointment['provider_name']) ?></td>
                                        <td><?= $appointment['appointment_date'] ?></td>
                                        <td><?= $appointment['appointment_time'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $appointment['status'] == 'pending' ? 'warning' : 'success' ?>">
                                                <?= $appointment['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="appointments.php" class="btn btn-outline-primary w-100">View All Appointments</a>
                    </div>
                </div>
            </div>

            <!-- Health Records -->
            <div class="col-md-6">
                <div class="card dashboard-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Recent Health Records</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Diagnosis</th>
                                        <th>Provider</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while($record = $health_records->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $record['visit_date'] ?></td>
                                        <td><?= substr(htmlspecialchars($record['diagnosis']), 0, 50) ?>...</td>
                                        <td><?= htmlspecialchars($record['provider_id']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="medical_records.php" class="btn btn-outline-primary w-100">View All Records</a>
                    </div>
                </div>
            </div>

            <!-- Health Tips -->
            <div class="col-md-12">
                <div class="card dashboard-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Recommended Health Tips</h4>
                        <div class="row">
                            <?php while($tip = $health_tips->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="health-tracker-card">
                                    <h5><?= htmlspecialchars($tip['title']) ?></h5>
                                    <p><?= substr(htmlspecialchars($tip['content']), 0, 100) ?>...</p>
                                    <a href="health_tip.php?id=<?= $tip['id'] ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <a href="educational_resources.php" class="btn btn-outline-primary w-100">View All Resources</a>
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
            <a href="health_tracking.php" class="mobile-nav-item">
                <i class="fas fa-heartbeat"></i>
                <div>Health</div>
            </a>
        </div>
        <div class="col-3">
            <a href="educational_resources.php" class="mobile-nav-item">
                <i class="fas fa-book-medical"></i>
                <div>Resources</div>
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