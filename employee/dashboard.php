<?php
session_start();
if ($_SESSION['user_role'] != 'employee') {
    header("Location: ../employee_login.php");
    exit();
}
include '../db.php';

$id = $_SESSION['user_id'];

// Fetch total leave balance
$balance = $conn->query("SELECT balance FROM leave_balance WHERE employee_id=$id")->fetch_assoc()['balance'] ?? 0;

// Count pending leave applications
$pending = $conn->query("SELECT COUNT(*) as count FROM apply_leave WHERE employee_id=$id AND status='Pending'")->fetch_assoc()['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
       background-image: url("dg.jpg");
    background-size: cover;   
    background-repeat: no-repeat;
    background-attachment: fixed; 
    }

    .dashboard-container {
        max-width: 900px;
        margin: 60px auto;
        background: rgba(255,255,255,0.95);
        padding: 30px 40px;
        border-radius: 10px;
        box-shadow: 0 6px 25px rgba(0,0,0,0.1);
    }

    .header {
        margin-bottom: 30px;
        text-align: center;
    }

    .stats {
        display: flex;
        gap: 30px;
        justify-content: center;
        margin-bottom: 40px;
    }

    .stat-card {
        flex: 1;
        background: #e9ecef;
        border-radius: 8px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .stat-card:hover {
        background-color: #0d6efd;
        color: white;
        cursor: default;
    }

    .stat-card h2 {
        font-size: 2.5rem;
        margin-bottom: 5px;
    }

    .stat-card p {
        font-size: 1.1rem;
        font-weight: 500;
    }

    .actions ul {
        list-style: none;
        padding-left: 0;
    }

    .actions li {
        margin: 15px 0;
    }

    .actions a {
        text-decoration: none;
        font-weight: 600;
        color: #0d6efd;
        transition: color 0.2s ease;
    }

    .actions a:hover {
        color: #0a58ca;
        text-decoration: underline;
    }

    .logout-link {
        display: block;
        margin-top: 50px;
        text-align: center;
    }
</style>
</head>
<body>

<div class="dashboard-container">
    <div class="header">
        <h1>Welcome, Employee</h1>
        <p class="text-muted">Dashboard overview and quick links</p>
    </div>

    <div class="stats">
        <div class="stat-card">
            <h2><?= $balance ?></h2>
            <p>Total Leave Balance</p>
        </div>
        <div class="stat-card">
            <h2><?= $pending ?></h2>
            <p>Pending Leave Applications</p>
        </div>
    </div>

    <div class="actions">
        <h3>Employee Actions</h3>
        <ul>
            <li><a href="apply_leave.php">Apply for Leave</a></li>
            <li><a href="leave_balance.php">View Leave Balance</a></li>
            <li><a href="leave_status.php">View Leave Status</a></li>
            <li><a href="reset_password.php">Reset Password</a></li>
        </ul>
    </div>

    <p class="logout-link">
        <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </p>
</div>

</body>
</html>
