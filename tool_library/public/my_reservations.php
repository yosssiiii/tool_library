<?php
session_start();

require_once("../config/Database.php");
require_once("../models/reservation.php");

$db = new Database();
$conn = $db->connect();

$reservation = new Reservation($conn);

$data = $reservation->getMyReservations(
$_SESSION['user_id']
);

while($row = $data->fetch_assoc()){

echo "<hr>";

echo "Tool: ".$row['tool_name']."<br>";

echo "From: ".$row['start_date']."<br>";

echo "To: ".$row['end_date']."<br>";

echo "Price: ".$row['total_price']."<br>";

echo "Status: ".$row['status']."<br>";
}
?>