<?php
session_start();
include 'db_conn.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

// Handle appointment status update
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    $stmt->execute();
    
    // Log the activity
    $admin_id = $_SESSION['user_id'];
    $action = "Updated appointment status for ID: " . $appointment_id;
    $stmt = $conn->prepare("INSERT INTO system_activity (admin_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $admin_id, $action);
    $stmt->execute();
    
    header("Location: manage_appointments.php?success=Appointment status updated successfully");
    exit();
}

// Handle appointment deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $appointment_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    
    // Log the activity
    $admin_id = $_SESSION['user_id'];
    $action = "Deleted appointment with ID: " . $appointment_id;
    $stmt = $conn->prepare("INSERT INTO system_activity (admin_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $admin_id, $action);
    $stmt->execute();
    
    header("Location: manage_appointments.php?success=Appointment deleted successfully");
    exit();
}

// Fetch all appointments with user and provider details
$appointments = $conn->query("
    SELECT a.*, 
           u1.name as patient_name, u1.email as patient_email,
           u2.name as provider_name, u2.email as provider_email
    FROM appointments a 
    JOIN users u1 ON a.user_id = u1.id 
    JOIN users u2 ON a.provider_id = u2.id 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0],
    'pending' => $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetch_row()[0],
    'confirmed' => $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'")->fetch_row()[0],
    'completed' => $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetch_row()[0],
    'cancelled' => $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Mama Well</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
        }
        .sidebar .nav-link:hover {
            color: white;
        }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>Mama Well</h4>
                        <p class="text-muted">Admin Dashboard</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_appointments.php">
                                <i class="bi bi-calendar-check me-2"></i>Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_health_tips.php">
                                <i class="bi bi-lightbulb me-2"></i>Health Tips
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="system_settings.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Appointments</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Appointment
                        </button>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Appointments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Confirmed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['confirmed']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Completed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Cancelled</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['cancelled']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient</th>
                                        <th>Provider</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($appointment = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $appointment['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['patient_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['patient_email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['provider_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['provider_email']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editAppointmentModal"
                                                    data-appointment-id="<?php echo $appointment['id']; ?>"
                                                    data-patient-id="<?php echo $appointment['user_id']; ?>"
                                                    data-provider-id="<?php echo $appointment['provider_id']; ?>"
                                                    data-date="<?php echo $appointment['appointment_date']; ?>"
                                                    data-time="<?php echo $appointment['appointment_time']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="manage_appointments.php?delete=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this appointment?')">
                                                    <i class="bi bi-trash"></i>
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
            </main>
        </div>
    </div>

    <!-- Add Appointment Modal -->
    <div class="modal fade" id="addAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_appointment.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Patient</label>
                            <select class="form-select" name="user_id" required>
                                <?php
                                $patients = $conn->query("SELECT id, name FROM users WHERE role_id = 1");
                                while($patient = $patients->fetch_assoc()):
                                ?>
                                <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Healthcare Provider</label>
                            <select class="form-select" name="provider_id" required>
                                <?php
                                $providers = $conn->query("SELECT id, name FROM users WHERE role_id = 2");
                                while($provider = $providers->fetch_assoc()):
                                ?>
                                <option value="<?php echo $provider['id']; ?>"><?php echo htmlspecialchars($provider['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="appointment_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time</label>
                            <input type="time" class="form-control" name="appointment_time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Appointment Modal -->
    <div class="modal fade" id="editAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_appointment.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="edit_appointment_id">
                        <div class="mb-3">
                            <label class="form-label">Patient</label>
                            <select class="form-select" name="user_id" id="edit_user_id" required>
                                <?php
                                $patients = $conn->query("SELECT id, name FROM users WHERE role_id = 1");
                                while($patient = $patients->fetch_assoc()):
                                ?>
                                <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Healthcare Provider</label>
                            <select class="form-select" name="provider_id" id="edit_provider_id" required>
                                <?php
                                $providers = $conn->query("SELECT id, name FROM users WHERE role_id = 2");
                                while($provider = $providers->fetch_assoc()):
                                ?>
                                <option value="<?php echo $provider['id']; ?>"><?php echo htmlspecialchars($provider['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="appointment_date" id="edit_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time</label>
                            <input type="time" class="form-control" name="appointment_time" id="edit_time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit modal data
        document.getElementById('editAppointmentModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit_appointment_id').value = button.getAttribute('data-appointment-id');
            document.getElementById('edit_user_id').value = button.getAttribute('data-patient-id');
            document.getElementById('edit_provider_id').value = button.getAttribute('data-provider-id');
            document.getElementById('edit_date').value = button.getAttribute('data-date');
            document.getElementById('edit_time').value = button.getAttribute('data-time');
        });
    </script>
</body>
</html> 