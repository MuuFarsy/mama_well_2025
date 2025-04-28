<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['track_type'])) {
        $track_type = $_POST['track_type'];
        $user_id = $_SESSION['user_id'];
        $date = date('Y-m-d');
        
        switch($track_type) {
            case 'symptom':
                $symptom = $_POST['symptom'];
                $severity = $_POST['severity'];
                $notes = $_POST['notes'];
                $stmt = $conn->prepare("INSERT INTO health_tracking (user_id, track_type, symptom, severity, notes, date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $user_id, $track_type, $symptom, $severity, $notes, $date);
                break;
                
            case 'nutrition':
                $meal_type = $_POST['meal_type'];
                $food_items = $_POST['food_items'];
                $calories = $_POST['calories'];
                $notes = $_POST['notes'];
                $stmt = $conn->prepare("INSERT INTO health_tracking (user_id, track_type, meal_type, food_items, calories, notes, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssiss", $user_id, $track_type, $meal_type, $food_items, $calories, $notes, $date);
                break;
                
            case 'activity':
                $activity_type = $_POST['activity_type'];
                $duration = $_POST['duration'];
                $intensity = $_POST['intensity'];
                $notes = $_POST['notes'];
                $stmt = $conn->prepare("INSERT INTO health_tracking (user_id, track_type, activity_type, duration, intensity, notes, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $user_id, $track_type, $activity_type, $duration, $intensity, $notes, $date);
                break;
        }
        
        if ($stmt->execute()) {
            $success_message = "Entry added successfully!";
        } else {
            $error_message = "Error adding entry. Please try again.";
        }
    }
}

// Fetch recent entries
$recent_entries = $conn->query("SELECT * FROM health_tracking 
    WHERE user_id = " . $_SESSION['user_id'] . " 
    ORDER BY date DESC, created_at DESC 
    LIMIT 10");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Health Tracking - Maternal Health</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="CSS/dashboard.css" rel="stylesheet">
</head>
<body>
<!-- Desktop Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark desktop-nav">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-heartbeat me-2"></i>Health Tracking</a>
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

        <!-- Tracking Options -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="tracking-card">
                    <h4><i class="fas fa-exclamation-triangle text-warning me-2"></i>Symptoms</h4>
                    <p>Track your pregnancy symptoms and their severity</p>
                    <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#symptomModal">
                        Add Symptom
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="tracking-card">
                    <h4><i class="fas fa-utensils text-success me-2"></i>Nutrition</h4>
                    <p>Log your meals and nutritional intake</p>
                    <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#nutritionModal">
                        Add Meal
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="tracking-card">
                    <h4><i class="fas fa-running text-primary me-2"></i>Activity</h4>
                    <p>Record your physical activities and exercise</p>
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#activityModal">
                        Add Activity
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Entries -->
        <div class="row">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h4 class="card-title">Recent Entries</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Details</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while($entry = $recent_entries->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $entry['date'] ?></td>
                                        <td>
                                            <?php
                                            switch($entry['track_type']) {
                                                case 'symptom':
                                                    echo '<span class="badge bg-warning">Symptom</span>';
                                                    break;
                                                case 'nutrition':
                                                    echo '<span class="badge bg-success">Nutrition</span>';
                                                    break;
                                                case 'activity':
                                                    echo '<span class="badge bg-primary">Activity</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            switch($entry['track_type']) {
                                                case 'symptom':
                                                    echo htmlspecialchars($entry['symptom']) . ' (Severity: ' . $entry['severity'] . ')';
                                                    break;
                                                case 'nutrition':
                                                    echo htmlspecialchars($entry['meal_type']) . ': ' . $entry['food_items'] . ' (' . $entry['calories'] . ' cal)';
                                                    break;
                                                case 'activity':
                                                    echo htmlspecialchars($entry['activity_type']) . ' (' . $entry['duration'] . ' min, ' . $entry['intensity'] . ')';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($entry['notes']) ?></td>
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

<!-- Symptom Modal -->
<div class="modal fade" id="symptomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Symptom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" class="tracking-form">
                    <input type="hidden" name="track_type" value="symptom">
                    <div class="mb-3">
                        <label class="form-label">Symptom</label>
                        <input type="text" class="form-control" name="symptom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select class="form-select" name="severity" required>
                            <option value="mild">Mild</option>
                            <option value="moderate">Moderate</option>
                            <option value="severe">Severe</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Add Symptom</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Nutrition Modal -->
<div class="modal fade" id="nutritionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Meal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" class="tracking-form">
                    <input type="hidden" name="track_type" value="nutrition">
                    <div class="mb-3">
                        <label class="form-label">Meal Type</label>
                        <select class="form-select" name="meal_type" required>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snack">Snack</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Food Items</label>
                        <textarea class="form-control" name="food_items" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Calories</label>
                        <input type="number" class="form-control" name="calories" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Add Meal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activity Modal -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" class="tracking-form">
                    <input type="hidden" name="track_type" value="activity">
                    <div class="mb-3">
                        <label class="form-label">Activity Type</label>
                        <select class="form-select" name="activity_type" required>
                            <option value="walking">Walking</option>
                            <option value="yoga">Yoga</option>
                            <option value="swimming">Swimming</option>
                            <option value="prenatal_exercise">Prenatal Exercise</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" name="duration" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Intensity</label>
                        <select class="form-select" name="intensity" required>
                            <option value="light">Light</option>
                            <option value="moderate">Moderate</option>
                            <option value="vigorous">Vigorous</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Activity</button>
                </form>
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
            <a href="#" class="mobile-nav-item active">
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