<?php
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

/* Actions */
if(isset($_GET['action']) && $_GET['action'] == 'approve'){
    $id = intval($_GET['id']);
    $conn->query("UPDATE users SET status='approved' WHERE user_id=$id");
    header("Location: users.php?filter=pending");
    exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'reject'){
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: users.php?filter=pending");
    exit();
}

/* Filter */
$filter = $_GET['filter'] ?? 'all';

if($filter == 'pending'){
    $result = $conn->query("SELECT * FROM users WHERE status='pending'");
} else {
    $result = $conn->query("SELECT * FROM users");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f4f6f9;
            font-family: Arial;
        }

        .container-box{
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .title{
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .table thead{
            background: #3b82f6;
            color: white;
        }

        .btn-approve{
            background:#22c55e;
            color:white;
            border-radius:8px;
            padding:5px 12px;
            text-decoration:none;
        }

        .btn-reject{
            background:#ef4444;
            color:white;
            border-radius:8px;
            padding:5px 12px;
            text-decoration:none;
        }

        .btn-approve:hover{background:#16a34a;}
        .btn-reject:hover{background:#dc2626;}

        .badge-approved{
            background:#16a34a;
            padding:6px 10px;
            border-radius:8px;
            color:white;
        }

        .top-bar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }

        .filter-btn{
            padding:6px 12px;
            border-radius:8px;
            text-decoration:none;
            background:#e5e7eb;
            color:#111;
            margin-left:5px;
        }

        .filter-btn.active{
            background:#3b82f6;
            color:white;
        }
    </style>
</head>

<body>

<div class="container-box">

    <div class="top-bar">
        <div class="title">👥 Users Management</div>

        <div>
            <a class="filter-btn <?= $filter=='all'?'active':'' ?>" href="users.php?filter=all">All</a>
            <a class="filter-btn <?= $filter=='pending'?'active':'' ?>" href="users.php?filter=pending">Pending</a>
        </div>
    </div>

    <table class="table table-hover text-center align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['user_id'] ?></td>
                <td><?= $row['full_name'] ?></td>
                <td><?= $row['email'] ?></td>

                <td>
                    <?php if($row['status'] == 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                    <?php else: ?>
                        <span class="badge-approved">Approved</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if($row['status'] == 'pending'): ?>
                        <a class="btn-approve"
                            href="users.php?action=approve&id=<?= $row['user_id'] ?>&filter=pending">
                            ✔ Approve
                        </a>

                        <a class="btn-reject"
                            href="users.php?action=reject&id=<?= $row['user_id'] ?>&filter=pending">
                            ✖ Reject
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No actions</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>

    </table>

</div>

</body>
</html>