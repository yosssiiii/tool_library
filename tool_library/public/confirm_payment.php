<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

$id = $_POST['id'];

// تحديث الدفع
$sql = "UPDATE reservations SET payment_status='paid' WHERE reservation_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

echo "<script>
alert('Payment Successful ✅');
window.location='dashboard.php';
</script>";