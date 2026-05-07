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
       body{
            background:#f3f4f6;
            font-family:Arial,sans-serif;
        }

        .details-card{
            border:none;
            border-radius:22px;
            background:white;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
            overflow:hidden;
        }

        .tool-main-img{
            width:100%;
            height:480px;
            object-fit:contain;
            background:#fff;
            padding:25px;
        }

        .tool-title{
            font-size:2rem;
            font-weight:700;
            color:#111827;
        }

        .tool-category{
            color:#6b7280;
            font-size:.95rem;
        }

        .section-title{
            font-size:1rem;
            font-weight:700;
            margin-bottom:14px;
            color:#111827;
        }

        .price-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:14px;
        }

        .price-card{
            border:1px solid #e5e7eb;
            border-radius:16px;
            padding:18px;
            text-align:center;
            transition:.2s;
            background:#fafafa;
        }

        .price-card:hover{
            transform:translateY(-3px);
            box-shadow:0 6px 16px rgba(0,0,0,.08);
        }

        .price-icon{
            width:52px;
            height:52px;
            margin:auto;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.2rem;
            margin-bottom:12px;
        }

        .hourly-icon{
            background:#dbeafe;
            color:#2563eb;
        }

        .daily-icon{
            background:#dcfce7;
            color:#16a34a;
        }

        .weekly-icon{
            background:#fef3c7;
            color:#d97706;
        }

        .price-value{
            font-size:1.3rem;
            font-weight:700;
        }

        .lender-box{
            background:#f9fafb;
            border:1px solid #e5e7eb;
            border-radius:16px;
            padding:18px;
        }

        .lender-item{
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:12px;
        }

        .lender-item:last-child{
            margin-bottom:0;
        }

        .lender-icon{
            width:42px;
            height:42px;
            border-radius:12px;
            background:#eef2ff;
            color:#4f46e5;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .reserve-btn{
            border-radius:14px;
            padding:14px;
            font-weight:700;
            font-size:1rem;
        }

        .status-badge{
            font-size:.9rem;
            padding:10px 16px;
            border-radius:12px;
        }
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
                    <div class="mb-4">

                        <h5 class="section-title">
                            <i class="fa fa-tags me-2 text-primary"></i>
                            Pricing Options
                        </h5>

                        <div class="price-grid">

                            <div class="price-card">

                                <div class="price-icon hourly-icon">
                                    <i class="fa fa-clock"></i>
                                </div>

                                <div class="text-muted small mb-1">
                                    Per Hour
                                </div>

                                <div class="price-value text-primary">
                                    $<?= number_format($tool['price_per_hour'],2) ?>
                                </div>

                            </div>

                            <div class="price-card">

                                <div class="price-icon daily-icon">
                                    <i class="fa fa-calendar-day"></i>
                                </div>

                                <div class="text-muted small mb-1">
                                    Per Day
                                </div>

                                <div class="price-value text-success">
                                    $<?= number_format($tool['price_per_day'],2) ?>
                                </div>

                            </div>

                            <div class="price-card">

                                <div class="price-icon weekly-icon">
                                    <i class="fa fa-calendar-week"></i>
                                </div>

                                <div class="text-muted small mb-1">
                                    Per Week
                                </div>

                                <div class="price-value text-warning">
                                    $<?= number_format($tool['price_per_week'],2) ?>
                                </div>

                            </div>

                        </div>

                    </div>
                    <span class="badge bg-success px-3 py-2">Available</span>
                </div>

                    <div class="lender-box mb-4">

                        <h5 class="section-title">
                            <i class="fa fa-user-shield me-2 text-primary"></i>
                            Lender Information
                        </h5>

                        <div class="lender-item">

                            <div class="lender-icon">
                                <i class="fa fa-user"></i>
                            </div>
                            <div>
                                <div class="fw-bold">
                                    <?= htmlspecialchars($tool['lender_name']); ?>
                                </div>
                                <small class="text-muted">
                                    Verified Community Lender
                                </small>
                            </div>
                        </div>
                        <div class="lender-item">
                            <div class="lender-icon">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <div>
                                <div class="fw-bold">
                                    <?= htmlspecialchars($tool['lender_email']); ?>
                                </div>
                                <small class="text-muted">
                                    Contact Email
                                </small>
                            </div>
                        </div>
                    </div>
                <form action="reserve.php" method="GET">
                    <input type="hidden"
                    name="tool_id"
                    value="<?= $tool['tool_id']; ?>">
                    <button type="submit"
                    class="btn btn-primary reserve-btn w-100">
                        <i class="fa fa-calendar-check me-2"></i>
                        Request Reservation
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>