<?php
session_start();

require_once("../config/Database.php");
require_once("../models/payment.php");

$db = new Database();
$conn = $db->connect();

$payment = new Payment($conn);

$reservation_id = $_GET['reservation_id'];
$amount = $_GET['amount'];

$deposit = $amount * 0.30;

if(isset($_POST['pay'])){

$payment->makePayment(
$reservation_id,
$amount,
$deposit
);

echo "Payment Successful";
}
?>

<h2>Payment Page</h2>

Rental Amount:
<?php echo $amount; ?><br>

Deposit:
<?php echo $deposit; ?><br><br>

<form method="POST">

<button name="pay">
Pay Now
</button>

</form>