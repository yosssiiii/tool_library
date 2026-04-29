<?php
session_start();
require_once("../config/Database.php");
require_once("../models/tool.php");

$db = new Database();
$conn = $db->connect();

// استعلام لجلب الأدوات المتاحة مع اسم المؤجر (Lender) من الداتابيز
$sql = "SELECT tools.*, users.full_name as lender_name 
        FROM tools 
        JOIN users ON tools.owner_id = users.user_id 
        WHERE tools.availability_status = 'available'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Tool Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .tool-card { 
            border: none; 
            border-radius: 12px; 
            transition: 0.3s ease; 
            background: white; 
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .tool-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .tool-img-container { height: 200px; display: flex; align-items: center; justify-content: center; background: #fff; padding: 10px; }
        .tool-img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .status-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; }
        .price-text { color: #2c3e50; font-weight: bold; font-size: 1.1rem; }
        .lender-name { font-size: 0.85rem; color: #6c757d; }
        .btn-see-more { border-radius: 8px; font-weight: 600; }
        .search-header { background: white; padding: 15px 0; border-bottom: 1px solid #eee; margin-bottom: 30px; }
    </style>
</head>
<body>

<header class="search-header">
    <div class="container d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-primary">🔧 Tool Library Inventory</h4>
        <div class="d-flex gap-3">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Home</a>
            <a href="dashboard.php" class="btn btn-sm btn-primary">My Account</a>
        </div>
    </div>
</header>

<div class="container">
    <div class="row">
        <aside class="col-md-3">
            <div class="sidebar mb-4">
                <h5>Search & Filter</h5>
                <input type="text" class="form-control mb-3" placeholder="Search within...">
                <hr>
                <h6>Availability</h6>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="stock">
                    <label class="form-check-label" for="stock">In stock now</label>
                </div>
                <hr>
                <h6>Category</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-decoration-none text-muted">Automotive</a></li>
                    <li><a href="#" class="text-decoration-none text-muted">Gardening</a></li>
                    <li><a href="#" class="text-decoration-none text-muted">Power Tools</a></li>
                </ul>
            </div>
        </aside>

        <main class="col-md-9">
            <div class="row g-4">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card tool-card h-100">
                                <div class="tool-img-container">
                                    <?php 
                                        $image = !empty($row['photo']) ? "uploads/".$row['photo'] : "https://via.placeholder.com/200?text=No+Image";
                                        ?>
                                    <img src="<?php echo $image; ?>" class="tool-img" alt="Tool Image">
                                </div>
                                
                                <div class="card-body">
                                    <h6 class="card-title text-truncate mb-1"><?php echo htmlspecialchars($row['tool_name']); ?></h6>
                                    
                                    <span class="badge bg-success-subtle text-success status-badge mb-2">In Stock Now</span>
                                    
                                    <div class="price-text mb-1">$<?php echo number_format($row['price_per_hour'], 2); ?> <span class="small text-muted" style="font-size: 0.7rem;">per loan</span></div>
                                    
                                    <div class="lender-name mb-3">
                                        <i class="fa fa-user-circle me-1"></i> Lender: <strong><?php echo htmlspecialchars($row['lender_name']); ?></strong>
                                    </div>

                                    <a href="tool_details.php?id=<?php echo $row['tool_id']; ?>" class="btn btn-outline-primary btn-sm w-100 btn-see-more">
                                        See More & Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No tools found in the database. Start by adding one!</p>
                        <a href="add_tool.php" class="btn btn-primary">Add New Tool</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>