<?php
session_start();

require_once("../config/Database.php");
require_once("../models/payment.php");

$db = new Database();
$conn = $db->connect();

$payment = new Payment($conn);

$data = $payment->getMyPayments(
$_SESSION['user_id']
);

while($row = $data->fetch_assoc()){

echo "<hr>";

echo "Tool: ".$row['tool_name']."<br>";

echo "Amount: ".$row['amount']."<br>";

echo "Deposit: ".$row['deposit']."<br>";

echo "Status: ".$row['payment_status']."<br>";

echo "Date: ".$row['payment_date']."<br>";

echo "<a href='payment.php?reservation_id=".$row['reservation_id']."&amount=".$row['total_price']."'>Pay</a>";

}
?>