<?php
session_start();
require_once("../config/Database.php");
require_once("../models/reservation.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {

    $db = new Database();
    $conn = $db->connect();

    $reservation = new Reservation($conn);

    // البيانات
    $user_id = $_SESSION['user_id'];
    $tool_id = $_POST['tool_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $price_per_day = $_POST['price_per_day'];

    // تنفيذ
if ($reservation->reserve($user_id, $tool_id, $start_date, $end_date, $price_per_day)) {

    // نجيب آخر ID اتضاف
    $reservation_id = $conn->insert_id;

    header("Location: payment.php?id=" . $reservation_id);
    exit();

} else {
    echo "Error while reserving.";
}

} else {
    header("Location: login.php");
}