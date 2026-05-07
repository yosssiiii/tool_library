<?php

session_start();

require_once("../config/Database.php");
require_once("../models/reservation.php");
require_once("../models/tool.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {

    $db = new Database();
    $conn = $db->connect();

    $reservationModel = new Reservation($conn);
    $toolModel        = new Tool($conn);

    /* ================= DATA ================= */

    $user_id      = $_SESSION['user_id'];

    $tool_id      = (int) $_POST['tool_id'];

    $start_date   = $_POST['start_date'];

    $end_date     = $_POST['end_date'];

    $rental_type  = $_POST['rental_type'];

    /* ================= TOOL ================= */

    $tool = $toolModel->getToolById($tool_id);

    if(!$tool){
        die("Tool not found");
    }

    /* ================= VALIDATION ================= */

    if(strtotime($end_date) <= strtotime($start_date)){
        die("End date must be after start date.");
    }

    /* ================= PRICE ENGINE ================= */

    $start = strtotime($start_date);

    $end   = strtotime($end_date);

    $hours = ceil(($end - $start) / 3600);

    $total_price = 0;

    if($rental_type == 'hour'){

        $total_price = $hours * $tool['price_per_hour'];

    }

    elseif($rental_type == 'day'){

        $days = ceil($hours / 24);

        $total_price = $days * $tool['price_per_day'];

    }

    elseif($rental_type == 'week'){

        $weeks = ceil($hours / (24 * 7));

        $total_price = $weeks * $tool['price_per_week'];

    }

    else{

        die("Invalid rental type");

    }

    /* ================= DEPOSIT ================= */

    $deposit = $total_price * 0.30;

    /* ================= RESERVE ================= */

    $success = $reservationModel->reserve(

        $user_id,
        $tool_id,
        $start_date,
        $end_date,
        $total_price,
        $deposit,
        $rental_type

    );

    if($success){

        $reservation_id = $conn->insert_id;

        header("Location: payment.php?id=" . $reservation_id);

        exit();

    } else {

        die("Reservation failed.");

    }

} else {

    header("Location: login.php");

    exit();

}