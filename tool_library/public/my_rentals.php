<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

/* ================= ACTION HANDLER ================= */

if(isset($_GET['action']) && isset($_GET['id'])){

$id = $_GET['id'];
$action = $_GET['action'];

if($action == "approve"){
    $sql = "UPDATE reservations SET status='approved' WHERE reservation_id=?";
}
elseif($action == "reject"){
    $sql = "UPDATE reservations SET status='rejected' WHERE reservation_id=?";
}
elseif($action == "handover"){
    $sql = "UPDATE reservations SET status='active' WHERE reservation_id=?";
}
elseif($action == "return"){
    $sql = "UPDATE reservations SET status='completed' WHERE reservation_id=?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: my_rentals.php");
exit();

}

/* ================= GET DATA ================= */

$sql = "SELECT reservations.*, tools.tool_name, users.full_name
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        JOIN users ON reservations.user_id = users.user_id
        WHERE tools.owner_id = ?
        ORDER BY reservations.reservation_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();

$data = $stmt->get_result();
?>

<h2>My Rentals (Lender Dashboard)</h2>

<?php while($row = $data->fetch_assoc()){ ?>

<hr>

<b>Tool:</b> <?php echo $row['tool_name']; ?><br>
<b>User:</b> <?php echo $row['full_name']; ?><br>
<b>From:</b> <?php echo $row['start_date']; ?><br>
<b>To:</b> <?php echo $row['end_date']; ?><br>
<b>Status:</b> <?php echo $row['status']; ?><br><br>

<?php if($row['status'] == 'pending'){ ?>

<a href="?action=approve&id=<?php echo $row['reservation_id']; ?>" class="btn btn-success">Approve</a>

<a href="?action=reject&id=<?php echo $row['reservation_id']; ?>" class="btn btn-danger">Reject</a>

<?php } ?>

<?php if($row['status'] == 'approved'){ ?>

<a href="?action=handover&id=<?php echo $row['reservation_id']; ?>" class="btn btn-primary">
Confirm Handover
</a>

<?php } ?>

<?php if($row['status'] == 'active'){ ?>

<a href="?action=return&id=<?php echo $row['reservation_id']; ?>" class="btn btn-warning">
Return Tool
</a>

<?php } ?>

<?php } ?>