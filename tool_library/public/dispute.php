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

$success = "";
$error = "";

if(isset($_POST['submit'])){

    $reservation_id = $_POST['reservation_id'];
    $reason = $_POST['reason'];

    $sql = "INSERT INTO disputes (reservation_id, opened_by, reason, status)
            VALUES (?, ?, ?, 'open')";

    $stmt = $conn->prepare($sql);

    if($stmt){

        $stmt->bind_param("iis", $reservation_id, $user_id, $reason);

        if($stmt->execute()){
            $success = "✅ Your dispute has been submitted successfully!";
        } else {
            $error = "❌ Failed to submit dispute.";
        }

    } else {
        $error = "❌ Database error: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Open Dispute</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body { background:#f1f5f9; }

.sidebar {
    min-height:100vh;
    background:#1e293b;
    color:#fff;
}

.sidebar a {
    color:#94a3b8;
    padding:10px;
    display:block;
    text-decoration:none;
    border-radius:8px;
}

.sidebar a:hover {
    background:#3b82f6;
    color:#fff;
}

.card {
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

.btn-danger {
    border-radius:10px;
}
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">

<!-- Sidebar -->
<div class="col-md-2 sidebar p-3">
    <h5 class="text-center mb-4">Tool Library</h5>

    <a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="search_tool.php"><i class="fa fa-search"></i> Browse Tools</a>
    <a href="my_reservation.php"><i class="fa fa-book"></i> My Rentals</a>
    <a href="add_tool.php"><i class="fa fa-plus"></i> Add Tool</a>
    <a href="my_disputes.php"><i class="fa fa-exclamation-circle"></i> My Disputes</a>
    <a href="logout.php" class="text-danger"><i class="fa fa-sign-out"></i> Logout</a>
</div>

<!-- Content -->
<div class="col-md-10 p-4">

<div class="d-flex justify-content-between mb-4">
    <h3>Open Dispute ⚠️</h3>
</div>

<div class="card p-4" style="max-width:600px;">

    <h5 class="mb-3">Report a Problem</h5>
    <p class="text-muted">If you have any issue with a reservation, submit a dispute.</p>

    <!-- رسائل النجاح / الخطأ -->
    <?php if($success != "") { ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php } ?>

    <?php if($error != "") { ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Reservation ID</label>
            <input type="number" name="reservation_id" class="form-control" placeholder="Enter Reservation ID" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Reason</label>
            <textarea name="reason" class="form-control" rows="4" placeholder="Explain your issue..." required></textarea>
        </div>

        <button type="submit" name="submit" class="btn btn-danger w-100">
            Submit Dispute
        </button>

    </form>

</div>

</div>
</div>
</div>

</body>
</html> 

