<?php
session_start();
include 'db_conn.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'appointments';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch report data based on type
$report_data = [];
$total_count = 0;

switch ($report_type) {
    case 'appointments':
        $query = "SELECT a.*, 
                 u1.name as patient_name, 
                 u2.name as provider_name,
                 u1.email as patient_email,
                 u2.email as provider_email
                 FROM appointments a
                 JOIN users u1 ON a.user_id = u1.id
                 JOIN users u2 ON a.provider_id = u2.id
                 WHERE a.appointment_date BETWEEN ? AND ?";
        
        if ($status != 'all') {
            $query .= " AND a.status = ?";
        }
        
        $query .= " ORDER BY a.appointment_date DESC";
        
        $stmt = $conn->prepare($query);
        if ($status != 'all') {
            $stmt->bind_param("sss", $start_date, $end_date, $status);
        } else {
            $stmt->bind_param("ss", $start_date, $end_date);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
        break;

    case 'users':
        $query = "SELECT u.*, r.role_name 
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 WHERE u.created_at BETWEEN ? AND ?";
        
        if ($status != 'all') {
            $query .= " AND u.status = ?";
        }
        
        $query .= " ORDER BY u.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if ($status != 'all') {
            $stmt->bind_param("sss", $start_date, $end_date, $status);
        } else {
            $stmt->bind_param("ss", $start_date, $end_date);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
        break;

    case 'health_tracking':
        $query = "SELECT ht.*, u.name as patient_name, u.email as patient_email
                 FROM health_tracking ht
                 JOIN users u ON ht.user_id = u.id
                 WHERE ht.date BETWEEN ? AND ?
                 ORDER BY ht.date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
        break;
}

// Get statistics
$stats = [];
if ($report_type == 'appointments') {
    $stats['total'] = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0];
    $stats['pending'] = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetch_row()[0];
    $stats['confirmed'] = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'")->fetch_row()[0];
    $stats['completed'] = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetch_row()[0];
    $stats['cancelled'] = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'")->fetch_row()[0];
} elseif ($report_type == 'users') {
    $stats['total'] = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $stats['active'] = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetch_row()[0];
    $stats['inactive'] = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetch_row()[0];
    $stats['pregnant_women'] = $conn->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetch_row()[0];
    $stats['providers'] = $conn->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetch_row()[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Mama Well</title>
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
                            <a class="nav-link" href="manage_appointments.php">
                                <i class="bi bi-calendar-check me-2"></i>Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_reports.php">
                                <i class="bi bi-file-earmark-text me-2"></i>Reports
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
                    <h1 class="h2">Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Print Report
                        </button>
                    </div>
                </div>

                <!-- Report Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select name="report_type" class="form-select" onchange="this.form.submit()">
                                    <option value="appointments" <?php echo $report_type == 'appointments' ? 'selected' : ''; ?>>Appointments</option>
                                    <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>>Users</option>
                                    <option value="health_tracking" <?php echo $report_type == 'health_tracking' ? 'selected' : ''; ?>>Health Tracking</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All</option>
                                    <?php if ($report_type == 'appointments'): ?>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <?php elseif ($report_type == 'users'): ?>
                                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <?php if (!empty($stats)): ?>
                <div class="row mb-4">
                    <?php foreach ($stats as $key => $value): ?>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    <?php echo ucfirst(str_replace('_', ' ', $key)); ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $value; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Report Table -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php if ($report_type == 'appointments'): ?>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Provider</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        <?php elseif ($report_type == 'users'): ?>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                        <?php elseif ($report_type == 'health_tracking'): ?>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Type</th>
                                            <th>Details</th>
                                            <th>Date</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <?php if ($report_type == 'appointments'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($row['patient_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['patient_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($row['provider_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['provider_email']); ?></small>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] == 'pending' ? 'warning' : 
                                                        ($row['status'] == 'confirmed' ? 'info' : 
                                                            ($row['status'] == 'completed' ? 'success' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        <?php elseif ($report_type == 'users'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['role_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <?php elseif ($report_type == 'health_tracking'): ?>
                                            <td><?php echo $row['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($row['patient_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['patient_email']); ?></small>
                                            </td>
                                            <td><?php echo ucfirst($row['track_type']); ?></td>
                                            <td>
                                                <?php
                                                switch($row['track_type']) {
                                                    case 'symptom':
                                                        echo "Symptom: " . htmlspecialchars($row['symptom']) . 
                                                             "<br>Severity: " . htmlspecialchars($row['severity']);
                                                        break;
                                                    case 'nutrition':
                                                        echo "Meal: " . htmlspecialchars($row['meal_type']) . 
                                                             "<br>Calories: " . htmlspecialchars($row['calories']);
                                                        break;
                                                    case 'activity':
                                                        echo "Activity: " . htmlspecialchars($row['activity_type']) . 
                                                             "<br>Duration: " . htmlspecialchars($row['duration']) . " minutes";
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 