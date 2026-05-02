<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

$id = $_GET['id'];

$sql = "SELECT reservations.*, tools.tool_name 
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        WHERE reservation_id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$total = $res['total_price'] + $res['deposit_amount'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

<div class="card p-4 shadow">

<h3 class="mb-4">Payment for <?php echo $res['tool_name']; ?></h3>

<p>Rental Price: <b>$<?php echo $res['total_price']; ?></b></p>
<p>Deposit: <b>$<?php echo $res['deposit_amount']; ?></b></p>

<hr>

<h4>Total: $<?php echo $total; ?></h4>

<form action="confirm_payment.php" method="POST">

<input type="hidden" name="id" value="<?php echo $id; ?>">

<!-- Fake Card UI -->

<input class="form-control mb-2" placeholder="Card Number" required>

<input class="form-control mb-2" placeholder="Expiry Date" required>

<input class="form-control mb-2" placeholder="CVV" required>

<button class="btn btn-success w-100 mt-3">
Pay Now
</button>

</form>

</div>

</div>

</body>
</html>