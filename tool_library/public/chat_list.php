<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once("../config/Database.php");
require_once("../models/user.php");

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

/* 
   استعلام لجلب كل الأشخاص الذين تواصلت معهم.
   الاستعلام بيبحث عن أي شخص أرسلت له أو استلمت منه رسالة،
   ويجيب اسمه وصورته الرمزية.
*/
$sql = "SELECT user_id, full_name, email FROM users WHERE user_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$contacts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .contact-card {
            transition: 0.3s;
            border-radius: 12px;
            border: none;
            margin-bottom: 10px;
        }
        .contact-card:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        .avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="fa fa-comments text-primary me-2"></i>Messages</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
            </div>

            <div class="card shadow-sm border-0 p-3">
                <?php if($contacts->num_rows > 0): ?>
                    <?php while($user = $contacts->fetch_assoc()): ?>
                        <a href="chat.php?user=<?php echo $user['user_id']; ?>" class="text-decoration-none text-dark">
                            <div class="card contact-card p-3">
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random" class="avatar me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold"><?php echo $user['full_name']; ?></h6>
                                        <small class="text-muted"><?php echo $user['email']; ?></small>
                                    </div>
                                    <i class="fa fa-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa fa-comment-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No conversations found yet.</p>
                        <a href="dashboard.php" class="btn btn-primary">Browse Tools to Contact Owners</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>