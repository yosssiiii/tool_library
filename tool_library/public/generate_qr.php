<?php
session_start();
if(!isset($_SESSION['user_id'])) die("Access Denied");

$res_id = $_GET['id'];
$confirmation_url = "http://localhost/tool_library/public/confirm_handover.php?id=" . $res_id;

// استخدام رابط QuickChart (بديل قوي لـ Google)
$qr_api = "https://quickchart.io/qr?text=" . urlencode($confirmation_url) . "&size=300";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Handover QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5 text-center">
        <div class="card shadow mx-auto" style="max-width: 400px; border-radius: 20px;">
            <div class="card-body">
                <h4 class="fw-bold mb-4">Handover Confirmation</h4>
                <p class="text-muted">Ask the renter to scan this code to confirm they received the tool.</p>
                
                <!-- عرض الكود -->
                <img src="<?php echo $qr_api; ?>" style="border: 10px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.1);" alt="QR Code">

                <div class="alert alert-info small">
                    Reservation ID: #<?php echo $res_id; ?>
                </div>

                <a href="dashboard.php" class="btn btn-secondary w-100">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>