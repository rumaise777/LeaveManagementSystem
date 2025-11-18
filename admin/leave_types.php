<?php
session_start();
if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../db.php';

// ADD leave type
if (isset($_POST['add'])) {
    $type = trim($_POST['type_name']);
    $days = (int)$_POST['max_days'];
    if (!empty($type) && $days > 0) {
        $stmt = $conn->prepare("INSERT INTO leave_type (type_name, max_days) VALUES (?, ?)");
        $stmt->bind_param("si", $type, $days);
        $stmt->execute();
        $stmt->close();
        header("Location: leave_types.php");
        exit();
    }
}

// EDIT leave type
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $type = trim($_POST['type_name']);
    $days = (int)$_POST['max_days'];
    if (!empty($type) && $days > 0) {
        $stmt = $conn->prepare("UPDATE leave_type SET type_name=?, max_days=? WHERE id=?");
        $stmt->bind_param("sii", $type, $days, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: leave_types.php");
        exit();
    }
}

// DELETE leave type
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM leave_type WHERE id=$id");
    header("Location: leave_types.php");
    exit();
}

$types = $conn->query("SELECT * FROM leave_type");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Types</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-image: url("cg.jpg"); 
    background-size: cover;   
    background-repeat: no-repeat;
    background-attachment: fixed; 
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }
        .back-btn:hover {
            text-decoration: underline;
            color: #0a58ca;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
    <h2 class="text-center">Manage Leave Types</h2>

    <!-- Add Leave Type Form -->
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-6">
            <input type="text" name="type_name" class="form-control" required placeholder="Leave Type Name">
        </div>
        <div class="col-md-4">
            <input type="number" name="max_days" class="form-control" required placeholder="Max Days" min="1">
        </div>
        <div class="col-md-2">
            <button name="add" type="submit" class="btn btn-success w-100">Add</button>
        </div>
    </form>

    <!-- Leave Types Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center">
            <thead class="table-light">
                <tr>
                    <th>Leave Type</th>
                    <th>Max Days</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $types->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['type_name']) ?></td>
                        <td><?= htmlspecialchars($row['max_days']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-btn"
                                    data-id="<?= $row['id'] ?>"
                                    data-type="<?= htmlspecialchars($row['type_name'], ENT_QUOTES) ?>"
                                    data-days="<?= $row['max_days'] ?>">
                                Edit
                            </button>
                            <a href="?delete=<?= $row['id'] ?>"
                               onclick="return confirm('Are you sure you want to delete this leave type?');"
                               class="btn btn-danger btn-sm ms-2">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Leave Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3">
                <label for="edit-type" class="form-label">Leave Type Name</label>
                <input type="text" name="type_name" id="edit-type" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="edit-days" class="form-label">Max Days</label>
                <input type="number" name="max_days" id="edit-days" class="form-control" required min="1">
            </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const editButtons = document.querySelectorAll('.edit-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit-id').value = btn.dataset.id;
            document.getElementById('edit-type').value = btn.dataset.type;
            document.getElementById('edit-days').value = btn.dataset.days;
            editModal.show();
        });
    });
</script>

</body>
</html>
