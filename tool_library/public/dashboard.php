<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// 1. استدعاء الملفات الأساسية
require_once("../config/Database.php");
require_once("../models/user.php");
require_once("../models/notification.php");

// 2. إنشاء الاتصال بقاعدة البيانات أولاً (الخطوة الأهم)
$db = new Database();
$conn = $db->connect(); // هنا عرفنا $conn

// 3. الآن نمرر $conn للموديلات وأنت مرتاح
$userModel = new User($conn);
$not = new Notification($conn);

$user_id = $_SESSION['user_id'];
$userData = $userModel->getUser($user_id);

// 4. جلب التنبيهات
$notifications = $not->get($user_id);
$not_count = ($notifications) ? $notifications->num_rows : 0;

/* =========================
   Dynamic Statistics 
   ========================= */

// My Tools - تعديل owner_id ليكون متوافق مع جدولك
$sql = "SELECT COUNT(*) AS total FROM tools WHERE owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$myTools = $stmt->get_result()->fetch_assoc()['total'];

// Active Rentals
$sql = "SELECT COUNT(*) AS total FROM reservations 
        WHERE user_id = ? AND status='approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$activeRentals = $stmt->get_result()->fetch_assoc()['total'];

// Earnings
$sql = "SELECT SUM(reservations.total_price) AS total
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        WHERE tools.owner_id = ? AND reservations.status='completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$earnings = $row['total'] ? $row['total'] : 0;

// Pending Tasks
$sql = "SELECT COUNT(*) AS total
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        WHERE tools.owner_id = ? AND reservations.status='pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$pendingTasks = $stmt->get_result()->fetch_assoc()['total'];

// Recent Activity - تم التعديل لجلب بيانات المستخدم
$sql = "SELECT reservations.*, tools.tool_name, users.full_name, users.user_id
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        JOIN users ON (CASE 
            WHEN reservations.user_id = ? THEN tools.owner_id 
            ELSE reservations.user_id 
        END) = users.user_id
        WHERE reservations.user_id = ? OR tools.owner_id = ?
        ORDER BY reservation_id DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$activities = $stmt->get_result();

// Incoming Requests (Owner Perspective)
// Incoming Requests (Owner Perspective)
$sql = "SELECT reservations.*, tools.tool_name, users.full_name  -- تأكد من إضافة users.full_name هنا
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        JOIN users ON reservations.user_id = users.user_id     -- وتأكد من وجود هذا السطر (الربط مع جدول المستخدمين)
        WHERE tools.owner_id = ?
        AND reservations.payment_status = 'paid'
        ORDER BY reservation_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentalData = $stmt->get_result();

// Trust Score
$trust = isset($userData['trust_score']) ? $userData['trust_score'] : "5.0";

// Handle Actions
if(isset($_GET['action']) && isset($_GET['id'])){
    $id = $_GET['id'];
    $action = $_GET['action'];
    if($action == "approve") $sql = "UPDATE reservations SET status='approved' WHERE reservation_id=?";
    elseif($action == "reject") $sql = "UPDATE reservations SET status='rejected' WHERE reservation_id=?";
    elseif($action == "handover") $sql = "UPDATE reservations SET status='active' WHERE reservation_id=?";
    elseif($action == "return") $sql = "UPDATE reservations SET status='completed' WHERE reservation_id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Dashboard - Tool Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sidebar-bg: #1e293b; --primary-color: #3b82f6; --bg-gray: #f1f5f9; }
        body { background: var(--bg-gray); font-family: 'Inter', sans-serif; }
        .sidebar { min-height: 100vh; background: var(--sidebar-bg); color: #fff; position: sticky; top: 0; }
        .sidebar a { color: #94a3b8; padding: 12px 20px; display: block; text-decoration: none; transition: 0.3s; border-radius: 8px; margin: 4px 10px; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary-color); color: #fff; }
        .stat-card { border: none; border-radius: 15px; transition: transform 0.3s; background: #fff; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .trust-badge { background: #fef3c7; color: #92400e; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; }
        .table-container { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .status-pill { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #ffedd5; color: #9a3412; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-active { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0 d-none d-md-block">
            <div class="text-center py-4 border-bottom border-secondary mb-3">
                <h5 class="fw-bold"><i class="fa fa-tools me-2"></i>Tool Library</h5>
            </div>
            <a href="dashboard.php" class="active"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="search_tool.php"><i class="fa fa-search me-2"></i> Browse Tools</a>
            <a href="my_reservation.php"><i class="fa fa-clock me-2"></i> My Rentals</a>
            <a href="add_tool.php"><i class="fa fa-plus-circle me-2"></i> List Tool</a>
            <a href="logout.php" class="text-danger mt-5"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
        </div>

        <div class="col-md-10 p-md-5 p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Hello, <?php echo explode(' ', $userData['full_name'])[0]; ?>! 👋</h2>
                    <p class="text-muted">Here's what's happening with your tools today.</p>
                </div>
                
                <div class="d-flex align-items-center gap-3">

    <!-- 🔔 Notification -->
    <div class="dropdown">

        <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
            <i class="fa fa-bell fs-5"></i>

            <?php if($not_count > 0){ ?>
            <span class="position-absolute top-0 start-100 translate-middle badge bg-danger">
                <?php echo $not_count; ?>
            </span>
            <?php } ?>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow" style="width:300px; max-height:300px; overflow:auto;">
            <li class="dropdown-header fw-bold">Notifications</li>

<?php
$notifications = $not->get($_SESSION['user_id']); // إعادة تحميل

if($not_count > 0){
    while($n = $notifications->fetch_assoc()){
        echo "<li class='px-3 py-2 border-bottom small'>
                        🔔 ".$n['message']."
                        </li>";
                    }
                    }else{
                    echo "<li class='px-3 py-2 text-muted small'>No notifications</li>";
                    }
                ?>
        </ul>

    </div>

    <!-- 💬 Chat Button -->
<td>
        <a href="chat_list.php" class="btn btn-light">
            <i class="fa fa-comments"></i>
        </a>
</td>

    <!-- ⭐ Trust -->
    <div class="trust-badge">
        <i class="fa fa-star me-1"></i>
        Trust Score: <?php echo $trust; ?>
    </div>

</div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="stat-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted small mb-1">My Tools</p><h3 class="fw-bold mb-0"><?php echo $myTools; ?></h3></div>
                            <div class="icon-box bg-primary text-white"><i class="fa fa-toolbox"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted small mb-1">Active Rentals</p><h3 class="fw-bold mb-0"><?php echo $activeRentals; ?></h3></div>
                            <div class="icon-box bg-success text-white"><i class="fa fa-retweet"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted small mb-1">Total Earnings</p><h3 class="fw-bold mb-0">$<?php echo number_format($earnings, 2); ?></h3></div>
                            <div class="icon-box bg-warning text-white"><i class="fa fa-wallet"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted small mb-1">Pending Requests</p><h3 class="fw-bold mb-0"><?php echo $pendingTasks; ?></h3></div>
                            <div class="icon-box bg-danger text-white"><i class="fa fa-bell"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="table-container shadow-sm">
                        <h5 class="fw-bold mb-4"><i class="fa fa-inbox me-2 text-primary"></i>Incoming Requests</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tool Name</th>
                                        <th>Borrower</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $rentalData->fetch_assoc()){ ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $row['tool_name']; ?></td>
                                        <td>
                                            <!-- التعديل هنا: السطر الخاص باسم المستأجر ورابط الشات -->
                                            <a href="chat.php?user=<?php echo $row['user_id']; ?>" class="text-decoration-none text-primary fw-bold">
                                                <i class="fa fa-comment-dots me-1"></i>
                                                <?php echo $row['full_name']; ?>
                                            </a>
                                        </td>
                                        <td><span class="small text-muted"><?php echo $row['start_date']; ?> to <?php echo $row['end_date']; ?></span></td>
                                        <td><span class="status-pill status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                                        <td>
                                            <!-- قسم الأزرار (Actions) -->
                                            <?php if($row['status'] == 'pending'): ?>
                                                <a href="?action=approve&id=<?php echo $row['reservation_id']; ?>" class="btn btn-sm btn-success"><i class="fa fa-check"></i></a>
                                                <a href="?action=reject&id=<?php echo $row['reservation_id']; ?>" class="btn btn-sm btn-danger"><i class="fa fa-times"></i></a>
                                            <?php elseif($row['status'] == 'approved'): ?>
                                                <a href="generate_qr.php?id=<?php echo $row['reservation_id']; ?>" class="btn btn-sm btn-primary px-3"><i class="fa fa-qrcode me-1"></i> Handover (QR)</a>
                                            <?php elseif($row['status'] == 'active'): ?>
                                                <a href="?action=return&id=<?php echo $row['reservation_id']; ?>" class="btn btn-sm btn-warning px-3">Mark Returned</a>
                                            <?php else: ?>
                                                <span class="text-muted small">No Actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="table-container shadow-sm text-center">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userData['full_name']); ?>&background=0D6EFD&color=fff" class="rounded-circle mb-3 shadow-sm" width="100">
                        <h5 class="fw-bold mb-1"><?php echo $userData['full_name']; ?></h5>
                        <p class="text-muted small mb-3"><?php echo $userData['email']; ?></p>
                        <div class="text-start border-top pt-3 mt-3">
                            <p class="small mb-1"><strong>Phone:</strong> <?php echo $userData['phone']; ?></p>
                            <p class="small mb-4"><strong>Address:</strong> <?php echo $userData['address']; ?></p>
                            <a href="manage_profile.php" class="btn btn-outline-primary btn-sm w-100">Edit Profile Information</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>