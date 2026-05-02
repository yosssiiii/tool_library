<?php

session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

// التأكد من وجود ID للأداة في الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: search_tool.php");
    exit();
}

$tool_id = $_GET['id'];

// استعلام لجلب تفاصيل الأداة مع بيانات المالك (Lender)
$sql = "SELECT tools.*, users.full_name as lender_name, users.email as lender_email, users.phone as lender_phone 
        FROM tools 
        JOIN users ON tools.owner_id = users.user_id 
        WHERE tools.tool_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();
$tool = $result->fetch_assoc();

if (!$tool) {
    die("Tool not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tool['tool_name']; ?> - Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .details-card { border: none; border-radius: 15px; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
        .tool-main-img { width: 100%; max-height: 450px; object-fit: contain; background: #fff; padding: 20px; }
        .price-badge { font-size: 1.5rem; color: #f26522; font-weight: bold; }
        .lender-box { background: #e9ecef; border-radius: 10px; padding: 15px; }
    </style>
</head>
<body>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="search_tool.php">Inventory</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($tool['tool_name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="details-card text-center">
                <?php 
                    $image = !empty($tool['photo']) ? "".$tool['photo'] : "https://via.placeholder.com/500?text=No+Image";
                ?>
                <img src="<?php echo $image; ?>" class="tool-main-img" alt="Tool Image">
            </div>
        </div>

        <div class="col-md-6">
            <div class="details-card p-4">
                <h1 class="mb-2"><?php echo htmlspecialchars($tool['tool_name']); ?></h1>
                <p class="text-muted"><i class="fa fa-tag"></i> <?php echo htmlspecialchars($tool['category'] ?? 'General Tool'); ?></p>
                
                <hr>

                <div class="mb-4">
                    <h5 class="text-primary">Description:</h5>
                    <p class="text-dark" style="line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($tool['description'] ?? 'No description provided for this tool.')); ?>
                    </p>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="price-badge">
                        $<?php echo number_format($tool['price_per_day'] ?? 0, 2); ?> <span class="small text-muted" style="font-size: 1rem;">/ day</span>
                    </div>
                    <span class="badge bg-success px-3 py-2">Available</span>
                </div>

                <div class="lender-box mb-4">
                    <h6 class="mb-2">Lender Information:</h6>
                    <p class="mb-1"><strong><i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($tool['lender_name']); ?></strong></p>
                    <p class="mb-0 text-muted small"><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($tool['lender_email']); ?></p>
                </div>

                <form action="reserve.php" method="GET">
                    <input type="hidden" name="tool_id" value="<?php echo $tool['tool_id']; ?>">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                        <i class="fa fa-calendar-check"></i> Request Reservation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>