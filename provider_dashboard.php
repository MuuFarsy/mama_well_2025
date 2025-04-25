<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Fetch Appointments
$appointments = $conn->query("SELECT a.id, a.date, a.time, u.name AS patient_name, a.status 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.date DESC");

// Fetch Patients
$patients = $conn->query("SELECT id, name FROM users WHERE role_id = 1");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Healthcare Provider Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet"> <!-- Optional: external styles -->
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Provider Dashboard</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h3>

    <hr>
    <h4>Upcoming Appointments</h4>
    <table class="table table-striped">
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
        <?php while($row = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= $row['date'] ?></td>
                <td><?= $row['time'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <a href="update_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Update</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <hr>
    <h4>Add Medical Record</h4>
    <form action="add_medical_record.php" method="POST">
        <div class="mb-3">
            <label>Patient</label>
            <select name="user_id" class="form-control" required>
                <?php while($pat = $patients->fetch_assoc()): ?>
                    <option value="<?= $pat['id'] ?>"><?= htmlspecialchars($pat['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Diagnosis</label>
            <textarea name="diagnosis" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label>Prescribed Treatment</label>
            <textarea name="treatment" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Submit Record</button>
    </form>

    <hr>
    <h4>Send Advice to Patient</h4>
    <form action="send_advice.php" method="POST">
        <div class="mb-3">
            <label>Patient</label>
            <select name="user_id" class="form-control" required>
                <?php
                $patients->data_seek(0); // rewind result set
                while($pat = $patients->fetch_assoc()):
                ?>
                    <option value="<?= $pat['id'] ?>"><?= htmlspecialchars($pat['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Message</label>
            <textarea name="message" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-info">Send Advice</button>
    </form>
</div>
</body>
</html>
