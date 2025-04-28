<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $content = $_POST['content'];
                $category = $_POST['category'];
                
                $stmt = $conn->prepare("INSERT INTO health_tips (title, content, category, provider_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $title, $content, $category, $_SESSION['user_id']);
                break;
                
            case 'edit':
                $id = $_POST['tip_id'];
                $title = $_POST['title'];
                $content = $_POST['content'];
                $category = $_POST['category'];
                
                $stmt = $conn->prepare("UPDATE health_tips SET title = ?, content = ?, category = ? WHERE id = ? AND provider_id = ?");
                $stmt->bind_param("sssii", $title, $content, $category, $id, $_SESSION['user_id']);
                break;
                
            case 'delete':
                $id = $_POST['tip_id'];
                $stmt = $conn->prepare("DELETE FROM health_tips WHERE id = ? AND provider_id = ?");
                $stmt->bind_param("ii", $id, $_SESSION['user_id']);
                break;
        }
        
        if ($stmt->execute()) {
            $message = "Operation completed successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Fetch all health tips
$tips = $conn->query("SELECT * FROM health_tips WHERE provider_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Health Tips - Provider Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .tip-card {
            margin-bottom: 1rem;
        }
        .category-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="provider_dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTipModal">
            <i class="fas fa-plus me-2"></i>Add New Tip
        </button>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="row">
        <?php while($tip = $tips->fetch_assoc()): ?>
            <div class="col-md-6">
                <div class="card tip-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= htmlspecialchars($tip['title']) ?></h5>
                        <span class="badge bg-info category-badge"><?= htmlspecialchars($tip['category']) ?></span>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?= nl2br(htmlspecialchars($tip['content'])) ?></p>
                        <div class="text-muted small">
                            Created: <?= $tip['created_at'] ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-warning" onclick="editTip(<?= $tip['id'] ?>, '<?= htmlspecialchars($tip['title']) ?>', '<?= htmlspecialchars($tip['content']) ?>', '<?= htmlspecialchars($tip['category']) ?>')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tip?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="tip_id" value="<?= $tip['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Tip Modal -->
<div class="modal fade" id="addTipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Health Tip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Pregnancy">Pregnancy</option>
                            <option value="Nutrition">Nutrition</option>
                            <option value="Exercise">Exercise</option>
                            <option value="Mental Health">Mental Health</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Tip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tip Modal -->
<div class="modal fade" id="editTipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Health Tip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="tip_id" id="edit_tip_id">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="edit_category" class="form-control" required>
                            <option value="Pregnancy">Pregnancy</option>
                            <option value="Nutrition">Nutrition</option>
                            <option value="Exercise">Exercise</option>
                            <option value="Mental Health">Mental Health</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" id="edit_content" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editTip(id, title, content, category) {
        document.getElementById('edit_tip_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_content').value = content;
        document.getElementById('edit_category').value = category;
        new bootstrap.Modal(document.getElementById('editTipModal')).show();
    }
</script>
</body>
</html> 