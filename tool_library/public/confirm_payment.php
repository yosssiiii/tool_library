<?php
session_start();
require_once("../config/Database.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$reservation_id = (int)($_POST['id'] ?? 0);

if(!$reservation_id){
    die("Invalid request");
}

/* 1. تأكيد إن الحجز موجود */
$stmt = $conn->prepare("
    SELECT * FROM reservations
    WHERE reservation_id=? AND user_id=?
");
$stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if(!$res){
    die("Reservation not found");
}

$total   = $res['total_price'];
$deposit = $res['deposit_amount'];

/* 2. تسجيل الدفع الأساسي (Rental) */
$p1 = $conn->prepare("
    INSERT INTO payments (reservation_id, user_id, amount, payment_type, status)
    VALUES (?, ?, ?, 'rental', 'paid')
");
$p1->bind_param("iid", $reservation_id, $_SESSION['user_id'], $total);
$p1->execute();

/* 3. تسجيل التأمين (Held) */
$p2 = $conn->prepare("
    INSERT INTO payments (reservation_id, user_id, amount, payment_type, status)
    VALUES (?, ?, ?, 'deposit', 'held')
");
$p2->bind_param("iid", $reservation_id, $_SESSION['user_id'], $deposit);
$p2->execute();

/* 4. تحديث حالة الحجز */
$u = $conn->prepare("
    UPDATE reservations
    SET payment_status='paid',
        status='approved'
    WHERE reservation_id=?
");
$u->bind_param("i", $reservation_id);
$u->execute();

/* 5. تحويل الأداة لحالة active (اختياري حسب نظامك) */
$t = $conn->prepare("
    UPDATE tools t
    JOIN reservations r ON t.tool_id = r.tool_id
    SET t.availability_status='reserved'
    WHERE r.reservation_id=?
");
$t->bind_param("i", $reservation_id);
$t->execute();

/* 6. تحويل المستخدم */
header("Location: my_reservation.php");
exit();
?>