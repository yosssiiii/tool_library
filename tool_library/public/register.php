<?php
require_once("../config/Database.php");
require_once("../models/User.php");

$db = new Database();
$conn = $db->connect();

$user = new User($conn);

$message = "";

if(isset($_POST['register'])){

$name     = $_POST['name'];
$email    = $_POST['email'];
$password = $_POST['password'];
$phone    = $_POST['phone'];
$address  = $_POST['address'];

if($user->register($name,$email,$password,$phone,$address)){
    $message = "Account Created Successfully";
}else{
    $message = "Something went wrong";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Register</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
background: linear-gradient(135deg,#0d6efd,#6610f2);
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
}

.register-box{
width:100%;
max-width:500px;
background:white;
padding:35px;
border-radius:20px;
box-shadow:0 15px 35px rgba(0,0,0,0.15);
}

.logo{
font-size:28px;
font-weight:bold;
text-align:center;
margin-bottom:10px;
color:#0d6efd;
}

.sub{
text-align:center;
color:gray;
margin-bottom:25px;
}

.form-control{
padding:12px;
border-radius:10px;
}

.btn-custom{
padding:12px;
font-weight:bold;
border-radius:10px;
}

.link{
text-decoration:none;
font-weight:600;
}
</style>

</head>
<body>

<div class="register-box">

<div class="logo">Tool Library</div>
<div class="sub">Create Your Account</div>

<?php if($message!=""){ ?>
<div class="alert alert-info text-center">
<?php echo $message; ?>
</div>
<?php } ?>

<form method="POST">

<div class="mb-3">
<input type="text" name="name" class="form-control" placeholder="Full Name" required>
</div>

<div class="mb-3">
<input type="email" name="email" class="form-control" placeholder="Email Address" required>
</div>

<div class="mb-3">
<input type="password" name="password" class="form-control" placeholder="Password" required>
</div>

<div class="mb-3">
<input type="text" name="phone" class="form-control" placeholder="Phone Number" required>
</div>

<div class="mb-3">
<input type="text" name="address" class="form-control" placeholder="Address" required>
</div>

<div class="d-grid">
<button type="submit" name="register" class="btn btn-primary btn-custom">
Create Account
</button>
</div>

</form>

<div class="text-center mt-4">
Already have an account?
<a href="login.php" class="link">Login</a>
</div>

</div>

</body>
</html>