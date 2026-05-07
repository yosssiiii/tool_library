<?php
// ✅ BUG3 FIXED: was "comfirm_handover.php" — now correct name
session_start();
require_once("../config/Database.php");

$db   = new Database();
$conn = $db->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die("Invalid request.");
}

// Update reservation to active
$stmt = $conn->prepare("UPDATE reservations SET status='active' WHERE reservation_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Get reservation details for confirmation message
$stmt2 = $conn->prepare("SELECT r.*, t.tool_name FROM reservations r JOIN tools t ON r.tool_id=t.tool_id WHERE r.reservation_id=?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$res = $stmt2->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Handover Confirmed</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center">
  <div class="card shadow mx-auto" style="max-width:420px;border-radius:20px">
    <div class="card-body p-5">
      <div style="font-size:4rem">✅</div>
      <h4 class="fw-bold mt-3">Handover Confirmed!</h4>
      <?php if($res): ?>
      <p class="text-muted mt-2">
        You have successfully confirmed receiving<br>
        <strong><?= htmlspecialchars($res['tool_name']) ?></strong>
      </p>
      <div class="alert alert-success small">
        Reservation #<?= $id ?> is now <strong>Active</strong>
      </div>
      <?php endif; ?>
      <a href="dashboard.php" class="btn btn-primary w-100 mt-2">Go to Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>
