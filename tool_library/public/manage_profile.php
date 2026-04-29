<?php
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.php");

require_once("../config/Database.php");
require_once("../models/User.php");

$db = new Database();
$conn = $db->connect();
$userModel = new User($conn);

$message = "";
$userId = $_SESSION['user_id'];

if(isset($_POST['update_profile'])){
    if($userModel->updateFullProfile($userId, $_POST['name'], $_POST['email'], $_POST['password'], $_POST['phone'], $_POST['address'])){
        $message = "<div class='alert alert-success'>Profile Updated Successfully!</div>";
    }
}


if(isset($_POST['upgrade_pro'])){
    if($userModel->upgradeToPro($userId)){
        $message = "<div class='alert alert-gold' style='background:#fff3cd; color:#856404;'>Congratulations! You are now a PRO Member.</div>";
    }
}

$userData = $userModel->getUser($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Profile - Tool Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .profile-card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .pro-card { 
            background: linear-gradient(135deg, #ffd700, #ffa500);
            color: #000; border: none; border-radius: 15px;
        }
        .btn-upgrade { background: #000; color: #fff; border-radius: 10px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card profile-card p-4">
                <h4 class="mb-4">Edit Profile Information</h4>
                <?php echo $message; ?>
                
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $userData['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $userData['email']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" value="<?php echo $userData['password']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $userData['phone']; ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" required><?php echo $userData['address']; ?></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary px-5">Save Changes</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Back to Dashboard</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <?php if($userData['membership_type'] != 'pro'): ?>
            <div class="card pro-card p-4 text-center shadow">
                <i class="fa fa-crown fa-3x mb-3"></i>
                <h5>Upgrade to PRO</h5>
                <p class="small">Unlock "Boost Tool" logic, get discounts, and exclusive trust score badges!</p>
                <h3 class="mb-3">$30 <span class="small" style="font-size:14px">/one time</span></h3>
                <form method="POST">
                    <button type="submit" name="upgrade_pro" class="btn btn-upgrade w-100 py-2">
                        Upgrade Now
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="card p-4 text-center border-0 shadow-sm" style="background: #e9ecef;">
                <i class="fa fa-circle-check fa-3x text-success mb-3"></i>
                <h5>Pro Member Active</h5>
                <p class="text-muted">You are currently enjoying all premium features.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>