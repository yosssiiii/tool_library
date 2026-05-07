<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

require_once("../config/Database.php");
require_once("../models/notification.php");

$db   = new Database();
$conn = $db->connect();
$uid  = (int)$_SESSION['user_id'];
$notModel = new Notification($conn);

// Mark all read
$notModel->markAllRead($uid);

// Get all notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notifications — Tool Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{background:#f1f5f9;font-family:'Inter',sans-serif}
.not-item{background:#fff;border-radius:12px;padding:16px 20px;margin-bottom:10px;border:1px solid #e2e8f0;display:flex;align-items:flex-start;gap:14px;transition:.15s}
.not-item:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
.not-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;background:#eff6ff;color:#3b82f6}
.not-time{font-size:.75rem;color:#94a3b8;margin-top:3px}
</style>
</head>
<body>
<nav class="navbar navbar-light bg-white border-bottom px-4 py-3">
  <a class="navbar-brand fw-bold" href="dashboard.php">🔧 Tool Library</a>
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left me-1"></i>Dashboard</a>
</nav>

<div class="container py-4" style="max-width:680px">
  <h4 class="fw-bold mb-4"><i class="fa fa-bell me-2 text-primary"></i>Notifications</h4>

  <?php if ($notifications->num_rows === 0): ?>
  <div class="text-center py-5 text-muted">
    <div style="font-size:3rem">🔔</div>
    <p class="mt-3">No notifications yet.</p>
    <a href="search_tool.php" class="btn btn-primary btn-sm">Browse Tools</a>
  </div>
  <?php else: while ($n = $notifications->fetch_assoc()): ?>
  <div class="not-item">
    <div class="not-icon"><i class="fa fa-bell"></i></div>
    <div class="flex-1">
      <div style="font-size:.9rem"><?= htmlspecialchars($n['message']) ?></div>
      <div class="not-time"><i class="fa fa-clock me-1"></i>
        <?php
          $diff = time() - strtotime($n['created_at']);
          if ($diff < 60)          echo 'Just now';
          elseif ($diff < 3600)    echo floor($diff/60) . 'm ago';
          elseif ($diff < 86400)   echo floor($diff/3600) . 'h ago';
          else                     echo date('M d, Y', strtotime($n['created_at']));
        ?>
      </div>
    </div>
  </div>
  <?php endwhile; endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>