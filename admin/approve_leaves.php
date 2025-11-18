<?php
session_start();
if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../db.php';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = $_GET['action'] == 'approve' ? 'Approved' : 'Rejected';

    if ($newStatus === 'Approved') {
        // Fetch leave application details
        $app = $conn->query("SELECT * FROM apply_leave WHERE id = $id")->fetch_assoc();
    }

    // Update application status
    $conn->query("UPDATE apply_leave SET status='$newStatus' WHERE id=$id");
    
    // Redirect to clear GET params and avoid resubmission
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Fetch pending leave applications with employee and leave type names
$applications = $conn->query("
    SELECT 
        al.id, 
        e.username AS name, 
        lt.type_name, 
        al.from_date, 
        al.to_date, 
        al.reason
    FROM apply_leave al
    JOIN employee e ON al.employee_id = e.id
    JOIN leave_type lt ON al.leave_type_id = lt.id
    WHERE al.status = 'Pending'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Approve Leave Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-image: url("cg.jpg"); 
    background-size: cover;  
    background-repeat: no-repeat;
    background-attachment: fixed; 
        }
        .container {
            max-width: 1000px;
            margin: 50px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 30px;
            text-align: center;
        }
        table {
            table-layout: fixed;
        }
        td, th {
            word-wrap: break-word;
        }
        .btn-approve {
            color: #fff;
            background-color: #198754;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            margin-right: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-approve:hover {
            background-color: #157347;
            color: #fff;
        }
        .btn-reject {
            color: #fff;
            background-color: #dc3545;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .btn-reject:hover {
            background-color: #b02a37;
            color: #fff;
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
    <h2>Pending Leave Applications</h2>

    <?php if ($applications->num_rows === 0): ?>
        <p class="text-center text-muted">No pending leave applications.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Reason</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $applications->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['type_name']) ?></td>
                            <td><?= htmlspecialchars($row['from_date']) ?></td>
                            <td><?= htmlspecialchars($row['to_date']) ?></td>
                            <td style="max-width: 250px;"><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
                            <td class="text-center">
                                <a href="?action=approve&id=<?= $row['id'] ?>" class="btn-approve btn btn-sm">Approve</a>
                                <a href="?action=reject&id=<?= $row['id'] ?>" class="btn-reject btn btn-sm">Reject</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
