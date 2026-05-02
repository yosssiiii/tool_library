<?php
// هذا الملف يفتحه المستأجر بعد عمل سكان
require_once("../config/Database.php");
$db = new Database(); $conn = $db->connect();

$id = $_GET['id'];
$sql = "UPDATE reservations SET status='active' WHERE reservation_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

echo "<h1>Success! You have confirmed the handover.</h1>";
?>