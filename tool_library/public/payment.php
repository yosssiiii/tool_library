<?php
session_start();
require_once("../config/Database.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$reservation_id = (int)($_GET['id'] ?? 0);

if(!$reservation_id){
    die("Invalid reservation");
}

/* جلب بيانات الحجز */
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

$total = $res['total_price'];
$deposit = $res['deposit_amount'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    // 1. تسجيل الدفع الأساسي (الإيجار)
    $p1 = $conn->prepare("
        INSERT INTO payments (reservation_id, user_id, amount, payment_type, status)
        VALUES (?, ?, ?, 'rental', 'paid')
    ");
    $p1->bind_param("iid", $reservation_id, $_SESSION['user_id'], $total);
    $p1->execute();

    // 2. تسجيل التأمين (held)
    $p2 = $conn->prepare("
        INSERT INTO payments (reservation_id, user_id, amount, payment_type, status)
        VALUES (?, ?, ?, 'deposit', 'held')
    ");
    $p2->bind_param("iid", $reservation_id, $_SESSION['user_id'], $deposit);
    $p2->execute();

    // 3. تحديث الحجز
    $u = $conn->prepare("
        UPDATE reservations
        SET payment_status='paid'
        WHERE reservation_id=?
    ");
    $u->bind_param("i", $reservation_id);
    $u->execute();

    header("Location: my_reservation.php");
    exit();
}
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

<h3>Payment Summary</h3>

<p>Rental Fee: <b>$<?= $total ?></b></p>
<p>Deposit: <b>$<?= $deposit ?></b></p>

<hr>

<h4>Total: $<?= $total + $deposit ?></h4>

<form method="POST">

<input class="form-control mb-2" placeholder="Card Number" required>
<input class="form-control mb-2" placeholder="Expiry" required>
<input class="form-control mb-2" placeholder="CVV" required>

<button class="btn btn-success w-100 mt-3">
Pay Now
</button>

</form>

</div>

</div>

</body>
</html>