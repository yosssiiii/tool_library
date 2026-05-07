<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

/* ================= GET USER DISPUTES ================= */

$sql = "SELECT d.*, 
               t.tool_name,
               r.start_date,
               r.end_date
        FROM disputes d
        JOIN reservations r 
            ON d.reservation_id = r.reservation_id
        JOIN tools t 
            ON r.tool_id = t.tool_id
        WHERE d.opened_by = ?
        ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$disputes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>My Disputes</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    background:#f1f5f9;
    font-family:Arial, sans-serif;
}

.page-box{
    max-width:1100px;
    margin:auto;
    margin-top:40px;
}

.card-box{
    background:#fff;
    border-radius:16px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.status-open{
    background:#fee2e2;
    color:#991b1b;
    padding:6px 14px;
    border-radius:20px;
    font-size:.85rem;
    font-weight:bold;
}

.status-resolved{
    background:#dcfce7;
    color:#166534;
    padding:6px 14px;
    border-radius:20px;
    font-size:.85rem;
    font-weight:bold;
}

.table td{
    vertical-align:middle;
}

</style>
</head>

<body>

<div class="container page-box">

<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<h2 class="fw-bold">
<i class="fa fa-exclamation-circle text-danger me-2"></i>
My Disputes
</h2>

<p class="text-muted mb-0">
Track all disputes and issue reports
</p>
</div>

<a href="dashboard.php" class="btn btn-outline-secondary">
<i class="fa fa-arrow-left me-1"></i>
Back
</a>

</div>

<div class="card-box">

<?php if($disputes->num_rows > 0): ?>

<div class="table-responsive">

<table class="table align-middle">

<thead>
<tr class="text-muted text-uppercase small">
<th>#</th>
<th>Tool</th>
<th>Rental Period</th>
<th>Reason</th>
<th>Status</th>
<th>Date</th>
</tr>
</thead>

<tbody>

<?php while($row = $disputes->fetch_assoc()): ?>

<tr>

<td>
#<?= $row['dispute_id']; ?>
</td>

<td>
<div class="fw-bold">
<?= htmlspecialchars($row['tool_name']); ?>
</div>
</td>

<td>
<div class="small">
<div>
<b>From:</b>
<?= $row['start_date']; ?>
</div>

<div>
<b>To:</b>
<?= $row['end_date']; ?>
</div>
</div>
</td>

<td style="max-width:300px;">
<?= nl2br(htmlspecialchars($row['reason'])); ?>
</td>

<td>

<?php if($row['status'] == 'open'): ?>

<span class="status-open">
OPEN
</span>

<?php else: ?>

<span class="status-resolved">
RESOLVED
</span>

<?php endif; ?>

</td>

<td>
<?= date("d M Y", strtotime($row['created_at'])); ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?php else: ?>

<div class="text-center py-5">

<i class="fa fa-circle-check fa-4x text-success mb-3"></i>

<h5>No disputes found</h5>

<p class="text-muted">
You haven't opened any disputes yet.
</p>

<a href="my_reservation.php" class="btn btn-primary">
Go To My Rentals
</a>

</div>

<?php endif; ?>

</div>

</div>

</body>
</html>