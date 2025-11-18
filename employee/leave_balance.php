<?php 
session_start();

if ($_SESSION['user_role'] !== 'employee') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$id = (int)$_SESSION['user_id']; // Cast for safety

// Fetch leave types and their max days
$leaveTypes = $conn->query("SELECT id, type_name, max_days FROM leave_type");

$leaveData = [];
$total = 0;

while ($lt = $leaveTypes->fetch_assoc()) {
    $leaveTypeId = (int)$lt['id'];
    $typeName = $lt['type_name'];
    $maxDays = (int)$lt['max_days'];

    // Query updated to handle half-day leaves
    $approved = $conn->query("
        SELECT SUM(
            CASE 
                WHEN is_half_day = 1 THEN 0.5
                ELSE DATEDIFF(to_date, from_date) + 1
            END
        ) AS days_taken 
        FROM apply_leave
        WHERE employee_id = $id 
          AND leave_type_id = $leaveTypeId 
          AND status = 'approved'
    ");

    $takenRow = $approved ? $approved->fetch_assoc() : ['days_taken' => 0];
    $daysTaken = isset($takenRow['days_taken']) ? (float)$takenRow['days_taken'] : 0;

    $balance = $maxDays - $daysTaken;
    $total += max(0, $balance); // Prevent negative total

    $leaveData[] = [
        'type_name' => $typeName,
        'leave_type_id' => $leaveTypeId,
        'balance' => round(max(0, $balance), 1),
        'max_days' => $maxDays,
        'taken' => round($daysTaken, 1)
    ];
}

// ✅ Update or insert into leave_balance table (total balance only)
$conn->query("
    INSERT INTO leave_balance (employee_id, balance)
    VALUES ($id, $total)
    ON DUPLICATE KEY UPDATE balance = $total
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Balances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
           background-image: url("dg.jpg"); 
    background-size: cover;  
    background-repeat: no-repeat;
    background-attachment: fixed; 
        }
        .content-container {
            max-width: 750px;
            margin: 60px auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .table thead {
            background-color: #0d6efd;
            color: #fff;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .summary-box {
            background: #e9f5ff;
            border-left: 5px solid #0d6efd;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .summary-box strong {
            color: #0d6efd;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

<div class="container content-container">
    <div class="header-section">
        <h3 class="mb-0">Leave Balances</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
    </div>

    <?php if (count($leaveData) > 0): ?>
        <div class="summary-box">
            <span>Total Leave Balance: <strong><?= number_format($total, 1) ?> Days</strong></span>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Allowed (Days)</th>
                        <th>Taken (Days)</th>
                        <th>Remaining (Days)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaveData as $b): ?>
                        <tr class="<?= $b['balance'] < 3 ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars($b['type_name']) ?></td>
                            <td><?= $b['max_days'] ?></td>
                            <td><?= number_format($b['taken'], 1) ?></td>
                            <td><strong><?= number_format($b['balance'], 1) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No leave balances found.</p>
    <?php endif; ?>
</div>

</body>
</html>
