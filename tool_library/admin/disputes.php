<?php
session_start();
require_once("../config/Database.php");

/* 🔐 حماية الأدمن */
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian'){
    die("Access denied");
}

$db = new Database();
$conn = $db->connect();

/* ================= ACTION ================= */
if (isset($_GET['action'], $_GET['id'])) {

    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    // get reservation id
    $q = $conn->prepare("SELECT reservation_id FROM disputes WHERE dispute_id=?");
    $q->bind_param("i", $id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();

    if ($res) {

        $reservation_id = $res['reservation_id'];

        if ($action == 'resolve') {
            $conn->query("UPDATE disputes SET status='resolved' WHERE dispute_id=$id");
        }

        elseif ($action == 'refund') {
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET payment_status='refunded', status='completed'
                WHERE reservation_id=?
            ");
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();

            $conn->query("UPDATE disputes SET status='resolved' WHERE dispute_id=$id");
        }

        elseif ($action == 'deduct') {

    // 1. تحديث الحجز (التأمين اتخصم)
    $stmt = $conn->prepare("
        UPDATE reservations
        SET payment_status='deposit_deducted',
            status='completed'
        WHERE reservation_id=?
    ");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();

    // 2. نجيب tool_id + user اللي عمل الحجز
    $t = $conn->prepare("
        SELECT tool_id, user_id 
        FROM reservations 
        WHERE reservation_id=?
    ");
    $t->bind_param("i", $reservation_id);
    $t->execute();
    $tool = $t->get_result()->fetch_assoc();

    $tool_id = $tool['tool_id'];
    $reported_by = $tool['user_id'];

    // 3. إدخال صيانة (صح حسب الجدول بتاعك)
    $m = $conn->prepare("
        INSERT INTO maintenance (tool_id, reported_by, issue_description, status)
        VALUES (?, ?, 'Damage reported after rental', 'pending')
    ");
    $m->bind_param("ii", $tool_id, $reported_by);
    $m->execute();

    // 4. قفل الـ dispute
    $stmt2 = $conn->prepare("
        UPDATE disputes
        SET status='resolved'
        WHERE dispute_id=?
    ");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
}
    }

    header("Location: disputes.php");
    exit();
}

/* ================= DATA ================= */
$sql = "SELECT 
            d.*,
            u.full_name,
            r.tool_id,
            t.tool_name
        FROM disputes d
        JOIN users u ON d.opened_by = u.user_id
        JOIN reservations r ON d.reservation_id = r.reservation_id
        JOIN tools t ON r.tool_id = t.tool_id
        ORDER BY d.dispute_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Disputes</title>

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
            margin-bottom:20px;
        }

        .card-box{
            background:white;
            padding:20px;
            border-radius:15px;
            box-shadow:0 5px 15px rgba(0,0,0,0.08);
            margin-bottom:20px;
        }

        .status{
            padding:5px 10px;
            border-radius:8px;
            font-size:13px;
            color:white;
        }

        .open{background:#ef4444;}
        .resolved{background:#22c55e;}

        .btn-resolve{
            background:#22c55e;
            color:white;
            padding:6px 12px;
            border-radius:8px;
            text-decoration:none;
            font-size:13px;
        }
    </style>
</head>

<body>

<div class="container-box">

    <div class="title">⚖️ Disputes Management</div>

    <?php if($result && $result->num_rows > 0): ?>

        <?php while($row = $result->fetch_assoc()): ?>

            <div class="card-box">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <h5 class="mb-1">
                            <?= htmlspecialchars($row['tool_name']); ?>
                        </h5>

                        <small class="text-muted">
                            Opened By:
                            <?= htmlspecialchars($row['full_name']); ?>
                        </small>
                    </div>

                    <div>
                        <span class="status <?= $row['status']; ?>">
                            <?= strtoupper($row['status']); ?>
                        </span>
                    </div>

                </div>

                <hr>

                <div class="mb-3">

                    <p class="mb-2">
                        <b>Reason:</b><br>
                        <?= nl2br(htmlspecialchars($row['reason'])); ?>
                    </p>

                    <p class="mb-1">
                        <b>Reservation ID:</b>
                        #<?= $row['reservation_id']; ?>
                    </p>

                    <p class="mb-0">
                        <b>Created At:</b>
                        <?= $row['created_at']; ?>
                    </p>

                </div>

                <?php if($row['status'] == 'open'): ?>

                    <div class="d-flex gap-2 flex-wrap">

                        <!-- Refund Deposit -->
                        <a href="?action=refund&id=<?= $row['dispute_id']; ?>"
                        class="btn btn-success btn-sm"
                        onclick="return confirm('Refund full deposit?')">

                            💰 Refund Deposit

                        </a>

                        <!-- Deduct Deposit -->
                        <a href="?action=deduct&id=<?= $row['dispute_id']; ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Deduct deposit because of damage?')">

                            ⚠ Deduct Deposit

                        </a>

                        <!-- Resolve -->
                        <a href="?action=resolve&id=<?= $row['dispute_id']; ?>"
                        class="btn btn-dark btn-sm"
                        onclick="return confirm('Mark dispute as resolved?')">

                            ✔ Resolve

                        </a>

                    </div>

                <?php else: ?>

                    <div class="alert alert-success mt-3 mb-0">
                        ✔ This dispute has been resolved
                    </div>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="alert alert-info text-center">
            No disputes found 📭
        </div>

    <?php endif; ?>

</div>

</body>
</html>