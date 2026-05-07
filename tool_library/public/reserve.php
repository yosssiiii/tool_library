<?php
session_start();
require_once("../config/Database.php");
require_once("../models/tool.php"); // استدعاء موديل الأدوات

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$toolModel = new Tool($conn);

// الحل: نتأكد من الـ key المرسل في الرابط (سواء اسمه id أو tool_id)
$tool_id = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['tool_id']) ? $_GET['tool_id'] : null);

if (!$tool_id) {
    die("Error: No Tool ID provided.");
}

$tool = $toolModel->getToolById($tool_id);

if (!$tool) {
    die("Error: Tool not found in database.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reserve Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4 border-0">
                <h3 class="text-primary">Reserve: <?php echo htmlspecialchars($tool['tool_name']); ?></h3>
                <p class="text-muted">Rate: $<?php echo htmlspecialchars($tool['price_per_day'] ?? '0.00'); ?> / day</p>
                <hr>
                
                <form action="process_reservation.php" method="POST">

                <input type="hidden" name="tool_id" value="<?= $tool_id; ?>">

                <div class="mb-3">

                <label class="form-label">Rental Type</label>

                <select name="rental_type" class="form-select" required id="rentalType">

                    <option value="">Select Type</option>

                    <option value="hour">
                        Hourly ($<?= $tool['price_per_hour']; ?>)
                    </option>

                    <option value="day">
                        Daily ($<?= $tool['price_per_day']; ?>)
                    </option>

                    <option value="week">
                        Weekly ($<?= $tool['price_per_week']; ?>)
                    </option>
                </select>
                </div>
                <div class="mb-3">
                <label>Start Date</label>
                <input type="datetime-local"
                name="start_date"
                class="form-control"
                required>
                </div>
                <div class="mb-3">
                <label>End Date</label>
                <input type="datetime-local"
                name="end_date"
                class="form-control"
                required>
                </div>
                <button class="btn btn-success w-100 py-2 fw-bold">
                Confirm Reservation
                </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>