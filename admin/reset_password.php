<?php
session_start();
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}
require '../db.php';

$message = "";

if (isset($_POST['submit'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Fetch admin password
    $stmt = $conn->prepare("SELECT password FROM admin WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!password_verify($current, $result['password'])) {
        $message = "Current password is incorrect!";
    } elseif ($new !== $confirm) {
        $message = "New password and confirm password do not match!";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $_SESSION['user_id']);
        $stmt->execute();
        $message = "Password has been updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
     <style>
        
    body {
    background-image: url("cg.jpg"); 
    background-size: cover;  
    background-repeat: no-repeat;
    background-attachment: fixed; 
    }
    </style>
<div class="container mt-5" style="max-width:500px;">
    <div class="card p-4">
        <h3 class="text-center mb-4">Reset Password</h3>
        <?php if(!empty($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <div class="d-grid">
                <button type="submit" name="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
        <div class="mt-3 text-center">
            <a href="dashboard.php" class="text-decoration-none">&larr; Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
