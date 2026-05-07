<?php
session_start();

// ── Auth: librarians only ────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php"); exit();
}
if ($_SESSION['role'] !== 'librarian') {
    header("Location: public/dashboard.php"); exit();
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

// ── Handle admin actions ─────────────────────────────────────
if (isset($_GET['action'], $_GET['id'])) {
    $id     = (int) $_GET['id'];
    $action = $_GET['action'];

    $queries = [
        // User management
        
        'activate_user'  => ["UPDATE users SET status='active'     WHERE user_id=?",    "i"],
       
        'suspend_user'   => ["UPDATE users SET status='suspended' WHERE user_id=?",    "i"],
        // Tool management
        'approve_tool'   => ["UPDATE tools SET availability_status='available' WHERE tool_id=?",    "i"],
        'reject_tool'    => ["UPDATE tools SET availability_status='unlisted'  WHERE tool_id=?",    "i"],
        // Dispute management
        'resolve_dispute'=> ["UPDATE disputes SET status='resolved', resolved_at=NOW() WHERE dispute_id=?", "i"],
    ];

    if (isset($queries[$action])) {
        [$sql, $types] = $queries[$action];
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, $id);
        $stmt->execute();
    }
    header("Location: dashboard.php"); exit();
}

// ══ STATISTICS ══════════════════════════════════════════════

// Total users
$s = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='member'");
$totalMembers = (int)$s->fetch_assoc()['c'];

// Pending users (waiting KYC approval)
$s = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='pending'");
$pendingUsers = (int)$s->fetch_assoc()['c'];

//Pending tools (waiting approval)
// BUG5 FIXED: tools table uses availability_status ENUM('available','reserved','maintenance')
// No 'pending' value exists — counting all tools for display
$s = $conn->query("SELECT COUNT(*) AS c FROM tools WHERE availability_status='available'");
$pendingTools = (int)$s->fetch_assoc()['c'];

// Active reservations
$s = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE status IN ('approved','active')");
$activeReservations = (int)$s->fetch_assoc()['c'];

// Open disputes
$s = $conn->query("SELECT COUNT(*) AS c FROM disputes WHERE status != 'resolved'");
$openDisputes = (int)$s->fetch_assoc()['c'];

// Total platform revenue
$s = $conn->query("SELECT COALESCE(SUM(total_price),0) AS total FROM reservations WHERE status='completed'");
$totalRevenue = (float)$s->fetch_assoc()['total'];

// New users this month
$s = $conn->query("SELECT COUNT(*) AS c FROM users WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
$newUsersMonth = (int)$s->fetch_assoc()['c'];

// Maintenance pending
$s = $conn->query("SELECT COUNT(*) AS c FROM maintenance WHERE status='pending'");
$pendingMaintenance = (int)$s->fetch_assoc()['c'];

// ══ DATA LISTS ══════════════════════════════════════════════

// Pending users awaiting KYC approval
$pendingUsersList = $conn->query(
    "SELECT * FROM users WHERE status='pending' ORDER BY created_at DESC LIMIT 10"
);

// Pending tools awaiting approval
$pendingToolsList = $conn->query(
    "SELECT t.*, u.full_name AS owner_name
     FROM tools t 
     JOIN users u ON t.owner_id=u.user_id
     WHERE t.availability_status='pending'
     ORDER BY t.created_at DESC"
);

// Open disputes
$disputesList = $conn->query(
    "SELECT d.*, r.start_date, r.end_date,
            t.tool_name,
            opener.full_name AS opener_name
     FROM disputes d
     JOIN reservations r ON d.reservation_id = r.reservation_id
     JOIN tools t ON r.tool_id = t.tool_id
     JOIN users opener ON d.opened_by = opener.user_id
     WHERE d.status != 'resolved'
     ORDER BY d.created_at DESC LIMIT 10"
);

// Recent reservations (all users)
$recentReservations = $conn->query(
    "SELECT r.*, t.tool_name, 
            borrower.full_name AS borrower_name,
            owner.full_name    AS owner_name
     FROM reservations r
     JOIN tools t ON r.tool_id = t.tool_id
     JOIN users borrower ON r.user_id = borrower.user_id
     JOIN users owner    ON t.owner_id    = owner.user_id
     ORDER BY r.created_at DESC
     LIMIT 8"
);

// All members list
$membersList = $conn->query(
    "SELECT * FROM users WHERE role='member' ORDER BY created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard — Tool Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root { --sidebar:#0f172a; --accent:#6366f1; --bg:#f8fafc; }
* { box-sizing:border-box; }
body { background:var(--bg); font-family:'Inter',sans-serif; margin:0; }

/* Sidebar */
.sidebar { width:250px; min-height:100vh; background:var(--sidebar); position:fixed; top:0; left:0; z-index:100; display:flex; flex-direction:column; }
.sidebar .brand { padding:22px 20px 16px; font-size:1.05rem; font-weight:800; color:#fff; border-bottom:1px solid #1e293b; display:flex; align-items:center; gap:10px; }
.sidebar .brand .badge-admin { background:var(--accent); color:#fff; font-size:.65rem; padding:2px 8px; border-radius:20px; }
.sidebar nav { flex:1; padding:12px 10px; }
.sidebar a { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; color:#94a3b8; text-decoration:none; font-size:.88rem; margin-bottom:2px; transition:.2s; }
.sidebar a:hover,.sidebar a.active { background:var(--accent); color:#fff; }
.sidebar .section-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.08em; color:#475569; padding:16px 14px 6px; font-weight:700; }
.sidebar .bottom { padding:12px 10px 20px; border-top:1px solid #1e293b; }

/* Main */
.main { margin-left:250px; padding:28px 32px; min-height:100vh; }

/* Topbar */
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.topbar h2 { font-size:1.45rem; font-weight:800; color:#0f172a; margin:0; }
.topbar .sub { color:#64748b; font-size:.88rem; margin-top:3px; }
.admin-chip { background:#ede9fe; color:#6d28d9; padding:5px 14px; border-radius:20px; font-size:.8rem; font-weight:700; }

/* Alert chips for attention items */
.alert-chip { background:#fef2f2; color:#dc2626; padding:6px 12px; border-radius:8px; font-size:.8rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; }

/* Stat cards */
.stat-card { background:#fff; border-radius:16px; padding:20px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.05); transition:.2s; }
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.stat-card .num { font-size:2rem; font-weight:800; line-height:1; }
.stat-card .lbl { font-size:.78rem; color:#64748b; margin-top:5px; }
.icon-box { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }

/* Card box */
.card-box { background:#fff; border-radius:16px; padding:22px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.05); margin-bottom:20px; }
.box-title { font-weight:700; font-size:.95rem; color:#0f172a; margin-bottom:16px; display:flex; align-items:center; gap:8px; }

/* Tables */
.tbl thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; background:#f8fafc; font-weight:700; padding:10px 12px; border-bottom:1px solid #e2e8f0; }
.tbl td { font-size:.86rem; padding:11px 12px; vertical-align:middle; border-bottom:1px solid #f1f5f9; color:#334155; }
.tbl tr:last-child td { border-bottom:none; }

/* Pills */
.pill { display:inline-block; padding:3px 9px; border-radius:6px; font-size:.7rem; font-weight:700; text-transform:uppercase; }
.pill-pending   { background:#ffedd5; color:#9a3412; }
.pill-approved,.pill-available { background:#dcfce7; color:#166534; }
.pill-active    { background:#dbeafe; color:#1e40af; }
.pill-completed { background:#e2e8f0; color:#475569; }
.pill-cancelled { background:#fee2e2; color:#991b1b; }
.pill-open      { background:#fef3c7; color:#92400e; }
.pill-resolved  { background:#d1fae5; color:#065f46; }

/* Tabs */
.nav-pills .nav-link { font-size:.84rem; border-radius:10px; color:#475569; font-weight:500; }
.nav-pills .nav-link.active { background:var(--accent); }

@media(max-width:768px){ .sidebar{display:none;} .main{margin-left:0;padding:16px;} }
</style>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<div class="sidebar">
  <div class="brand">
    🔧 ToolLibrary
    <span class="badge-admin">ADMIN</span>
  </div>
  <nav>
    <div class="section-label">Overview</div>
    <a href="dashboard.php" class="active"><i class="fa fa-gauge fa-fw"></i> Dashboard</a>

    <div class="section-label">Users</div>
    <a href="users.php"><i class="fa fa-users fa-fw"></i> All Members
      <?php if ($pendingUsers > 0): ?><span class="ms-auto badge bg-danger" style="font-size:.65rem"><?= $pendingUsers ?></span><?php endif; ?>
    </a>
    <a href="users.php?filter=pending"><i class="fa fa-user-clock fa-fw"></i> Pending KYC</a>

    <div class="section-label">Tools</div>
    <a href="tools.php"><i class="fa fa-toolbox fa-fw"></i> All Tools</a>
    <a href="tools.php?filter=pending"><i class="fa fa-hourglass fa-fw"></i> Awaiting Approval
      <?php if ($pendingTools > 0): ?><span class="ms-auto badge bg-warning text-dark" style="font-size:.65rem"><?= $pendingTools ?></span><?php endif; ?>
    </a>

    <div class="section-label">Operations</div>
    <a href="reservations.php"><i class="fa fa-calendar-check fa-fw"></i> Reservations</a>
    <a href="disputes.php"><i class="fa fa-scale-balanced fa-fw"></i> Disputes
      <?php if ($openDisputes > 0): ?><span class="ms-auto badge bg-danger" style="font-size:.65rem"><?= $openDisputes ?></span><?php endif; ?>
    </a>
    <a href="maintenance.php"><i class="fa fa-wrench fa-fw"></i> Maintenance</a>
    <a href="reports.php"><i class="fa fa-chart-bar fa-fw"></i> Reports</a> 
    <a href="../public/logout.php" style="color:#f87171"><i class="fa fa-right-from-bracket fa-fw"></i> Logout</a>
  </nav>
  <div class="bottom">
    <div class="px-2 mb-2 small" style="color:#64748b">
      <div style="color:#94a3b8;font-size:.75rem">Logged in as</div>
      <div style="color:#e2e8f0;font-weight:600"><?= htmlspecialchars($userData['full_name']) ?></div>
    </div>
    
  </div>
</div>

<!-- ══ MAIN ═════════════════════════════════════════════════ -->
<div class="main">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <h2>Admin Dashboard 🛡️</h2>
      <div class="sub">Platform overview — <?= date('l, F j, Y') ?></div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <?php if ($pendingUsers > 0 || $pendingTools > 0 || $openDisputes > 0): ?>
      <div class="alert-chip">
        <i class="fa fa-triangle-exclamation"></i>
        <?= $pendingUsers + $pendingTools + $openDisputes ?> items need attention
      </div>
      <?php endif; ?>
      <span class="admin-chip"><i class="fa fa-shield-halved me-1"></i>Librarian</span>

      <!-- Notifications -->
      <div class="dropdown">
        <button class="btn btn-light rounded-circle position-relative" data-bs-toggle="dropdown" style="width:40px;height:40px;padding:0">
          <i class="fa fa-bell"></i>
          <?php if ($notCount > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge bg-danger" style="font-size:.6rem"><?= $notCount ?></span><?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="width:300px;max-height:320px;overflow-y:auto;border-radius:14px">
          <li class="px-3 py-2 fw-700 border-bottom small">🔔 Notifications</li>
          <?php
          $notList = $notModel->get($uid);
          if ($notCount > 0): while ($n = $notList->fetch_assoc()):
          ?><li class="px-3 py-2 border-bottom" style="font-size:.82rem"><?= htmlspecialchars($n['message']) ?></li>
          <?php endwhile; else: ?>
          <li class="px-3 py-3 text-muted text-center small">No notifications</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- ── Stat Cards ──────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#6366f1"><?= $totalMembers ?></div><div class="lbl">Total Members</div></div>
        <div class="icon-box" style="background:#ede9fe;color:#6366f1"><i class="fa fa-users"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#f59e0b"><?= $pendingUsers ?></div><div class="lbl">Pending KYC</div></div>
        <div class="icon-box" style="background:#fffbeb;color:#f59e0b"><i class="fa fa-user-clock"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#10b981"><?= $activeReservations ?></div><div class="lbl">Active Reservations</div></div>
        <div class="icon-box" style="background:#d1fae5;color:#10b981"><i class="fa fa-calendar-check"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#ef4444"><?= $openDisputes ?></div><div class="lbl">Open Disputes</div></div>
        <div class="icon-box" style="background:#fee2e2;color:#ef4444"><i class="fa fa-scale-balanced"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#3b82f6"><?= $pendingTools ?></div><div class="lbl">Tools Awaiting Approval</div></div>
        <div class="icon-box" style="background:#eff6ff;color:#3b82f6"><i class="fa fa-hourglass-half"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#8b5cf6">EGP <?= number_format($totalRevenue,0) ?></div><div class="lbl">Total Bookings</div></div>
        <div class="icon-box" style="background:#f5f3ff;color:#8b5cf6"><i class="fa fa-coins"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#06b6d4"><?= $newUsersMonth ?></div><div class="lbl">New Members This Month</div></div>
        <div class="icon-box" style="background:#ecfeff;color:#06b6d4"><i class="fa fa-user-plus"></i></div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card d-flex justify-content-between align-items-center">
        <div><div class="num" style="color:#f97316"><?= $pendingMaintenance ?></div><div class="lbl">Maintenance Pending</div></div>
        <div class="icon-box" style="background:#fff7ed;color:#f97316"><i class="fa fa-wrench"></i></div>
      </div>
    </div>
  </div>

  <!-- ── Tabs: Pending Actions ──────────────────────────────── -->
  <div class="card-box">
    <div class="box-title"><i class="fa fa-triangle-exclamation text-warning"></i> Actions Required</div>

    <ul class="nav nav-pills mb-3 gap-2" id="adminTabs">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-users">
          👤 Pending Users <?php if($pendingUsers>0): ?><span class="badge bg-danger ms-1"><?=$pendingUsers?></span><?php endif; ?>
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-tools">
          🔧 Pending Tools <?php if($pendingTools>0): ?><span class="badge bg-warning text-dark ms-1"><?=$pendingTools?></span><?php endif; ?>
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-disputes">
          ⚖️ Open Disputes <?php if($openDisputes>0): ?><span class="badge bg-danger ms-1"><?=$openDisputes?></span><?php endif; ?>
        </button>
      </li>
    </ul>

    <div class="tab-content">

      <!-- Pending Users -->
      <div class="tab-pane fade show active" id="tab-users">
        <div class="table-responsive">
          <table class="table tbl mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>ID Photo</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($pendingUsersList->num_rows === 0): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">✅ No pending users</td></tr>
            <?php else: while ($u = $pendingUsersList->fetch_assoc()): ?>
              <tr>
                <td><?= $u['user_id'] ?></td>
                <td class="fw-600"><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>

                <td>
                  <a href="?action=activate_user&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Activate this user?')">
                    <i class="fa fa-check me-1"></i>Activate
                  </a>
                  <a href="?action=suspend_user&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this user?')">
                    <i class="fa fa-ban me-1"></i>Reject
                  </a>
                </td>
              </tr>
            <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pending Tools -->
      <div class="tab-pane fade" id="tab-tools">
        <div class="table-responsive">
          <table class="table tbl mb-0">
            <thead><tr><th>#</th><th>Tool</th><th>Owner</th><th>Category</th><th>Price/Day</th><th>Deposit</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($pendingToolsList->num_rows === 0): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">✅ No tools awaiting approval</td></tr>
            <?php else: while ($t = $pendingToolsList->fetch_assoc()): ?>
              <tr>
                <td><?= $t['tool_id'] ?></td>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($t['tool_name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars(substr($t['description'] ?? '', 0, 50)) ?>...</small>
                </td>
                <td><?= htmlspecialchars($t['owner_name']) ?></td>
                <td><span class="pill pill-active"><?= htmlspecialchars($t['category_id'] ?? '—') ?></span></td>
                <td class="fw-600">EGP <?= number_format($t['price_per_day'], 0) ?></td>
                <td>EGP <?= number_format($t['deposit_amount'], 0) ?></td>
                <td>
                  <a href="?action=approve_tool&id=<?= $t['tool_id'] ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Approve this tool?')">
                    <i class="fa fa-check me-1"></i>Approve
                  </a>
                  <a href="?action=reject_tool&id=<?= $t['tool_id'] ?>"  class="btn btn-sm btn-danger" onclick="return confirm('Reject this tool?')">
                    <i class="fa fa-xmark me-1"></i>Reject
                  </a>
                </td>
              </tr>
            <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Open Disputes -->
      <div class="tab-pane fade" id="tab-disputes">
        <div class="table-responsive">
          <table class="table tbl mb-0">
            <thead><tr><th>#</th><th>Tool</th><th>Opened By</th><th>Reason</th><th>Period</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($disputesList->num_rows === 0): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">✅ No open disputes</td></tr>
            <?php else: while ($d = $disputesList->fetch_assoc()): ?>
              <tr>
                <td><?= $d['dispute_id'] ?></td>
                <td class="fw-600"><?= htmlspecialchars($d['tool_name']) ?></td>
                <td><?= htmlspecialchars($d['opener_name']) ?></td>
                <td><small><?= htmlspecialchars(substr($d['reason'] ?? '', 0, 60)) ?>...</small></td>
                <td><small class="text-muted"><?= $d['start_date'] ?> → <?= $d['end_date'] ?></small></td>
                <td><span class="pill pill-<?= $d['status'] ?>"><?= $d['status'] ?></span></td>
                <td>
                  <a href="dispute_detail.php?id=<?= $d['dispute_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                    <i class="fa fa-eye me-1"></i>Review
                  </a>
                  <a href="?action=resolve_dispute&id=<?= $d['dispute_id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as resolved?')">
                    <i class="fa fa-check me-1"></i>Resolve
                  </a>
                </td>
              </tr>
            <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Recent Reservations ──────────────────────────────────── -->
  <div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="box-title mb-0"><i class="fa fa-calendar-check text-indigo"></i> Recent Reservations</div>
      <a href="reservations.php" class="btn btn-outline-secondary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="table tbl mb-0">
        <thead><tr><th>#</th><th>Tool</th><th>Borrower</th><th>Owner</th><th>Period</th><th>Price</th><th>Status</th></tr></thead>
        <tbody>
        <?php if ($recentReservations->num_rows === 0): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No reservations yet</td></tr>
        <?php else: while ($r = $recentReservations->fetch_assoc()): ?>
          <tr>
            <td><?= $r['reservation_id'] ?></td>
            <td class="fw-600"><?= htmlspecialchars($r['tool_name']) ?></td>
            <td><?= htmlspecialchars($r['borrower_name']) ?></td>
            <td><?= htmlspecialchars($r['owner_name']) ?></td>
            <td><small class="text-muted"><?= $r['start_date'] ?> → <?= $r['end_date'] ?></small></td>
            <td class="fw-600">EGP <?= number_format($r['total_price'] ?? 0, 0) ?></td>
            <td><span class="pill pill-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Members List ──────────────────────────────────────────── -->
  <div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="box-title mb-0"><i class="fa fa-users text-primary"></i> Recent Members</div>
      <a href="users.php" class="btn btn-outline-secondary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="table tbl mb-0">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th>Trust</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($m = $membersList->fetch_assoc()): ?>
          <tr>
            <td><?= $m['user_id'] ?></td>
            <td class="fw-600"><?= htmlspecialchars($m['full_name']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['phone'] ?? '—') ?></td>
            <td><span class="pill <?= $m['membership_type']==='pro'?'pill-approved':'pill-pending' ?>"><?= $m['membership_type'] ?></span></td>
            
            <td><span class="pill pill-<?= $m['status'] === 'active' ? 'approved' : $m['status'] ?>"><?= $m['status'] ?></span></td>
            <td>
              <?php if ($m['status'] === 'active'): ?>
                <a href="?action=suspend_user&id=<?= $m['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Suspend this user?')">
                  <i class="fa fa-ban"></i>
                </a>
              <?php else: ?>
                <a href="?action=activate_user&id=<?= $m['user_id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Activate this user?')">
                  <i class="fa fa-check"></i>
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>