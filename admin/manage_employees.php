<?php
session_start();

//Only admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require '../db.php';
require '../vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function sanitize($conn, $str) {
    return $conn->real_escape_string(trim($str));
}

// send mail function
function sendAccountEmail($toEmail, $toName, $plainPassword, $isNew = true) {
    $mail = new PHPMailer(true);
    try {
        // --- SMTP Configuration ---
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rumaise777@gmail.com';    
        $mail->Password = 'bzrr ztys firb zlnj';      
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // --- Recipients ---
        $mail->setFrom('yourgmail@gmail.com', 'Leave Management System');
        $mail->addAddress($toEmail, $toName);

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $isNew ? "Welcome to the Company!" : "Your Account Has Been Updated";

        if ($isNew) {
            $mail->Body = "
                <p>Hi <strong>$toName</strong>,</p>
                <p>Your employee account has been created. Below are your login details:</p>
                <ul>
                    <li><strong>Email:</strong> $toEmail</li>
                    <li><strong>Password:</strong> $plainPassword</li>
                </ul>
                <p>Please log in and change your password immediately.</p>
                <p>Best regards,<br>Admin Team</p>
            ";
        } else {
            $mail->Body = "
                <p>Hi <strong>$toName</strong>,</p>
                <p>Your employee account details have been updated.</p>
                <ul>
                    <li><strong>Email:</strong> $toEmail</li>"
                    . (!empty($plainPassword) ? "<li><strong>New Password:</strong> $plainPassword</li>" : "")
                    . "</ul>
                <p>If you did not request this change, please contact support immediately.</p>
                <p>Best regards,<br>Admin Team</p>
            ";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Add Employee

if (isset($_POST['add'])) {
    $name = sanitize($conn, $_POST['username']);
    $email = sanitize($conn, $_POST['email']);
    $dept = sanitize($conn, $_POST['department']);
    $plainPassword = $_POST['password'];
    $password = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM employee WHERE username=? OR email=?");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<div class='alert alert-danger text-center'>Username or Email already exists.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO employee (username, email, password, department) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $dept);
        if ($stmt->execute()) {
            // Send welcome email
            if (!sendAccountEmail($email, $name, $plainPassword, true)) {
                echo "<div class='alert alert-warning text-center'>Employee added, but email could not be sent.</div>";
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "<div class='alert alert-danger text-center'>Database Error: {$conn->error}</div>";
        }
    }
    $stmt->close();
}


//  Edit Employee

if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($conn, $_POST['username']);
    $email = sanitize($conn, $_POST['email']);
    $dept = sanitize($conn, $_POST['department']);
    $plainPassword = $_POST['password'];

    if (!empty($plainPassword)) {
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE employee SET username=?, email=?, department=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $dept, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE employee SET username=?, email=?, department=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $dept, $id);
    }

    if ($stmt->execute()) {
        if (!sendAccountEmail($email, $name, $plainPassword, false)) {
            echo "<div class='alert alert-warning text-center'>Employee updated, but email could not be sent.</div>";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<div class='alert alert-danger text-center'>Database Error: {$conn->error}</div>";
    }
    $stmt->close();
}


// Delete Employee

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM leave_balance WHERE employee_id=$id");
    $conn->query("DELETE FROM employee WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch Employees

$emps = $conn->query("SELECT * FROM employee ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Employees</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-image: url("cg.jpg");
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 6px 25px rgba(0,0,0,0.1);
}
h3 { text-align: center; margin-bottom: 20px; }
.back-btn { display: inline-block; margin-bottom: 20px; color: #0d6efd; text-decoration: none; font-weight: 500; }
.back-btn:hover { text-decoration: underline; color: #0a58ca; }
</style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>

    <h3>Add Employee</h3>
    <form method="POST" class="row g-3 mb-5">
        <div class="col-md-6"><input name="username" class="form-control" required placeholder="Name"></div>
        <div class="col-md-6"><input name="email" type="email" class="form-control" required placeholder="Email"></div>
        <div class="col-md-6"><input name="department" class="form-control" required placeholder="Department"></div>
        <div class="col-md-6"><input name="password" type="password" class="form-control" required placeholder="Password"></div>
        <div class="col-12 text-center">
            <button name="add" type="submit" class="btn btn-primary px-4">Add Employee</button>
        </div>
    </form>

    <h3>Employees</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $emps->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn"
                                data-id="<?= $row['id'] ?>"
                                data-name="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>"
                                data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>"
                                data-department="<?= htmlspecialchars($row['department'], ENT_QUOTES) ?>">
                            Edit
                        </button>
                        <a href="?delete=<?= $row['id'] ?>" 
                           onclick="return confirm('Are you sure you want to delete this employee?');" 
                           class="btn btn-sm btn-danger ms-2">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="username" id="edit-name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="edit-email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" id="edit-department" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password <small>(leave blank to keep unchanged)</small></label>
            <input type="password" name="password" id="edit-password" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-name').value = btn.dataset.name;
        document.getElementById('edit-email').value = btn.dataset.email;
        document.getElementById('edit-department').value = btn.dataset.department;
        document.getElementById('edit-password').value = '';
        new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
    });
});
</script>
</body>
</html>
