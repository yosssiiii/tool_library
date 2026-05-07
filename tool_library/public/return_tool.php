<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

require_once("../config/Database.php");
require_once("../models/notification.php");

$db   = new Database();
$conn = $db->connect();
$uid  = (int)$_SESSION['user_id'];
$notModel = new Notification($conn);

$reservation_id = (int)($_GET['id'] ?? 0);

if (!$reservation_id) {
    header("Location: my_reservation.php");
    exit();
}

/* ================= GET RESERVATION ================= */
$stmt = $conn->prepare(
    "SELECT r.*, t.tool_name, t.tool_id, t.owner_id,
            u.full_name AS owner_name
     FROM reservations r
     JOIN tools t ON r.tool_id = t.tool_id
     JOIN users u ON t.owner_id = u.user_id
     WHERE r.reservation_id = ? AND r.user_id = ? AND r.status = 'active'"
);

$stmt->bind_param("ii", $reservation_id, $uid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    die("<div class='container py-5 text-center'>
            <p class='text-danger'>Invalid or already returned reservation.</p>
            <a href='my_reservation.php' class='btn btn-primary'>Back</a>
        </div>");
}

$success = $error = '';

/* ================= RETURN PROCESS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $condition = $_POST['condition'] ?? 'good';

    // 1. mark completed
    $upd = $conn->prepare("UPDATE reservations SET status='completed' WHERE reservation_id=?");
    $upd->bind_param("i", $reservation_id);
    $upd->execute();

    // 2. make tool available again
    $t = $conn->prepare("UPDATE tools SET availability_status='available' WHERE tool_id=?");
    $t->bind_param("i", $res['tool_id']);
    $t->execute();

    if ($condition === 'good') {

        // notify owner
        $notModel->add(
            $res['owner_id'],
            "✅ {$res['tool_name']} returned in good condition."
        );

        // notify user
        $notModel->add(
            $uid,
            "✅ You successfully returned {$res['tool_name']}."
        );

        $success = "Tool returned successfully ✔";

    } else {

        // redirect to dispute system
        header("Location: dispute.php?reservation_id=$reservation_id&type=damage");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Return Tool — Tool Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f1f5f9; font-family:'Inter',sans-serif; }
.card-box { background:#fff; border-radius:16px; padding:32px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.cond-card { border:2px solid #e2e8f0; border-radius:12px; padding:20px; cursor:pointer; transition:.2s; }
.cond-card:hover { border-color:#3b82f6; }
.cond-card input:checked + .cond-inner { color: #166534; }
input[type=radio]:checked ~ .cond-card { border-color: #22c55e; background:#f0fdf4; }
</style>
</head>
<body>
<nav class="navbar navbar-light bg-white border-bottom px-4 py-3">
  <a class="navbar-brand fw-bold" href="dashboard.php">🔧 Tool Library</a>
  <a href="my_reservation.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left me-1"></i>Back</a>
</nav>

<div class="container py-5" style="max-width:560px">
  <div class="text-center mb-4">
    <div style="font-size:3rem">📦</div>
    <h3 class="fw-bold mt-2">Return Tool</h3>
    <p class="text-muted">Returning: <strong><?= htmlspecialchars($res['tool_name']) ?></strong></p>
    <p class="text-muted small">Owner: <?= htmlspecialchars($res['owner_name']) ?> &nbsp;|&nbsp;
      Reservation #<?= $reservation_id ?></p>
  </div>

  <?php if ($success): ?>
  <div class="alert alert-success rounded-3">
    <i class="fa fa-circle-check me-2"></i><?= $success ?>
  </div>
  <div class="d-grid gap-2 mt-3">
    <a href="review.php?reservation_id=<?= $reservation_id ?>" class="btn btn-warning fw-bold">
      <i class="fa fa-star me-2"></i>Leave a Review
    </a>
    <a href="my_reservation.php" class="btn btn-outline-secondary">Back to My Reservations</a>
  </div>

  <?php else: ?>
  <div class="card-box">
    <h6 class="fw-bold mb-1">📋 Reservation Summary</h6>
    <div class="d-flex justify-content-between text-muted small mb-3">
      <span><?= $res['start_date'] ?> → <?= $res['end_date'] ?></span>
      <span>Deposit: <strong class="text-dark">EGP <?= number_format($res['deposit_amount'], 0) ?></strong></span>
    </div>
    <hr>

    <form method="POST">
      <p class="fw-600 mb-3">What is the condition of the tool?</p>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="w-100">
            <div class="cond-card text-center" id="card-good">
              <div style="font-size:2.5rem">✅</div>
              <div class="fw-bold mt-2">Good Condition</div>
              <div class="text-muted small">No damage — deposit refunded</div>
              <input type="radio" name="condition" value="good" class="d-none" checked onchange="highlightCard('good')">
            </div>
          </label>
        </div>
        <div class="col-6">
          <label class="w-100">
            <div class="cond-card text-center" id="card-damaged">
              <div style="font-size:2.5rem">⚠️</div>
              <div class="fw-bold mt-2">Damaged</div>
              <div class="text-muted small">File a dispute — admin reviews</div>
              <input type="radio" name="condition" value="damaged" class="d-none" onchange="highlightCard('damaged')">
            </div>
          </label>
        </div>
      </div>

      <div class="alert alert-info small">
        <i class="fa fa-circle-info me-1"></i>
        If the tool is in <strong>good condition</strong>, your deposit of
        <strong>EGP <?= number_format($res['deposit_amount'], 0) ?></strong> will be refunded immediately.
        If damaged, an admin will review and decide on the deposit.
      </div>

      <button type="submit" class="btn btn-primary w-100 fw-bold py-2" style="border-radius:10px">
        <i class="fa fa-check me-2"></i>Confirm Return
      </button>
    </form>
  </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('card-good').style.borderColor = '#22c55e';
document.getElementById('card-good').style.background  = '#f0fdf4';
function highlightCard(val) {
  ['good','damaged'].forEach(v => {
    const c = document.getElementById('card-' + v);
    c.style.borderColor = v === val ? (v==='good'?'#22c55e':'#ef4444') : '#e2e8f0';
    c.style.background  = v === val ? (v==='good'?'#f0fdf4':'#fef2f2') : '';
  });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>