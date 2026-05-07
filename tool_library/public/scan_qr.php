<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scan Handover QR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- مكتبة السكانر -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body class="bg-dark text-white text-center">

<div class="container py-5">
    <h3>Scan Owner's QR Code</h3>
    <p class="text-muted">Point your camera at the QR code displayed on the owner's screen</p>
    
    <!-- المكان الذي ستظهر فيه الكاميرا -->
    <div id="reader" style="width: 100%; max-width: 500px; margin: auto; background: white; border-radius: 15px; overflow: hidden;"></div>
    
    <a href="dashboard.php" class="btn btn-outline-light mt-4">Cancel</a>
</div>

<script>
    function onScanSuccess(decodedText, decodedResult) {
        // بمجرد ما يقرأ الكود بنجاح، هيوديه للرابط اللي جوه الكود
        // الرابط هيكون هو confirm_handover.php?id=...
        window.location.href = decodedText; 
    }

    function onScanFailure(error) {
        // لا نفعل شيء في حالة الفشل المستمر (البحث عن كود)
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", { fps: 10, qrbox: 250 }
    );
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>

</body>
</html>