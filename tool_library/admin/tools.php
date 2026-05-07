<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../public/login.php"); exit();
}
require_once("../config/Database.php");
require_once("../models/notification.php");
$db = new Database(); $conn = $db->connect();
$notModel = new Notification($conn);

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $sqlMap = [
        'approve' => "UPDATE tools SET availability_status='available' WHERE tool_id=?",
        'unlist'  => "UPDATE tools SET availability_status='maintenance' WHERE tool_id=?",
    ];
    if (isset($sqlMap[$_GET['action']])) {
        $s = $conn->prepare($sqlMap[$_GET['action']]); $s->bind_param("i",$id); $s->execute();
        $ow = $conn->prepare("SELECT owner_id, tool_name FROM tools WHERE tool_id=?");
        $ow->bind_param("i",$id); $ow->execute();
        $tl = $ow->get_result()->fetch_assoc();
        if ($tl) {
            $msg = $_GET['action']==='approve'
                ? "✅ Your tool '{$tl['tool_name']}' is now approved and listed!"
                : "⚠️ Your tool '{$tl['tool_name']}' has been unlisted by admin.";
            $notModel->add($tl['owner_id'], $msg);
        }
    }
    header("Location: tools.php"); exit();
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$where = []; $params = []; $types = '';
if (in_array($filter,['available','reserved','maintenance'])) { $where[]="t.availability_status=?"; $params[]=$filter; $types.='s'; }
if ($search) { $where[]="(t.tool_name LIKE ? OR u.full_name LIKE ?)"; $l="%$search%"; $params[]=$l; $params[]=$l; $types.='ss'; }
$ws = $where ? 'WHERE '.implode(' AND ',$where) : '';
$sql = "SELECT t.*, u.full_name AS owner_name, u.email AS owner_email,
        (SELECT COUNT(*) FROM reservations r WHERE r.tool_id=t.tool_id) AS total_rentals
        FROM tools t JOIN users u ON t.owner_id=u.user_id $ws ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types,...$params);
$stmt->execute(); $tools = $stmt->get_result();

$cnt = [];
foreach(['all','available','reserved','maintenance'] as $s){
    $q = $s==='all' ? "SELECT COUNT(*) c FROM tools" : "SELECT COUNT(*) c FROM tools WHERE availability_status='$s'";
    $cnt[$s]=(int)$conn->query($q)->fetch_assoc()['c'];
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Tools — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{background:#f8fafc;font-family:'Inter',sans-serif;margin:0}
.sidebar{width:220px;min-height:100vh;background:#0f172a;position:fixed;top:0;left:0;display:flex;flex-direction:column}
.sidebar .brand{padding:18px 16px 14px;font-size:.95rem;font-weight:800;color:#fff;border-bottom:1px solid #1e293b}
.sidebar nav{flex:1;padding:10px 8px}
.sidebar a{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:.84rem;margin-bottom:1px;transition:.18s}
.sidebar a:hover,.sidebar a.active{background:#6366f1;color:#fff}
.sidebar .bottom{padding:10px 8px 18px;border-top:1px solid #1e293b}
.main{margin-left:220px;padding:24px 28px}
.cb{background:#fff;border-radius:14px;padding:20px;border:1px solid #e2e8f0;margin-bottom:16px}
.tbl thead th{font-size:.7rem;text-transform:uppercase;color:#94a3b8;background:#f8fafc;font-weight:700;padding:9px 11px;border-bottom:1px solid #e2e8f0}
.tbl td{font-size:.84rem;padding:10px 11px;vertical-align:middle;border-bottom:1px solid #f1f5f9}
.tbl tr:last-child td{border-bottom:none}
.pill{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.68rem;font-weight:700;text-transform:uppercase}
.pill-available{background:#dcfce7;color:#166534}.pill-reserved{background:#dbeafe;color:#1e40af}.pill-maintenance{background:#fee2e2;color:#991b1b}
.tool-img{width:46px;height:46px;object-fit:cover;border-radius:8px;background:#f1f5f9;flex-shrink:0}
</style>
</head><body>
<div class="sidebar">
  <div class="brand">🔧 ToolLibrary</div>
  <nav>
    <a href="dashboard.php"><i class="fa fa-gauge fa-fw"></i> Dashboard</a>
    <a href="users.php"><i class="fa fa-users fa-fw"></i> Users</a>
    <a href="tools.php" class="active"><i class="fa fa-toolbox fa-fw"></i> Tools</a>
    <a href="reservations.php"><i class="fa fa-calendar-check fa-fw"></i> Reservations</a>
    <a href="disputes.php"><i class="fa fa-scale-balanced fa-fw"></i> Disputes</a>
    <a href="reports.php"><i class="fa fa-chart-bar fa-fw"></i> Reports</a>
  </nav>
  <div class="bottom"><a href="../public/logout.php" style="color:#f87171"><i class="fa fa-right-from-bracket fa-fw"></i> Logout</a></div>
</div>

<div class="main">
  <h4 class="fw-bold mb-4"><i class="fa fa-toolbox me-2 text-primary"></i>Manage Tools</h4>

  <div class="row g-3 mb-4">
    <?php foreach([['all','All','#6366f1','fa-toolbox'],['available','Available','#10b981','fa-circle-check'],['reserved','Reserved','#3b82f6','fa-calendar'],['maintenance','Unlisted','#ef4444','fa-ban']] as [$k,$l,$c,$i]): ?>
    <div class="col-6 col-xl-3"><a href="?filter=<?=$k?>" class="text-decoration-none">
      <div class="cb d-flex justify-content-between align-items-center" style="<?=$filter===$k?'border-color:'.$c.';border-width:2px':''?>">
        <div><div style="font-size:1.6rem;font-weight:800;color:<?=$c?>"><?=$cnt[$k]?></div><div style="font-size:.75rem;color:#64748b"><?=$l?></div></div>
        <div style="width:40px;height:40px;border-radius:10px;background:<?=$c?>22;color:<?=$c?>;display:flex;align-items:center;justify-content:center"><i class="fa <?=$i?>"></i></div>
      </div></a></div>
    <?php endforeach; ?>
  </div>

  <div class="cb">
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
      <input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or owner..." value="<?=htmlspecialchars($search)?>" style="border-radius:8px;max-width:280px">
      <button class="btn btn-primary btn-sm px-3" style="border-radius:8px"><i class="fa fa-search"></i></button>
      <?php if($search):?><a href="?filter=<?=$filter?>" class="btn btn-outline-secondary btn-sm" style="border-radius:8px">Clear</a><?php endif?>
    </form>
    <div class="table-responsive"><table class="table tbl mb-0">
      <thead><tr><th>Tool</th><th>Owner</th><th>Category</th><th>Price/Day</th><th>Rentals</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
      <?php if($tools->num_rows===0):?>
        <tr><td colspan="8" class="text-center text-muted py-4">No tools found</td></tr>
      <?php else: while($t=$tools->fetch_assoc()):?>
        <tr>
          <td><div class="d-flex align-items-center gap-2">
            <?php if($t['photo']):?><img src="../public/<?=htmlspecialchars($t['photo'])?>" class="tool-img"><?php else:?><div class="tool-img d-flex align-items-center justify-content-center text-muted"><i class="fa fa-wrench"></i></div><?php endif?>
            <div><div class="fw-bold" style="font-size:.84rem"><?=htmlspecialchars($t['tool_name'])?></div><div class="text-muted" style="font-size:.73rem">#<?=$t['tool_id']?></div></div>
          </div></td>
          <td><div style="font-size:.84rem"><?=htmlspecialchars($t['owner_name'])?></div><div class="text-muted" style="font-size:.73rem"><?=htmlspecialchars($t['owner_email'])?></div></td>
          <td><small><?=htmlspecialchars($t['category']??'—')?></small></td>
          <td class="fw-bold">EGP <?=number_format($t['price_per_day'],0)?></td>
          <td class="text-center"><?=$t['total_rentals']?></td>
          <td><span class="pill pill-<?=$t['availability_status']?>"><?=$t['availability_status']?></span></td>
          <td><small class="text-muted"><?=date('M d, Y',strtotime($t['created_at']))?></small></td>
          <td class="d-flex gap-1 flex-wrap">
            <?php if($t['availability_status']!=='available'):?>
              <a href="?action=approve&id=<?=$t['tool_id']?>" class="btn btn-sm btn-success py-0" onclick="return confirm('Approve?')"><i class="fa fa-check"></i></a>
            <?php else:?>
              <a href="?action=unlist&id=<?=$t['tool_id']?>" class="btn btn-sm btn-danger py-0" onclick="return confirm('Unlist?')"><i class="fa fa-ban"></i></a>
            <?php endif?>
          </td>
        </tr>
      <?php endwhile; endif?>
      </tbody>
    </table></div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>