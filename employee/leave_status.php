<?php
session_start();
if ($_SESSION['user_role'] !== 'employee') {
    header("Location: ../login.php");
    exit();
}
include '../db.php';

$id = $_SESSION['user_id'];
$leaves = $conn->query("
    SELECT al.*, lt.type_name 
    FROM apply_leave al 
    JOIN leave_type lt ON al.leave_type_id = lt.id 
    WHERE employee_id = $id 
    ORDER BY applied_on DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Leave Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url("dg.jpg");
    background-size: cover;  
    background-repeat: no-repeat;
    background-attachment: fixed;
        }
        .content-container {
            max-width: 900px;
            margin: 60px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .badge-status {
            font-size: 0.9rem;
            padding: 0.45em 0.7em;
        }
    </style>
</head>
<body>

<div class="container content-container">
    <div class="header-section">
        <h3 class="mb-0">My Leave Applications</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
    </div>

    <?php if ($leaves->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th scope="col">Leave Type</th>
                        <th scope="col">From Date</th>
                        <th scope="col">To Date</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($l = $leaves->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($l['type_name']); ?></td>
                            <td><?php echo htmlspecialchars($l['from_date']); ?></td>
                            <td><?php echo htmlspecialchars($l['to_date']); ?></td>
                            <td>
                                <?php
                                    $status = $l['status'];
                                    $badgeClass = match(strtolower($status)) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning'
                                    };
                                ?>
                                <span class="badge bg-<?php echo $badgeClass; ?> badge-status">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No leave applications found.</p>
    <?php endif; ?>
</div>

</body>
</html>
