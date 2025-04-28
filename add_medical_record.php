<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

$record = null;
$appointment_id = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : null;

// If editing existing record
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM medical_records WHERE id = ? AND provider_id = ?");
    $stmt->bind_param("ii", $_GET['id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $visit_date = $_POST['visit_date'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $notes = $_POST['notes'];
    
    if (isset($_POST['record_id'])) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE medical_records SET 
            user_id = ?, visit_date = ?, diagnosis = ?, treatment = ?, notes = ? 
            WHERE id = ? AND provider_id = ?");
        $stmt->bind_param("issssii", $user_id, $visit_date, $diagnosis, $treatment, $notes, 
            $_POST['record_id'], $_SESSION['user_id']);
    } else {
        // Create new record
        $stmt = $conn->prepare("INSERT INTO medical_records 
            (user_id, provider_id, visit_date, diagnosis, treatment, notes) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $user_id, $_SESSION['user_id'], $visit_date, 
            $diagnosis, $treatment, $notes);
    }
    
    if ($stmt->execute()) {
        header("Location: manage_records.php");
        exit();
    } else {
        $error = "Error saving record: " . $conn->error;
    }
}

// Fetch patients
$patients = $conn->query("SELECT id, name FROM users WHERE role_id = 1 ORDER BY name");

// If appointment_id is provided, fetch appointment details
$appointment = null;
if ($appointment_id) {
    $stmt = $conn->prepare("SELECT a.*, u.name as patient_name 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.id = ? AND a.provider_id = ?");
    $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $record ? 'Edit' : 'Add' ?> Medical Record - Provider Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="provider_dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><?= $record ? 'Edit' : 'Add' ?> Medical Record</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <?php if ($record): ?>
                    <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <select name="user_id" class="form-control" required <?= $appointment ? 'disabled' : '' ?>>
                        <?php if ($appointment): ?>
                            <option value="<?= $appointment['user_id'] ?>" selected>
                                <?= htmlspecialchars($appointment['patient_name']) ?>
                            </option>
                        <?php else: ?>
                            <?php while($patient = $patients->fetch_assoc()): ?>
                                <option value="<?= $patient['id'] ?>" <?= $record && $record['user_id'] == $patient['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($patient['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ($appointment): ?>
                        <input type="hidden" name="user_id" value="<?= $appointment['user_id'] ?>">
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Visit Date</label>
                    <input type="date" name="visit_date" class="form-control" required 
                        value="<?= $record ? $record['visit_date'] : ($appointment ? $appointment['appointment_date'] : '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="3" required><?= $record ? htmlspecialchars($record['diagnosis']) : '' ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Treatment</label>
                    <textarea name="treatment" class="form-control" rows="3" required><?= $record ? htmlspecialchars($record['treatment']) : '' ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= $record ? htmlspecialchars($record['notes']) : '' ?></textarea>
                </div>

                <div class="text-end">
                    <a href="manage_records.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 