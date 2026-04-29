<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once("../config/Database.php");
require_once("../models/user.php");

$db = new Database();
$conn = $db->connect();

$userModel = new User($conn);
$user_id = $_SESSION['user_id'];

$userData = $userModel->getUser($user_id);

/* =========================
Dynamic Statistics
========================= */

// My Tools
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
        JOIN tools
        ON reservations.tool_id = tools.tool_id
        WHERE tools.owner_id = ?
        AND reservations.status='completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$earnings = $row['total'] ? $row['total'] : 0;

// Pending Tasks
$sql = "SELECT COUNT(*) AS total
        FROM reservations
        JOIN tools
        ON reservations.tool_id = tools.tool_id
        WHERE tools.owner_id = ?
        AND reservations.status='pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$pendingTasks = $stmt->get_result()->fetch_assoc()['total'];

// Recent Activity
$sql = "SELECT reservations.*, tools.tool_name
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        WHERE reservations.user_id = ?
        ORDER BY reservation_id DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$activities = $stmt->get_result();

// Trust Score
$trust = isset($userData['trust_score']) ? $userData['trust_score'] : "5.0";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root{
--primary:#0d6efd;
--dark:#212529;
}
body{
background:#f8f9fa;
}
.sidebar{
min-height:100vh;
background:var(--dark);
}
.sidebar a{
color:#ddd;
padding:14px 20px;
display:block;
text-decoration:none;
}
.sidebar a:hover,
.sidebar a.active{
background:var(--primary);
color:#fff;
}
.card-box{
border:none;
border-radius:16px;
box-shadow:0 5px 18px rgba(0,0,0,.05);
}
.trust{
background:#ffc107;
padding:8px 15px;
border-radius:30px;
font-weight:bold;
}
</style>

</head>
<body>

<div class="container-fluid">
<div class="row">

<!-- Sidebar -->
<div class="col-md-2 sidebar p-0">

<div class="text-center text-white py-4">
<h4>Tool Library</h4>
</div>

<a href="dashboard.php" class="active">Dashboard</a>
<a href="search_tool.php">Browse Tools</a>
<a href="my_reservations.php">My Rentals</a>
<a href="add_tool.php">List Tool</a>
<a href="my_payments.php">Payments</a>
<a href="logout.php" class="text-danger">Logout</a>

</div>

<!-- Content -->
<div class="col-md-10 p-4">

<div class="d-flex justify-content-between align-items-center mb-4">
<h1>
Welcome back,
<?php echo $userData['full_name']; ?>!
</h1>

<div class="trust">
Trust Score: <?php echo $trust; ?>
</div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card card-box p-3">
<h6>My Tools</h6>
<h2><?php echo $myTools; ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card card-box p-3">
<h6>Active Rentals</h6>
<h2><?php echo $activeRentals; ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card card-box p-3">
<h6>Earnings</h6>
<h2>$<?php echo $earnings; ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card card-box p-3">
<h6>Pending Tasks</h6>
<h2><?php echo $pendingTasks; ?></h2>
</div>
</div>

</div>

<div class="row">

<!-- Recent Activity -->
<div class="col-md-8">

<div class="card card-box p-4">
<h4>Recent Activity</h4>

<table class="table mt-3">
<tr>
<th>Tool</th>
<th>Status</th>
<th>Date</th>
</tr>

<?php while($row = $activities->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['tool_name']; ?></td>
<td><?php echo $row['status']; ?></td>
<td><?php echo $row['start_date']; ?></td>
</tr>

<?php } ?>

</table>

</div>

</div>

<!-- Profile -->
<div class="col-md-4">

<div class="card card-box p-4 text-center">

<img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userData['full_name']); ?>&background=random"
class="rounded-circle mb-3" width="90">

<h5><?php echo $userData['full_name']; ?></h5>

<p class="text-muted">
<?php echo $userData['email']; ?>
</p>

<hr>

<p><b>Phone:</b> <?php echo $userData['phone']; ?></p>
<p><b>Address:</b> <?php echo $userData['address']; ?></p>

<a href="manage_profile.php" class="btn btn-primary w-100">
Edit Profile
</a>

</div>

</div>

</div>

</div>
</div>
</div>

</body>
</html>