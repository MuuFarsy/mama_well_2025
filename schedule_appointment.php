<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $provider_id = $_POST['provider_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = $_POST['reason'];
    
    $stmt = $conn->prepare("INSERT INTO appointments (user_id, provider_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $user_id, $provider_id, $appointment_date, $appointment_time);
    
    if ($stmt->execute()) {
        $success_message = "Appointment scheduled successfully!";
    } else {
        $error_message = "Error scheduling appointment. Please try again.";
    }
}

// Fetch available healthcare providers
$providers = $conn->query("SELECT * FROM users WHERE role_id = 2 ORDER BY name");

// Fetch user's upcoming appointments
$upcoming_appointments = $conn->query("SELECT a.*, u.name as provider_name 
    FROM appointments a 
    JOIN users u ON a.provider_id = u.id 
    WHERE a.user_id = " . $_SESSION['user_id'] . " 
    AND a.appointment_date >= CURDATE() 
    ORDER BY a.appointment_date ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule Appointment - Maternal Health</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <style>
        .provider-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .provider-card:hover {
            transform: translateY(-5px);
        }
        .provider-card.selected {
            border: 2px solid #0d6efd;
        }
        .appointment-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<!-- Desktop Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark desktop-nav">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-calendar-plus me-2"></i>Schedule Appointment</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-home me-1"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link"><i class="fas fa-user me-1"></i>Profile</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Healthcare Providers -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h4 class="card-title">Select Healthcare Provider</h4>
                        <div class="row">
                            <?php while($provider = $providers->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="provider-card" onclick="selectProvider(<?= $provider['id'] ?>, '<?= htmlspecialchars($provider['name']) ?>')">
                                        <h5><?= htmlspecialchars($provider['name']) ?></h5>
                                        <p class="text-muted"><?= htmlspecialchars($provider['email']) ?></p>
                                        <p class="text-muted"><?= htmlspecialchars($provider['phone']) ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment Form -->
        <div class="row">
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h4 class="card-title">Schedule New Appointment</h4>
                        <form method="POST" class="appointment-form">
                            <input type="hidden" name="provider_id" id="provider_id">
                            <div class="mb-3">
                                <label class="form-label">Selected Provider</label>
                                <input type="text" class="form-control" id="provider_name" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Appointment Date</label>
                                <input type="date" class="form-control" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Appointment Time</label>
                                <input type="time" class="form-control" name="appointment_time" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Visit</label>
                                <textarea class="form-control" name="reason" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="scheduleBtn" disabled>Schedule Appointment</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h4 class="card-title">Your Upcoming Appointments</h4>
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
            <a href="patient_dashboard.php" class="mobile-nav-item">
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
    function selectProvider(id, name) {
        // Remove selected class from all cards
        document.querySelectorAll('.provider-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to clicked card
        event.currentTarget.classList.add('selected');
        
        // Update form fields
        document.getElementById('provider_id').value = id;
        document.getElementById('provider_name').value = name;
        document.getElementById('scheduleBtn').disabled = false;
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