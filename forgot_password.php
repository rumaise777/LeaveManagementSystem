<?php
session_start();
require('db.php');
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    $role = '';
    $userId = null;

    // --- Check in admin table ---
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username=? AND email=?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $role = 'admin';
        $userId = $row['id'];
    } else {
        // --- Check in employee table ---
        $stmt = $conn->prepare("SELECT id FROM employee WHERE username=? AND email=?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $role = 'employee';
            $userId = $row['id'];
        }
    }

    if ($role !== '') {
        // Generate a temporary human-readable password
        $tempPassword = substr(bin2hex(random_bytes(4)), 0, 8); // 8-character password
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Update password in database
        if ($role === 'admin') {
            $stmt = $conn->prepare("UPDATE admin SET password=? WHERE id=?");
        } else {
            $stmt = $conn->prepare("UPDATE employee SET password=? WHERE id=?");
        }
        $stmt->bind_param("si", $hashedPassword, $userId);
        $stmt->execute();

        // Send temporary password via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'rumaise777@gmail.com'; 
            $mail->Password = 'bzrr ztys firb zlnj';    
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('rumaise777@gmail.com', 'Leave Management System');
            $mail->addAddress($email, $username);

            $mail->isHTML(true);
            $mail->Subject = 'Your Temporary Password';
            $mail->Body = "<p>Hi <strong>$username</strong>,</p>
                           <p>Your temporary password is: <strong>$tempPassword</strong></p>
                           <p>Please login and change it immediately for security reasons.</p>";

            $mail->send();
            $message = "A temporary password has been sent to your email.";
        } catch (Exception $e) {
            $message = "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Invalid username or email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password - Leave Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <style>
        
    body {
    background-image: url("bg.jpg");
    background-size: cover;  
    background-repeat: no-repeat;
    background-attachment: fixed; 
    }
    </style>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4">
                <h3 class="text-center mb-4">Forgot Password</h3>
                <?php if(!empty($message)): ?>
                    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">&larr; Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
