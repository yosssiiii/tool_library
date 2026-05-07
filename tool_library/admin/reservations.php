<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian'){
    die("Access denied");
}

require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

/* ================= ACTIONS ================= */

if(isset($_GET['action'], $_GET['id'])){

    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    $queries = [

        'approve' => "
            UPDATE reservations
            SET status='approved'
            WHERE reservation_id=?
        ",

        'reject' => "
            UPDATE reservations
            SET status='rejected'
            WHERE reservation_id=?
        ",

        'activate' => "
            UPDATE reservations
            SET status='active'
            WHERE reservation_id=?
        ",

        'complete' => "
            UPDATE reservations
            SET status='completed'
            WHERE reservation_id=?
        ",

        'paid' => "
            UPDATE reservations
            SET payment_status='paid'
            WHERE reservation_id=?
        ",

        'refund' => "
            UPDATE reservations
            SET payment_status='refunded'
            WHERE reservation_id=?
        "
    ];

    if(isset($queries[$action])){

        $stmt = $conn->prepare($queries[$action]);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: reservations.php");
    exit();
}

/* ================= GET DATA ================= */

$sql = "SELECT 

            r.*,

            t.tool_name,

            borrower.full_name AS borrower_name,

            owner.full_name AS owner_name

        FROM reservations r

        JOIN tools t
        ON r.tool_id = t.tool_id

        JOIN users borrower
        ON r.user_id = borrower.user_id

        JOIN users owner
        ON t.owner_id = owner.user_id

        ORDER BY r.reservation_id DESC";

$data = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<title>Reservations Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    background:#f3f4f6;
    font-family:Arial;
}

.wrapper{
    max-width:1200px;
    margin:40px auto;
}

.page-title{
    font-size:28px;
    font-weight:bold;
    margin-bottom:25px;
}

.card-box{
    background:white;
    border-radius:16px;
    padding:22px;
    margin-bottom:20px;
    box-shadow:0 5px 15px rgba(0,0,0,.07);
}

.badge-status{
    padding:7px 12px;
    border-radius:10px;
    color:white;
    font-size:13px;
}

.pending{ background:#f59e0b; }
.approved{ background:#3b82f6; }
.active{ background:#8b5cf6; }
.completed{ background:#22c55e; }
.rejected{ background:#ef4444; }

.btn-custom{
    border:none;
    padding:8px 14px;
    border-radius:10px;
    color:white;
    text-decoration:none;
    font-size:13px;
    margin-right:6px;
    display:inline-block;
    margin-top:5px;
}

.btn-approve{ background:#22c55e; }
.btn-reject{ background:#ef4444; }
.btn-active{ background:#3b82f6; }
.btn-complete{ background:#8b5cf6; }
.btn-paid{ background:#14b8a6; }
.btn-refund{ background:#64748b; }

.payment-paid{
    color:#22c55e;
    font-weight:bold;
}

.payment-pending{
    color:#f59e0b;
    font-weight:bold;
}

.payment-refunded{
    color:#64748b;
    font-weight:bold;
}

</style>
</head>

<body>

<div class="wrapper">

    <div class="page-title">
        📦 Reservations Management
    </div>

    <?php if($data && $data->num_rows > 0): ?>

        <?php while($row = $data->fetch_assoc()): ?>

            <div class="card-box">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-1">
                            <?= htmlspecialchars($row['tool_name']) ?>
                        </h5>

                        <small class="text-muted">
                            Borrower:
                            <?= htmlspecialchars($row['borrower_name']) ?>
                        </small>

                        <br>

                        <small class="text-muted">
                            Owner:
                            <?= htmlspecialchars($row['owner_name']) ?>
                        </small>

                    </div>

                    <div>

                        <span class="badge-status <?= $row['status'] ?>">
                            <?= strtoupper($row['status']) ?>
                        </span>

                    </div>

                </div>

                <hr>

                <p>
                    <strong>From:</strong>
                    <?= $row['start_date'] ?>
                </p>

                <p>
                    <strong>To:</strong>
                    <?= $row['end_date'] ?>
                </p>

                <p>
                    <strong>Total Price:</strong>
                    <?= $row['total_price'] ?> EGP
                </p>

                <p>
                    <strong>Deposit:</strong>
                    <?= $row['deposit_amount'] ?> EGP
                </p>

                <p>
                    <strong>Payment:</strong>

                    <span class="payment-<?= $row['payment_status'] ?>">
                        <?= strtoupper($row['payment_status']) ?>
                    </span>
                </p>

                <div class="mt-3">

                    <?php if($row['status'] == 'pending'): ?>

                        <a href="?action=approve&id=<?= $row['reservation_id'] ?>"
                        class="btn-custom btn-approve">
                            Approve
                        </a>

                        <a href="?action=reject&id=<?= $row['reservation_id'] ?>"
                        class="btn-custom btn-reject">
                            Reject
                        </a>

                    <?php endif; ?>

                    <?php if($row['status'] == 'approved'): ?>

                        <a href="?action=activate&id=<?= $row['reservation_id'] ?>"
                        class="btn-custom btn-active">
                            Activate
                        </a>

                    <?php endif; ?>

                    <?php if($row['status'] == 'active'): ?>

                        <a href="?action=complete&id=<?= $row['reservation_id'] ?>"
                        class="btn-custom btn-complete">
                            Complete
                        </a>

                    <?php endif; ?>

                    <?php if($row['payment_status'] == 'pending'): ?>

                        <a href="?action=paid&id=<?= $row['reservation_id'] ?>"
                        class="btn-custom btn-paid">
                            Mark Paid
                        </a>

                    <?php endif; ?>

                    <?php if($row['payment_status'] == 'paid'): ?>

                        <a href="?action=refund&id=<?= $row['reservation_id'] ?>"
                        class="btn-custom btn-refund">
                            Refund
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="alert alert-info">
            No reservations found.
        </div>

    <?php endif; ?>

</div>

</body>
</html>