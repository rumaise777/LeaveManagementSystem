<?php
session_start();
require('db.php'); //database connection

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // --- Admin login check ---
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows == 1) {
        $admin = $adminResult->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_role'] = 'admin';
            header("Location: admin/dashboard.php");
            exit();
        }
    }

    // --- Employee login check ---
    $stmt = $conn->prepare("SELECT * FROM employee WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $empResult = $stmt->get_result();

    if ($empResult->num_rows == 1) {
        $emp = $empResult->fetch_assoc();
        if (password_verify($password, $emp['password'])) {
            $_SESSION['user_id'] = $emp['id'];
            $_SESSION['user_role'] = 'employee';
            header("Location: employee/dashboard.php");
            exit();
        }
    }

    $error = "Invalid username or password!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Leave Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
    background-image: url("bg.jpg");
    background-size: cover;   
    background-repeat: no-repeat;
    background-attachment: fixed; 
    }
</style>
<body>
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card p-4">
        <h3 class="text-center mb-4">Login</h3>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required placeholder="Enter username">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password">
          </div>
          <div class="d-grid mb-2">
            <button type="submit" name="login" class="btn btn-primary">Login</button>
          </div>
          <div class="text-center">
            <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
          </div>
          <div class="text-center mt-2">
            <a href="index.php" class="text-decoration-none">&larr; Back to Home</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
