<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once("../config/Database.php");
require_once("../models/reservation.php");

$db = new Database();
$conn = $db->connect();
$resModel = new Reservation($conn);

$user_id = $_SESSION['user_id'];
$myRentals = $resModel->getUserRentals($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Rentals - Tool Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .rental-card { border: none; border-radius: 15px; background: #fff; transition: 0.3s; }
        .tool-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .bg-pending { background: #fef3c7; color: #92400e; }
        .bg-approved { background: #dcfce7; color: #166534; }
        .bg-active { background: #dbeafe; color: #1e40af; }
        .bg-completed { background: #f1f5f9; color: #475569; }
        .bg-rejected { background: #fee2e2; color: #991b1b; }
        .status-badge{display:inline-block;margin-bottom:6px;}
        .btn-sm{font-size: 12px;border-radius: 8px;}
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fa fa-clock-rotate-left me-2 text-primary"></i>My Rentals</h2>
            <p class="text-muted">Track your borrowed tools and their status</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card rental-card shadow-sm p-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>Tool</th>
                                <th>Owner</th>
                                <th>Duration</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($myRentals->num_rows > 0): ?>
                            <?php while($row = $myRentals->fetch_assoc()): ?>

                            <tr>

                                <!-- TOOL -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php $img = !empty($row['photo']) ? $row['photo'] : "https://via.placeholder.com/60"; ?>
                                        <img src="<?php echo $img; ?>" class="tool-thumb me-3">
                                        <span class="fw-bold"><?php echo htmlspecialchars($row['tool_name']); ?></span>
                                    </div>
                                </td>

                                <!-- OWNER -->
                                <td>
                                    <small class="text-muted">
                                        <i class="fa fa-user me-1"></i>
                                        <?php echo htmlspecialchars($row['owner_name']); ?>
                                    </small>
                                </td>

                                <!-- DURATION -->
                                <td>
                                    <div class="small">
                                        <div><strong>From:</strong> <?php echo $row['start_date']; ?></div>
                                        <div><strong>To:</strong> <?php echo $row['end_date']; ?></div>
                                    </div>
                                </td>

                                <!-- COST -->
                                <td>
                                    <span class="fw-bold text-dark">
                                        $<?php echo number_format($row['total_price'], 2); ?>
                                    </span>
                                </td>

                                <!-- STATUS + ACTIONS -->
                                <td>
                                    
                                    <!-- STATUS -->
                                    <span class="status-badge bg-<?php echo $row['status']; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>

                                    <div class="mt-2 d-flex flex-column gap-1">

                                        <!-- Scan -->
                                        <?php if($row['status'] == 'approved'): ?>
                                            <a href="scan_qr.php?id=<?= $row['reservation_id'] ?>" 
                                            class="btn btn-primary btn-sm">
                                                <i class="fa fa-camera"></i> Scan Handover
                                            </a>
                                        <?php endif; ?>

                                        <!-- Handle Issue (ACTIVE only) -->
                                        <?php if($row['status'] == 'active'): ?>
                                            <a href="dispute.php?reservation_id=<?= $row['reservation_id'] ?>" 
                                            class="btn btn-danger btn-sm">
                                                ⚠ Handle Issue
                                            </a>
                                        <?php endif; ?>

                                        <!-- General Dispute (completed or active) -->
                                        <?php if(in_array($row['status'], ['active','completed'])): ?>
                                            <a href="dispute.php?reservation_id=<?= $row['reservation_id'] ?>" 
                                            class="btn btn-outline-danger btn-sm">
                                                ⚠ Dispute
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                                <td>
                                <?php if($row['status'] == 'active'): ?>

                                    <a href="return_tool.php?id=<?= $row['reservation_id'] ?>" 
                                    class="btn btn-success btn-sm">
                                        🚚 Return Tool
                                    </a>

                                <?php endif; ?>
                                </td>
                                <td>
                                <?php if($row['status'] == 'completed'): ?>
                                    <a href="review.php?reservation_id=<?= $row['reservation_id'] ?>" 
                                    class="btn btn-warning btn-sm">
                                        ⭐ Review
                                    </a>
                                <?php endif; ?>
                                </td>

                            </tr>

                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa fa-calendar-xmark fa-3x mb-3"></i>
                                    <p>You haven't requested any rentals yet.</p>
                                    <a href="search_tool.php" class="btn btn-primary btn-sm">Browse Tools</a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>