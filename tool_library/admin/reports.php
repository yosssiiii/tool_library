<?php
session_start();
require_once("../config/Database.php");

/* 🔐 حماية */
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian'){
    die("Access denied");
}

$db = new Database();
$conn = $db->connect();

/* ================== STATS ================== */

// Users
$total_users     = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$pending_users   = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='pending'")->fetch_assoc()['c'];
$approved_users  = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='approved'")->fetch_assoc()['c'];

// Tools
$total_tools     = $conn->query("SELECT COUNT(*) as c FROM tools")->fetch_assoc()['c'];
$available_tools = $conn->query("SELECT COUNT(*) as c FROM tools WHERE availability_status='available'")->fetch_assoc()['c'];
$hidden_tools    = $conn->query("SELECT COUNT(*) as c FROM tools WHERE availability_status='reserved'")->fetch_assoc()['c'];

// Rentals
$total_rentals   = $conn->query("SELECT COUNT(*) as c FROM reservations")->fetch_assoc()['c'];
$active_rentals  = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE payment_status='paid'")->fetch_assoc()['c'];
$completed       = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE payment_status='pending'")->fetch_assoc()['c'];

// Disputes
$total_disputes  = $conn->query("SELECT COUNT(*) as c FROM disputes")->fetch_assoc()['c'];
$resolved        = $conn->query("SELECT COUNT(*) as c FROM disputes WHERE status='resolved'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f4f6f9;
            font-family: Arial;
        }

        .container-box{
            max-width:1100px;
            margin:40px auto;
        }

        .title{
            font-size:26px;
            font-weight:bold;
            margin-bottom:25px;
        }

        .card-box{
            background:white;
            padding:20px;
            border-radius:15px;
            box-shadow:0 5px 15px rgba(0,0,0,0.08);
            text-align:center;
        }

        .card-title{
            font-size:16px;
            color:#555;
        }

        .card-number{
            font-size:28px;
            font-weight:bold;
        }
    </style>
</head>

<body>

<div class="container-box">

    <div class="title">📊 Admin Reports Dashboard</div>

    <div class="row g-4">

        <!-- USERS -->
        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Total Users</div>
                <div class="card-number"><?= $total_users ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Pending Users</div>
                <div class="card-number text-warning"><?= $pending_users ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Approved Users</div>
                <div class="card-number text-success"><?= $approved_users ?></div>
            </div>
        </div>

        <!-- TOOLS -->
        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Total Tools</div>
                <div class="card-number"><?= $total_tools ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Available Tools</div>
                <div class="card-number text-success"><?= $available_tools ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Hidden Tools</div>
                <div class="card-number text-danger"><?= $hidden_tools ?></div>
            </div>
        </div>

        <!-- RENTALS -->
        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Total Rentals</div>
                <div class="card-number"><?= $total_rentals ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">Active Rentals</div>
                <div class="card-number text-primary"><?= $active_rentals ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box">
                <div class="card-title">pending Rentals</div>
                <div class="card-number text-secondary"><?= $completed ?></div>
            </div>
        </div>

        <!-- DISPUTES -->
        <div class="col-md-6">
            <div class="card-box">
                <div class="card-title">Total Disputes</div>
                <div class="card-number"><?= $total_disputes ?></div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-box">
                <div class="card-title">Resolved Disputes</div>
                <div class="card-number text-success"><?= $resolved ?></div>
            </div>
        </div>

    </div>

</div>

</body>
</html>