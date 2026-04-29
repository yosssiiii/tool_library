<?php
session_start();

require_once("../config/Database.php");
require_once("../models/User.php");

$db = new Database();
$conn = $db->connect();

$user = new User($conn);

$message = "";

if(isset($_POST['login'])){

$data = $user->login(
$_POST['email'],
$_POST['password']
);

if($data){
$_SESSION['user_id'] = $data['user_id'];
header("Location: dashboard.php");
}else{
$message = "Wrong Email or Password";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login</title>

<style>

body{
margin:0;
padding:0;
font-family:Arial;
background:#f5f6fa;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.box{
width:400px;
background:white;
padding:30px;
border-radius:15px;
box-shadow:0 0 20px rgba(0,0,0,0.1);
}

h2{
text-align:center;
margin-bottom:25px;
}

input{
width:100%;
padding:12px;
margin-bottom:15px;
border:1px solid #ccc;
border-radius:8px;
}

button{
width:100%;
padding:12px;
background:#3498db;
color:white;
border:none;
border-radius:8px;
font-size:16px;
cursor:pointer;
}

button:hover{
background:#2980b9;
}

.error{
color:red;
text-align:center;
margin-bottom:10px;
}

a{
text-decoration:none;
display:block;
text-align:center;
margin-top:15px;
}

</style>

</head>
<body>

<div class="box">

<h2>Login</h2>

<?php if($message != ""){ ?>
<div class="error"><?php echo $message; ?></div>
<?php } ?>

<form method="POST">

<input type="email" name="email" placeholder="Enter Email">

<input type="password" name="password" placeholder="Enter Password">

<button name="login">Login</button>

</form>

<a href="register.php">Create Account</a>

</div>

</body>
</html>