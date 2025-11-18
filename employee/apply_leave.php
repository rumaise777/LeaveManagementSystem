<?php
session_start();
if ($_SESSION['user_role'] !== 'employee') {
    header("Location: ../login.php");
    exit();
}
include '../db.php';

$id = (int)$_SESSION['user_id'];
$success = $error = "";

// Fetch total leave balance (for display only, NO deduction here)
$totalRow = $conn->query("SELECT balance FROM leave_balance WHERE employee_id = $id");
$total = $totalRow ? ($totalRow->fetch_assoc()['balance'] ?? 0) : 0;

// Handle leave application
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply'])) {
    $type = (int)$_POST['leave_type'];
    $from = $_POST['from_date'];
    $to = $_POST['to_date'];
    $reason = $conn->real_escape_string($_POST['reason']);
    $isHalfDay = isset($_POST['half_day']) ? true : false;
    $halfDaySlot = $_POST['half_day_slot'] ?? '';

    try {
        $start = new DateTime($from);
        $end = new DateTime($to);
        if ($end < $start) {
            $error = "To Date must be after or same as From Date.";
        } else {
            $interval = $start->diff($end);
            $daysRequested = $interval->days + 1;

            if ($isHalfDay && $from === $to) {
                $daysRequested = 0.5; // half day
            }

            // Prevent overlapping with approved leaves
            $overlapQuery = $conn->prepare("
                SELECT COUNT(*) AS overlap_count
                FROM apply_leave
                WHERE employee_id = ?
                  AND status = 'Approved'
                  AND (
                      (from_date <= ? AND to_date >= ?) OR
                      (from_date <= ? AND to_date >= ?) OR
                      (? <= from_date AND ? >= to_date)
                  )
            ");
            $overlapQuery->bind_param("issssss", $id, $from, $from, $to, $to, $from, $to);
            $overlapQuery->execute();
            $overlapResult = $overlapQuery->get_result()->fetch_assoc();

            if ($overlapResult['overlap_count'] > 0) {
                $error = "You already have an approved leave during this period.";
            } else {
                // Check how many days of this leave type are already approved (including half days)
                $query = $conn->prepare("
                    SELECT SUM(days) AS total_days 
                    FROM apply_leave 
                    WHERE employee_id = ? AND leave_type_id = ? AND status = 'Approved'
                ");
                $query->bind_param("ii", $id, $type);
                $query->execute();
                $result = $query->get_result();
                $row = $result->fetch_assoc();
                $daysTaken = $row['total_days'] ?? 0;

                $limitReached = false;

                // Leave rules
                if ($type == 1 && ($daysTaken + $daysRequested) > 20) { // Casual Leave
                    $error = "Casual leave limit exceeded. Maximum allowed is 20 days.";
                    $limitReached = true;
                } elseif ($type == 2) { // Medical Leave
                    if ($daysRequested < 3) {
                        $error = "Medical leave must be taken for at least 3 days.";
                        $limitReached = true;
                    } elseif (($daysTaken + $daysRequested) > 12) {
                        $error = "Medical leave limit exceeded. Maximum allowed is 12 days.";
                        $limitReached = true;
                    }
                }

                if ($isHalfDay) {
                    if ($type != 1) {
                        $error = "Half day is allowed only for Casual Leave.";
                        $limitReached = true;
                    } elseif ($from !== $to) {
                        $error = "Half day can only be applied if From and To dates are the same.";
                        $limitReached = true;
                    } elseif (!in_array($halfDaySlot, ['forenoon', 'afternoon'])) {
                        $error = "Please select Forenoon or Afternoon for Half Day.";
                        $limitReached = true;
                    }
                }

                if (!$limitReached && !$error) {
                    $isHalfDayFlag = $isHalfDay ? 1 : 0;
                    $halfDaySlotValue = $isHalfDay ? $halfDaySlot : '';

                    // Default status is 'Pending' on new leave request
                    $status = 'Pending';

                    $stmt = $conn->prepare("INSERT INTO apply_leave 
                        (employee_id, leave_type_id, from_date, to_date, reason, is_half_day, half_day_slot, days, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisssisds", $id, $type, $from, $to, $reason, $isHalfDayFlag, $halfDaySlotValue, $daysRequested, $status);

                    if ($stmt->execute()) {
                        $success = "Leave application submitted successfully.";
                    } else {
                        $error = "Failed to apply for leave. Please try again.";
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error = "Invalid date input.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Apply for Leave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-image: url("dg.jpg");
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .form-container {
            max-width: 600px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<div class="container form-container">
    <div class="form-header">
        <h3>Apply for Leave</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label fw-bold">Total Leave Balance: </label>
        <span class="badge bg-primary"><?= (float)$total ?> Days</span>
    </div>

    <form method="POST" id="leaveForm">
        <div class="mb-3">
            <label for="leave_type" class="form-label">Leave Type</label>
            <select class="form-select" id="leave_type" name="leave_type" required>
                <option value="">-- Select Type --</option>
                <?php
                $types = $conn->query("SELECT * FROM leave_type");
                while ($t = $types->fetch_assoc()) {
                    echo "<option value='{$t['id']}'>" . htmlspecialchars($t['type_name']) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" class="form-control" id="from_date" name="from_date" required />
        </div>

        <div class="mb-3">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" class="form-control" id="to_date" name="to_date" required />
        </div>

        <!-- Half Day Checkbox -->
        <div class="mb-3 form-check hidden" id="half_day_checkbox_container">
            <input type="checkbox" class="form-check-input" id="half_day" name="half_day" />
            <label class="form-check-label" for="half_day">Half Day?</label>
        </div>

        <!-- Time Slot Dropdown -->
        <div class="mb-3 hidden" id="half_day_slot_container">
            <label for="half_day_slot" class="form-label">Select Half Day Time Slot</label>
            <select class="form-select" id="half_day_slot" name="half_day_slot">
                <option value="">-- Select Slot --</option>
                <option value="forenoon">Forenoon</option>
                <option value="afternoon">Afternoon</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Reason</label>
            <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
        </div>

        <div class="d-grid">
            <button type="submit" name="apply" class="btn btn-primary">Submit Leave Request</button>
        </div>
    </form>
</div>

<script>
    const leaveType = document.getElementById('leave_type');
    const fromDate = document.getElementById('from_date');
    const toDate = document.getElementById('to_date');
    const halfDayCheckbox = document.getElementById('half_day');
    const halfDaySlotContainer = document.getElementById('half_day_slot_container');
    const halfDayCheckboxContainer = document.getElementById('half_day_checkbox_container');

    function toggleHalfDayOption() {
        const selectedType = leaveType.value;
        const from = fromDate.value;
        const to = toDate.value;

        if (selectedType === '1' && from && to && from === to) {
            halfDayCheckboxContainer.classList.remove('hidden');
        } else {
            halfDayCheckbox.checked = false;
            halfDaySlotContainer.classList.add('hidden');
            halfDayCheckboxContainer.classList.add('hidden');
        }
    }

    function toggleSlotDropdown() {
        if (halfDayCheckbox.checked) {
            halfDaySlotContainer.classList.remove('hidden');
        } else {
            halfDaySlotContainer.classList.add('hidden');
        }
    }

    leaveType.addEventListener('change', toggleHalfDayOption);
    fromDate.addEventListener('change', toggleHalfDayOption);
    toDate.addEventListener('change', toggleHalfDayOption);
    halfDayCheckbox.addEventListener('change', toggleSlotDropdown);

    toggleHalfDayOption();
</script>

</body>
</html>
