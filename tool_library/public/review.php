<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

require_once("../config/Database.php");
require_once("../models/review.php");
require_once("../models/notification.php");

$db   = new Database();
$conn = $db->connect();
$uid  = (int)$_SESSION['user_id'];

$reviewModel = new Review($conn);
$notModel    = new Notification($conn);

$reservation_id = (int)($_GET['reservation_id'] ?? 0);
$success = $error = '';

// ── جلب بيانات الحجز ───────────────────────────────────────
if (!$reservation_id) { header("Location: my_reservation.php"); exit(); }

$stmt = $conn->prepare(
    "SELECT r.*, t.tool_name, t.owner_id,
            borrower.full_name AS borrower_name,
            owner.full_name    AS owner_name
     FROM reservations r
     JOIN tools t         ON r.tool_id    = t.tool_id
     JOIN users borrower  ON r.user_id    = borrower.user_id
     JOIN users owner     ON t.owner_id   = owner.user_id
     WHERE r.reservation_id = ? AND r.status = 'completed'"
);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    die("<div class='container py-5 text-center'><p class='text-muted'>Reservation not found or not completed yet.</p><a href='my_reservation.php' class='btn btn-primary'>Back</a></div>");
}

// تحديد: أنت البورور ولا الأونر؟
$is_borrower = ($res['user_id'] == $uid);
$reviewed_user_id = $is_borrower ? $res['owner_id'] : $res['user_id'];
$reviewed_name    = $is_borrower ? $res['owner_name'] : $res['borrower_name'];

// ── التحقق من إنه معمل review قبل كده ─────────────────────
$chk = $conn->prepare("SELECT review_id FROM reviews WHERE reservation_id=? AND reviewer_id=?");
$chk->bind_param("ii", $reservation_id, $uid);
$chk->execute();
$already_reviewed = $chk->get_result()->num_rows > 0;

// ── Submit ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_reviewed) {
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
    } else {
        $stmt2 = $conn->prepare(
            "INSERT INTO reviews (reservation_id, reviewer_id, reviewed_user_id, rating, comment)
             VALUES (?,?,?,?,?)"
        );
        $stmt2->bind_param("iiiis", $reservation_id, $uid, $reviewed_user_id, $rating, $comment);
        if ($stmt2->execute()) {
            // Update trust score
            $upd = $conn->prepare("
                UPDATE users u
                SET u.trust_score = (
                    SELECT COALESCE(ROUND(AVG(r.rating),1), 5)
                    FROM reviews r
                    WHERE r.reviewed_user_id = ?
                )
                WHERE u.user_id = ?
            ");

$upd->bind_param("ii", $reviewed_user_id, $reviewed_user_id);
$upd->execute();

            // Notify reviewed user
            $notModel->add($reviewed_user_id, "⭐ You received a new review! Rating: {$rating}/5");

            $already_reviewed = true;
            $success = "Your review has been submitted successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// ── جلب الـ reviews الموجودة على المستخدم ────────────────
$existing = $conn->prepare(
    "SELECT rv.*, u.full_name AS reviewer_name
     FROM reviews rv
     JOIN users u ON rv.reviewer_id = u.user_id
     WHERE rv.reviewed_user_id = ?
     ORDER BY rv.created_at DESC LIMIT 5"
);
$existing->bind_param("i", $reviewed_user_id);
$existing->execute();
$existingReviews = $existing->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Leave a Review — Tool Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f1f5f9; font-family:'Inter',sans-serif; }
.card-box { background:#fff; border-radius:16px; padding:28px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.star-btn { font-size:2rem; cursor:pointer; color:#d1d5db; transition:.15s; background:none; border:none; padding:0 4px; }
.star-btn:hover, .star-btn.active { color:#f59e0b; }
.review-card { background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:12px; border:1px solid #e2e8f0; }
</style>
</head>
<body>
<nav class="navbar navbar-light bg-white border-bottom px-4 py-3">
  <a class="navbar-brand fw-bold" href="dashboard.php">🔧 Tool Library</a>
  <a href="my_reservation.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left me-1"></i>Back</a>
</nav>

<div class="container py-5" style="max-width:640px">

  <!-- Header -->
  <div class="text-center mb-4">
    <div style="font-size:3rem">⭐</div>
    <h3 class="fw-bold mt-2">Rate Your Experience</h3>
    <p class="text-muted">You rented <strong><?= htmlspecialchars($res['tool_name']) ?></strong></p>
    <p class="text-muted">Reviewing: <strong><?= htmlspecialchars($reviewed_name) ?></strong></p>
  </div>

  <?php if ($success): ?>
  <div class="alert alert-success rounded-3"><i class="fa fa-circle-check me-2"></i><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger rounded-3"><i class="fa fa-circle-xmark me-2"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$already_reviewed || $success): ?>
  <?php if (!$success): ?>
  <!-- Review Form -->
  <div class="card-box mb-4">
    <form method="POST">
      <div class="text-center mb-4">
        <p class="fw-600 mb-2">How would you rate <?= htmlspecialchars($reviewed_name) ?>?</p>
        <div id="stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <button type="button" class="star-btn" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="ratingInput" value="0">
        <div id="ratingLabel" class="text-muted small mt-1">Click a star to rate</div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-600">Your Comment <span class="text-muted fw-normal">(optional)</span></label>
        <textarea name="comment" class="form-control" rows="4"
          placeholder="Share your experience — was the tool in good condition? Was the owner responsive?"
          style="border-radius:10px;resize:none"></textarea>
      </div>
      <button type="submit" class="btn btn-warning w-100 fw-bold py-2" style="border-radius:10px">
        <i class="fa fa-star me-2"></i>Submit Review
      </button>
    </form>
  </div>
  <?php endif; ?>
  <?php else: ?>
  <div class="alert alert-info rounded-3 text-center">
    <i class="fa fa-circle-info me-2"></i>You have already reviewed this reservation.
    <div class="mt-2"><a href="my_reservation.php" class="btn btn-primary btn-sm">Back to My Reservations</a></div>
  </div>
  <?php endif; ?>

  <!-- Existing reviews for this user -->
  <?php if ($existingReviews->num_rows > 0): ?>
  <div class="card-box">
    <h6 class="fw-bold mb-3">Previous Reviews for <?= htmlspecialchars($reviewed_name) ?></h6>
    <?php while ($rv = $existingReviews->fetch_assoc()): ?>
    <div class="review-card">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="fw-600 small"><?= htmlspecialchars($rv['reviewer_name']) ?></span>
        <span class="text-warning">
          <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rv['rating'] ? '★' : '☆'; ?>
        </span>
      </div>
      <?php if ($rv['comment']): ?>
      <p class="text-muted small mb-0"><?= htmlspecialchars($rv['comment']) ?></p>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>

</div>

<script>
const labels = ['','Poor','Fair','Good','Very Good','Excellent'];
function setRating(val) {
  document.getElementById('ratingInput').value = val;
  document.getElementById('ratingLabel').textContent = labels[val] + ' (' + val + '/5)';
  document.querySelectorAll('.star-btn').forEach((btn,i) => {
    btn.classList.toggle('active', i < val);
  });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>