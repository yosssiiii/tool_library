<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

$message = "";

if(isset($_POST['add_tool'])){

$tool_name     = $_POST['tool_name'];
$category      = $_POST['category'];
$description   = $_POST['description'];
$price_per_day = $_POST['price_per_day'];
$owner_id      = $_SESSION['user_id'];

/* Upload Image */
$photo = $_FILES['photo']['name'];
$tmp   = $_FILES['photo']['tmp_name'];

$folder = "uploads/" . time() . "_" . $photo;

move_uploaded_file($tmp,$folder);

/* Insert */
$status = "available";

$sql = "INSERT INTO tools
(owner_id,tool_name,category,description,price_per_day,photo,availability_status)
VALUES (?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
"isssdss",
$owner_id,
$tool_name,
$category,
$description,
$price_per_day,
$folder,
$availability_status
);

if($stmt->execute()){
$message = "Tool Added Successfully";
}else{
$message = "Error While Adding Tool";
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>List Tool</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
background:#f4f6f9;
}
.box{
max-width:700px;
margin:auto;
margin-top:50px;
background:white;
padding:35px;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,.08);
}
</style>

</head>
<body>

<div class="container">

<div class="box">

<h2 class="mb-4 text-center">
List New Tool
</h2>

<?php if($message!=""){ ?>

<div class="alert alert-info">
<?php echo $message; ?>
</div>

<?php } ?>

<form method="POST" enctype="multipart/form-data">

<div class="mb-3">
<label>Tool Name</label>
<input type="text"
name="tool_name"
class="form-control"
required>
</div>

<div class="mb-3">
<label>Category</label>
<select name="category" class="form-control" required>
<option value="">Choose</option>
<option>Power Tools</option>
<option>Gardening</option>
<option>Cleaning</option>
<option>Construction</option>
<option>Electronics</option>
<option>Other</option>
</select>
</div>

<div class="mb-3">
<label>Description</label>
<textarea
name="description"
class="form-control"
rows="4"
required></textarea>
</div>

<div class="mb-3">
<label>Price Per Day ($)</label>
<input type="number"
step="0.01"
name="price_per_day"
class="form-control"
required>
</div>

<div class="mb-3">
<label>Photo</label>
<input type="file"
name="photo"
class="form-control"
required>
</div>

<div class="d-grid">
<button
type="submit"
name="add_tool"
class="btn btn-primary">

Add Tool

</button>
</div>

</form>

<a href="dashboard.php"
class="btn btn-link mt-3">

Back To Dashboard

</a>

</div>

</div>

</body>
</html>
    </div>
</body>
</html>