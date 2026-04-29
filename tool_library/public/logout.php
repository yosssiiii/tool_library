<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial;
        }

        .box {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.1);
            width: 350px;
        }

        h2 {
            color: #1d4ed8;
            font-weight: bold;
        }

        p {
            color: #475569;
        }

        .btn-home {
            margin-top: 15px;
            background: #60a5fa;
            color: white;
            font-weight: bold;
            border-radius: 10px;
            padding: 10px 20px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-home:hover {
            background: #3b82f6;
        }
    </style>
</head>

<body>

<div class="box">

    <h2>👋 Logged Out</h2>
    <p>You have been safely logged out</p>

    <a href="index.php" class="btn-home">Go to Home</a>

</div>

</body>
</html>

<?php
session_start();

session_unset();
session_destroy();

//header("Location: login.php");
exit;
?>