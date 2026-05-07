<?php
session_start();

// ── Auth: members only ───────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php"); exit();
}
if ($_SESSION['role'] === 'librarian') {
    header("Location: ../librarian/dashboard.php"); exit();
}

require_once("../config/Database.php");
require_once("../models/user.php");
require_once("../models/notification.php");

$db   = new Database();
$conn = $db->connect();



$userModel = new User($conn);
$notModel  = new Notification($conn);

$uid      = (int) $_SESSION['user_id'];
$userData = $userModel->getUser($uid);
$notCount = $notModel->countUnread($uid);
$notifications = $notModel->get($uid);

// ── Handle reservation actions ───────────────────────────────
if (isset($_GET['action'], $_GET['id'])) {
    $rid    = (int) $_GET['id'];
    $action = $_GET['action'];
    $map    = [
        'approve'  => "UPDATE reservations SET status='approved'  WHERE reservation_id=? AND tool_id IN (SELECT tool_id FROM tools WHERE owner_id=?)",
        'reject'   => "UPDATE reservations SET status='cancelled' WHERE reservation_id=? AND tool_id IN (SELECT tool_id FROM tools WHERE owner_id=?)",
        'handover' => "UPDATE reservations SET status='active'    WHERE reservation_id=?",
        'returned' => "UPDATE reservations SET status='completed' WHERE reservation_id=?",
    ];
    if (isset($map[$action])) {
        $stmt = $conn->prepare($map[$action]);
        if (in_array($action, ['approve','reject'])) {
            $stmt->bind_param("ii", $rid, $uid);
        } else {
            $stmt->bind_param("i", $rid);
        }
        $stmt->execute();
    }
    header("Location: dashboard.php"); exit();
}


$user_id = $_SESSION['user_id'];
$trust = $userModel->getTrustScore($user_id);

echo "Trust Score: " . $trust;

// ── Stats ────────────────────────────────────────────────────
// My listed tools
$s = $conn->prepare("SELECT COUNT(*) AS c FROM tools WHERE owner_id=?");
$s->bind_param("i",$uid); $s->execute();
$myTools = (int)$s->get_result()->fetch_assoc()['c'];

// Active rentals (tools I borrowed)
$s = $conn->prepare("SELECT COUNT(*) AS c FROM reservations WHERE user_id=? AND status='active'");
$s->bind_param("i",$uid); $s->execute();
$activeRentals = (int)$s->get_result()->fetch_assoc()['c'];

// Total earnings from my tools
$s = $conn->prepare("SELECT COALESCE(SUM(r.total_price),0) AS total FROM reservations r JOIN tools t ON r.tool_id=t.tool_id WHERE t.owner_id=? AND r.status='completed'");
$s->bind_param("i",$uid); $s->execute();
$earnings = (float)$s->get_result()->fetch_assoc()['total'];

// Pending requests on my tools
$s = $conn->prepare("SELECT COUNT(*) AS c FROM reservations r JOIN tools t ON r.tool_id=t.tool_id WHERE t.owner_id=? AND r.status='pending'");
$s->bind_param("i",$uid); $s->execute();
$pendingCount = (int)$s->get_result()->fetch_assoc()['c'];

// ── Incoming requests on MY tools ────────────────────────────
$s = $conn->prepare(
    "SELECT r.*, t.tool_name, u.full_name AS borrower_name, u.user_id AS borrower_id
     FROM reservations r
     JOIN tools t ON r.tool_id = t.tool_id
     JOIN users u ON r.user_id = u.user_id
     WHERE t.owner_id = ?
     ORDER BY r.reservation_id DESC
     LIMIT 10"
);
$s->bind_param("i",$uid); $s->execute();
$incomingRequests = $s->get_result();

// ── My recent bookings (tools I borrowed) ────────────────────
$s = $conn->prepare(
    "SELECT r.*, t.tool_name, u.full_name AS owner_name
     FROM reservations r
     JOIN tools t ON r.tool_id = t.tool_id
     JOIN users u ON t.owner_id = u.user_id
     WHERE r.user_id = ?
     ORDER BY r.reservation_id DESC
     LIMIT 5"
);
$s->bind_param("i",$uid); $s->execute();
$myBookings = $s->get_result();

$trust = $userData['trust_score'] ?? '5.0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Dashboard — Tool Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root { --sidebar: #1e293b; --accent: #3b82f6; --bg: #f1f5f9; }
* { box-sizing: border-box; }
body { background: var(--bg); font-family: 'Inter', sans-serif; margin: 0; }

/* Sidebar */
.sidebar { width: 240px; min-height: 100vh; background: var(--sidebar); position: fixed; top: 0; left: 0; z-index: 100; display: flex; flex-direction: column; }
.sidebar .brand { padding: 24px 20px 16px; font-size: 1.1rem; font-weight: 800; color: #fff; border-bottom: 1px solid #334155; }
.sidebar .brand span { color: var(--accent); }
.sidebar nav { flex: 1; padding: 12px 10px; }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; color: #94a3b8; text-decoration: none; font-size: .9rem; margin-bottom: 2px; transition: .2s; }
.sidebar a:hover, .sidebar a.active { background: var(--accent); color: #fff; }
.sidebar .bottom { padding: 12px 10px 20px; border-top: 1px solid #334155; }

/* Main */
.main { margin-left: 240px; padding: 28px 32px; min-height: 100vh; }

/* Top bar */
.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.topbar h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.topbar .sub { color: #64748b; font-size: .9rem; margin-top: 2px; }

/* Stat cards */
.stat-card { background: #fff; border-radius: 16px; padding: 22px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.06); border: 1px solid #e2e8f0; transition: .2s; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
.stat-card .num { font-size: 1.9rem; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-card .lbl { font-size: .8rem; color: #64748b; margin-top: 4px; }
.icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

/* Tables */
.card-box { background: #fff; border-radius: 16px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,.06); border: 1px solid #e2e8f0; }
.card-box .box-title { font-weight: 700; font-size: 1rem; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.tbl thead th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; background: #f8fafc; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
.tbl td { font-size: .88rem; padding: 11px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
.tbl tr:last-child td { border-bottom: none; }

/* Status */
.pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
.pill-pending   { background: #ffedd5; color: #9a3412; }
.pill-approved  { background: #dcfce7; color: #166534; }
.pill-active    { background: #dbeafe; color: #1e40af; }
.pill-completed { background: #e2e8f0; color: #475569; }
.pill-cancelled { background: #fee2e2; color: #991b1b; }

/* Trust */
.trust-badge { background: #fef3c7; color: #92400e; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: .85rem; }

/* Profile mini */
.profile-box { text-align: center; }
.profile-box img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #e2e8f0; margin-bottom: 10px; }
.profile-box .name { font-weight: 700; font-size: 1rem; }
.profile-box .email { color: #64748b; font-size: .82rem; }

/* Responsive */
@media(max-width:768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 16px; } }
</style>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<div class="sidebar">
  <div class="brand">🔧 Tool<span>Library</span></div>
  <nav>
    <a href="dashboard.php" class="active"><i class="fa fa-house fa-fw"></i> Dashboard</a>
    <a href="search_tool.php"><i class="fa fa-magnifying-glass fa-fw"></i> Browse Tools</a>
    <a href="my_reservation.php"><i class="fa fa-calendar-check fa-fw"></i> My Rentals</a>
    <a href="my_rentals.php"><i class="fa fa-screwdriver-wrench fa-fw"></i> My Tools</a>
    <a href="add_tool.php"><i class="fa fa-plus-circle fa-fw"></i> List a Tool</a>
    <a href="chat_list.php?user=<?php echo $userData['user_id']; ?>"><i class="fa fa-comments fa-fw"></i>Messages</a>

    <a href="manage_profile.php"><i class="fa fa-user fa-fw"></i> Profile</a>
  </nav>
  <div class="bottom">
    <a href="logout.php" style="color:#f87171"><i class="fa fa-right-from-bracket fa-fw"></i> Logout</a>
  </div>
</div>

<!-- ══ MAIN ═════════════════════════════════════════════════ -->
<div class="main">

  <!-- Top bar -->
  <div class="topbar">
    <div>
      <h2>Hello, <?= htmlspecialchars(explode(' ', $userData['full_name'])[0]) ?> 👋</h2>
      <div class="sub">Here's what's happening with your tools today.</div>
    </div>
    <div class="d-flex align-items-center gap-3">

      <!-- Notifications -->
      <div class="dropdown">
        <button class="btn btn-light rounded-circle position-relative" data-bs-toggle="dropdown" style="width:40px;height:40px;padding:0">
          <i class="fa fa-bell"></i>
        <?php if ($notCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge bg-danger" style="font-size:.65rem"><?= $notCount ?></span>
        <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="width:300px;max-height:320px;overflow-y:auto;border-radius:14px">
        <li class="px-3 py-2 fw-700 border-bottom" style="font-size:.85rem">🔔 Notifications</li>
        <?php
        $notList = $notModel->get($uid);
        if ($notCount > 0):
            while ($n = $notList->fetch_assoc()):
        ?>
        <li class="px-3 py-2 border-bottom small" style="font-size:.82rem">
            <?= htmlspecialchars($n['message']) ?>
            <div class="text-muted" style="font-size:.72rem"><?= date('M d, H:i', strtotime($n['created_at'])) ?></div>
        </li>
        <?php endwhile; else: ?>
        <li class="px-3 py-3 text-muted small text-center">No notifications</li>
        <?php endif; ?>
        </ul>
    </div>

    <!-- Trust score -->

<div class="badge bg-success">
    ⭐ Trust Score: <?= number_format($trust, 2) ?>/5
</div>
      <!-- Messages -->
      <a href="chat.php" class="btn btn-light rounded-circle" style="width:40px;height:40px;padding:0;display:flex;align-items:center;justify-content:center">
        <i class="fa fa-comment-dots"></i>
      </a>
    </div>
  </div>

  <!-- ── Stats ─────────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div><div class="num"><?= $myTools ?></div><div class="lbl">My Listed Tools</div></div>
        <div class="icon-box" style="background:#eff6ff;color:#3b82f6"><i class="fa fa-toolbox"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div><div class="num"><?= $activeRentals ?></div><div class="lbl">Active Rentals</div></div>
        <div class="icon-box" style="background:#f0fdf4;color:#22c55e"><i class="fa fa-rotate"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div><div class="num">EGP <?= number_format($earnings,0) ?></div><div class="lbl">Total Earnings</div></div>
        <div class="icon-box" style="background:#fffbeb;color:#f59e0b"><i class="fa fa-wallet"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div>
          <div class="num"><?= $pendingCount ?></div>
          <div class="lbl">Pending Requests</div>
        </div>
        <div class="icon-box" style="background:#fef2f2;color:#ef4444"><i class="fa fa-clock"></i></div>
      </div>
    </div>
  </div>

  <div class="row g-3">

    <!-- ── Incoming Requests ─────────────────────────────── -->
    <div class="col-lg-8">
      <div class="card-box">
        <div class="box-title"><i class="fa fa-inbox text-primary"></i> Incoming Requests on My Tools</div>
        <div class="table-responsive">
          <table class="table tbl mb-0">
            <thead>
              <tr><th>Tool</th><th>Borrower</th><th>Period</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if ($incomingRequests->num_rows === 0): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No requests yet</td></tr>
            <?php else: while ($row = $incomingRequests->fetch_assoc()): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($row['tool_name']) ?></td>
                <td>
                  <a href="chat.php?to=<?= $row['borrower_id'] ?>" class="text-primary text-decoration-none">
                    <i class="fa fa-comment-dots me-1"></i><?= htmlspecialchars($row['borrower_name']) ?>
                  </a>
                </td>
                <td><small class="text-muted"><?= $row['start_date'] ?> → <?= $row['end_date'] ?></small></td>
                <td>
                  <span class="pill pill-<?= $row['status'] ?>"><?= $row['status'] ?></span>
                </td>
                <td>
                  <?php if ($row['status'] === 'pending'): ?>
                    <a href="?action=approve&id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-success me-1" title="Approve"><i class="fa fa-check"></i></a>
                    <a href="?action=reject&id=<?= $row['reservation_id'] ?>"  class="btn btn-sm btn-danger"  title="Reject"><i class="fa fa-xmark"></i></a>
                  <?php elseif ($row['status'] === 'approved'): ?>
                    <a href="generate_qr.php?id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-primary"><i class="fa fa-qrcode me-1"></i>QR Handover</a>
                  <?php elseif ($row['status'] === 'active'): ?>
                    <a href="?action=returned&id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-warning">Mark Returned</a>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Profile + My Bookings ─────────────────────────── -->
    <div class="col-lg-4 d-flex flex-column gap-3">

      <!-- Profile card -->
      <div class="card-box profile-box">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($userData['full_name']) ?>&background=3b82f6&color=fff&size=80" alt="">
        <div class="name"><?= htmlspecialchars($userData['full_name']) ?></div>
        <div class="email mb-1"><?= htmlspecialchars($userData['email']) ?></div>
        <span class="badge bg-<?= $userData['membership_type']==='pro'?'warning text-dark':'secondary' ?> mb-3">
          <?= strtoupper($userData['membership_type']) ?> Member
        </span>
        <div class="text-start border-top pt-3 small">
          <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($userData['phone'] ?? '—') ?></p>
          <p class="mb-3"><strong>Address:</strong> <?= htmlspecialchars($userData['address'] ?? '—') ?></p>
        </div>
        <a href="manage_profile.php" class="btn btn-outline-primary btn-sm w-100">
          <i class="fa fa-pen me-1"></i>Edit Profile
        </a>
      </div>
     <!--
      <!-- My recent bookings 
      <div class="card-box">
        <div class="box-title"><i class="fa fa-calendar-check text-success"></i> My Recent Bookings</div>
        <?php if ($myBookings->num_rows === 0): ?>
          <p class="text-muted small text-center py-2">No bookings yet. <a href="../search_tool.php">Browse tools</a></p>
        <?php else: while ($b = $myBookings->fetch_assoc()): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
              <div class="fw-600 small"><?= htmlspecialchars($b['tool_name']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= $b['start_date'] ?> → <?= $b['end_date'] ?></div>
            </div>
            <span class="pill pill-<?= $b['status'] ?>"><?= $b['status'] ?></span>
          </div>
        <?php endwhile; endif; ?>
        <a href="my_reservation.php" class="btn btn-outline-secondary btn-sm w-100 mt-3">View All</a>
      </div> 
        -->
    </div>
  </div>
</div><!-- /main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>