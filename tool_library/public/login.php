<?php
session_start();
require_once("../config/Database.php");
require_once("../models/user.php");

// Already logged in? redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'librarian' || $_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: public/dashboard.php");
    }
    exit();
}

$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = new Database();
        $conn = $db->connect();
        $userModel = new User($conn);  // 👈 ده object

        $data = $userModel->login(
            $_POST['email'],
            $_POST['password']
        );

        if($data){

            if($data['status'] != 'approved'){
                die("Your account is pending approval by admin.");
            }else{
                $_SESSION['user_id'] = $data['user_id'];
                $_SESSION['role'] = $data['role'];

                if($data['role'] == 'librarian'){
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }

                exit();
            }

        }else{
            $message = "Wrong Email or Password";
        }

    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Tool Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #f1f5f9; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; align-items: center; }
  .login-card { background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,.1); width: 100%; max-width: 420px; }
  .brand { font-size: 1.5rem; font-weight: 800; color: #1e293b; }
  .brand span { color: #3b82f6; }
  .form-control { border-radius: 10px; padding: 12px 16px; border: 1.5px solid #e2e8f0; }
  .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
  .btn-login { background: #1e293b; color: #fff; border-radius: 10px; padding: 12px; font-weight: 600; border: none; width: 100%; }
  .btn-login:hover { background: #3b82f6; color: #fff; }
  .divider { color: #94a3b8; font-size: .85rem; }
  .role-hint { background: #eff6ff; border-radius: 10px; padding: 10px 14px; font-size: .82rem; color: #1e40af; }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-12">
      <div class="login-card mx-auto">

        <!-- Brand -->
        <div class="text-center mb-4">
          <div class="brand mb-1">🔧 Tool<span>Library</span></div>
          <p class="text-muted small">Sign in to your account</p>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="alert alert-danger rounded-3 py-2 small"><i class="fa fa-circle-xmark me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-600 small">Email Address</label>
            <div class="input-group">
              <span class="input-group-text border-end-0 bg-white border" style="border-radius:10px 0 0 10px"><i class="fa fa-envelope text-muted"></i></span>
              <input type="email" name="email" class="form-control border-start-0" style="border-radius:0 10px 10px 0"
                     placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-600 small">Password</label>
            <div class="input-group">
              <span class="input-group-text border-end-0 bg-white border" style="border-radius:10px 0 0 10px"><i class="fa fa-lock text-muted"></i></span>
              <input type="password" name="password" id="pw" class="form-control border-start-0" style="border-radius:0 10px 10px 0"
                     placeholder="Enter your password" required>
              <span class="input-group-text bg-white border border-start-0" style="border-radius:0 10px 10px 0;cursor:pointer" onclick="togglePw()">
                <i class="fa fa-eye text-muted" id="eyeIcon"></i>
              </span>
            </div>
          </div>

          <button type="submit" class="btn-login mb-3">
            <i class="fa fa-arrow-right-to-bracket me-2"></i>Sign In
          </button>
        </form>

        <div class="text-center divider mb-3">— or —</div>
        <div class="text-center small">
          Don't have an account? <a href="register.php" class="text-primary fw-600">Create one free</a>
        </div>



      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw() {
  const pw = document.getElementById('pw');
  const icon = document.getElementById('eyeIcon');
  if (pw.type === 'password') { pw.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
  else { pw.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>
</body>
</html>