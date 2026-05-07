<?php
session_start();
require_once("../config/Database.php");

/* ================= AUTH ================= */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

/* ================= ACTIONS ================= */
if(isset($_GET['action'], $_GET['id'])){

    $id = intval($_GET['id']);
    $action = $_GET['action'];

    switch($action){
        case "approve":
            $sql = "UPDATE reservations SET status='approved' WHERE reservation_id=?";
            break;

        case "reject":
            $sql = "UPDATE reservations SET status='rejected' WHERE reservation_id=?";
            break;

        case "handover":
            $sql = "UPDATE reservations SET status='active' WHERE reservation_id=?";
            break;

        case "return":
            $sql = "UPDATE reservations SET status='completed' WHERE reservation_id=?";
            break;

        default:
            $sql = null;
    }

    if($sql){
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i",$id);
        $stmt->execute();
    }

    header("Location: my_rentals.php");
    exit();
}

/* ================= GET DATA ================= */
$sql = "SELECT 
            reservations.reservation_id,
            reservations.start_date,
            reservations.end_date,
            reservations.status,
            tools.tool_name,
            users.full_name
        FROM reservations
        JOIN tools ON reservations.tool_id = tools.tool_id
        JOIN users ON reservations.user_id = users.user_id
        WHERE tools.owner_id = ?
        ORDER BY reservations.reservation_id DESC";

$stmt = $conn->prepare($sql);

if(!$stmt){
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i",$user_id);

if(!$stmt->execute()){
    die("Execute Error: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Rentals</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f4f6f9;
            font-family: Arial;
        }

        .container-box{
            max-width:1000px;
            margin:40px auto;
        }

        .title{
            font-size:24px;
            font-weight:bold;
            margin-bottom:20px;
        }

        .card-rental{
            background:white;
            padding:20px;
            border-radius:15px;
            box-shadow:0 5px 15px rgba(0,0,0,0.08);
            margin-bottom:20px;
            transition:0.2s;
        }

        .card-rental:hover{
            transform:translateY(-3px);
        }

        .status{
            padding:5px 10px;
            border-radius:8px;
            font-size:13px;
            color:white;
        }

        .pending{background:#f59e0b;}
        .approved{background:#22c55e;}
        .active{background:#3b82f6;}
        .completed{background:#6b7280;}
        .rejected{background:#ef4444;}

        .btn-action{
            padding:6px 12px;
            border-radius:8px;
            font-size:13px;
            text-decoration:none;
            margin-right:5px;
        }

        .approve{background:#22c55e;color:white;}
        .reject{background:#ef4444;color:white;}
        .handover{background:#3b82f6;color:white;}
        .return{background:#f59e0b;color:white;}
    </style>
</head>

<body>

<div class="container-box">

    <div class="title">📦 My Rentals (Lender Dashboard)</div>

    <?php if($result && $result->num_rows > 0): ?>

        <?php while($row = $result->fetch_assoc()): ?>

            <div class="card-rental">

                <div class="d-flex justify-content-between">
                    <div>
                        <h5><?= $row['tool_name']; ?></h5>
                        <small class="text-muted">Rented by <?= $row['full_name']; ?></small>
                    </div>

                    <div>
                        <span class="status <?= $row['status']; ?>">
                            <?= strtoupper($row['status']); ?>
                        </span>
                    </div>
                </div>

                <hr>

                <p>
                    <b>From:</b> <?= $row['start_date']; ?> <br>
                    <b>To:</b> <?= $row['end_date']; ?>
                </p>

                <div>

                <?php if($row['status'] == 'pending'): ?>

                    <a href="?action=approve&id=<?= $row['reservation_id']; ?>"
                        class="btn-action approve">✔ Approve</a>

                    <a href="?action=reject&id=<?= $row['reservation_id']; ?>"
                        class="btn-action reject">✖ Reject</a>

                <?php elseif($row['status'] == 'approved'): ?>

                    <a href="?action=handover&id=<?= $row['reservation_id']; ?>"
                        class="btn-action handover">🚚 Confirm Handover</a>

                <?php elseif($row['status'] == 'active'): ?>

                    <a href="dispute.php?reservation_id=<?= $row['reservation_id'] ?>"
                        class="btn btn-danger btn-sm">
                        ⚠ Handle Issue
                        </a>
                    
                <?php elseif($row['status'] == 'completed'): ?>
                    <a href="reviews.php?user_id=<?= $row['user_id'] ?>"
                        class="btn btn-info btn-sm">
                        ⭐ Reviews
                        </a>


                <?php else: ?>
                    <span class="text-muted">No actions</span>
                <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="alert alert-info text-center">
            No rentals found 📭
        </div>

    <?php endif; ?>

</div>

</body>
</html>