<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian'){
    die("Access denied");
}

require_once("../config/Database.php");
require_once("../models/maintenance.php");

$db = new Database();
$conn = $db->connect();

$maintenance = new Maintenance($conn);

/* ================= ACTIONS ================= */

if(isset($_GET['action'], $_GET['id'])){

    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if($action == 'progress'){

        // 1. نجيب tool_id
        $q = $conn->prepare("
            SELECT tool_id 
            FROM maintenance 
            WHERE maintenance_id=?
        ");
        $q->bind_param("i", $id);
        $q->execute();
        $tool = $q->get_result()->fetch_assoc();

        $tool_id = $tool['tool_id'];

        // 2. تحديث حالة الصيانة
        $maintenance->updateStatus($id, 'in_progress');

        // 3. منع استخدام الأداة أثناء الصيانة
        $stmt = $conn->prepare("
            UPDATE tools 
            SET availability_status='maintenance'
            WHERE tool_id=?
        ");
        $stmt->bind_param("i", $tool_id);
        $stmt->execute();

    }

    elseif($action == 'complete'){

        // 1. نجيب tool_id من maintenance
        $q = $conn->prepare("
            SELECT tool_id 
            FROM maintenance 
            WHERE maintenance_id=?
        ");
        $q->bind_param("i", $id);
        $q->execute();
        $tool = $q->get_result()->fetch_assoc();

        $tool_id = $tool['tool_id'];

        // 2. تحديث الصيانة
        $maintenance->updateStatus($id, 'completed');

        // 3. ترجع الأداة متاحة
        $stmt = $conn->prepare("
            UPDATE tools 
            SET availability_status='available'
            WHERE tool_id=?
        ");
        $stmt->bind_param("i", $tool_id);
        $stmt->execute();

        header("Location: maintenance.php");
        exit();
    }

    header("Location: maintenance.php");
    exit();
}

/* ================= GET DATA ================= */

$data = $maintenance->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maintenance Requests</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    background:#f3f4f6;
    font-family:Arial;
}

.wrapper{
    max-width:1100px;
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

.status{
    padding:6px 12px;
    border-radius:10px;
    color:white;
    font-size:13px;
}

.pending{
    background:#f59e0b;
}

.in_progress{
    background:#3b82f6;
}

.completed{
    background:#22c55e;
}

.btn-custom{
    border-radius:10px;
    padding:7px 14px;
    text-decoration:none;
    color:white;
    font-size:14px;
}

.btn-progress{
    background:#3b82f6;
}

.btn-complete{
    background:#22c55e;
}

</style>
</head>

<body>

<div class="wrapper">

    <div class="page-title">
        🛠 Maintenance Requests
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
                            Reported by:
                            <?= htmlspecialchars($row['full_name']) ?>
                        </small>
                    </div>

                    <div>
                        <span class="status <?= $row['status'] ?>">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </div>

                </div>

                <hr>

                <p>
                    <strong>Issue:</strong><br>
                    <?= nl2br(htmlspecialchars($row['issue_description'])) ?>
                </p>

                <small class="text-muted">
                    Created at:
                    <?= $row['created_at'] ?>
                </small>

                <div class="mt-3">

                    <?php if($row['status'] == 'pending'): ?>

                        <a href="?action=progress&id=<?= $row['maintenance_id'] ?>"
                        class="btn-custom btn-progress">
                            Start Progress
                        </a>

                    <?php endif; ?>

                    <?php if($row['status'] == 'in_progress'): ?>

                        <a href="?action=complete&id=<?= $row['maintenance_id'] ?>"
                        class="btn-custom btn-complete">
                            Mark Completed
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="alert alert-info">
            No maintenance requests found.
        </div>

    <?php endif; ?>

</div>

</body>
</html>