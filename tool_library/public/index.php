<?php
session_start();
require_once("../config/Database.php"); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool Library | Community Tool Sharing</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    
    <style>
        :root {
            --primary-blue: #004a99; /* لون مشابه للموقع المطلوب */
            --accent-orange: #2530d1;
            --text-dark: #333;
            --light-gray: #f4f4f4;
        }

        body {
            margin: 0;
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
        }

        h1, h2, h3 { font-family: 'Montserrat', sans-serif; font-weight: 700; }

        /* Navbar - كما طلبت: Home, Login, Register, Contact */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 8%;
            background: white;
            border-bottom: 3px solid var(--primary-blue);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo { font-size: 24px; color: var(--primary-blue); text-decoration: none; font-weight: bold; }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            margin-left: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            transition: 0.3s;
        }

        .nav-links a:hover { color: var(--accent-orange); }

        /* Hero Section - مكان الصورة الرئيسية */
        .hero {
            position: relative;
            height: 550px;
            /* استبدل 'hero-bg.jpg' بصورتك الرئيسية */
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.squarespace-cdn.com/content/v1/5c362103f93fd4cf979dc893/1624557054906-E61712LO9F5FYAV012H2/IMG_1696.jpg?format=750w');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }

        .hero h1 { font-size: 3.5rem; margin-bottom: 10px; text-transform: uppercase; }
        .hero p { font-size: 1.5rem; margin-bottom: 25px; max-width: 700px; }
        
        .btn-main {
            background: var(--accent-orange);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-main:hover { background: #d4561b; transform: scale(1.05); }

        /* Mission Section - صور بجانب نص */
        .section-padding { padding: 80px 10%; }
        
        .flex-container {
            display: flex;
            align-items: center;
            gap: 50px;
            flex-wrap: wrap;
        }

        .flex-item { flex: 1; min-width: 300px; }

        .flex-item img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        /* Features Cards */
        .bg-gray { background: var(--light-gray); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        
        .image-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        /* مكان صور الخدمات */
        .image-card .img-placeholder {
            height: 200px;
            background: #ddd url('https://via.placeholder.com/400x250?text=Service+Image') center/cover;
        }
        .image-card .content { padding: 25px; }

        /* Footer */
        .footer { background: var(--primary-blue); color: white; padding: 50px 10% 20px; text-align: center; }
        
        @media (max-width: 768px) { .hero h1 { font-size: 2.5rem; } }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">TOOL LIBRARY</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<header class="hero">
    <h1>Empowering Communities</h1>
    <p>Sharing tools, skills, and resources to build a better neighborhood together.</p>
    <a href="register.php" class="btn-main">JOIN THE LIBRARY</a>
</header>

<section class="section-padding">
    <div class="flex-container">
        <div class="flex-item">
            <h2 style="color: var(--primary-blue); font-size: 2.5rem;">How It Works</h2>
            <p>Our tool library operates just like a regular book library. Members can browse our extensive collection of professional-grade tools, reserve them online, and pick them up for their home projects.</p>
            <p>By sharing resources, we reduce waste, save money, and foster a spirit of collaborative consumption in our community.</p>
        </div>
        <div class="flex-item">
            <img src="https://files.constantcontact.com/489ebb2e701/14588df7-7286-4c92-99a2-5b9a0e26580c.jpg?rdr=true" alt="Community Work">
        </div>
    </div>
</section>

<section class="section-padding bg-gray">
    <div style="text-align: center; margin-bottom: 50px;">
        <h2 style="font-size: 2.5rem;">Our Core Services</h2>
    </div>
    <div class="grid">
        <div class="image-card">
            <div class="img-placeholder" style="background-image: url('https://images.squarespace-cdn.com/content/v1/5c362103f93fd4cf979dc893/1624557060406-O8HAOLASZXS2O4WP28T6/DSCN0012.png?format=750w');"></div>
            <div class="content">
                <h3>Tool Borrowing</h3>
                <p>Access over 500+ tools ranging from power drills to landscaping equipment.</p>
            </div>
        </div>
        <div class="image-card">
            <div class="img-placeholder" style="background-image: url('https://images.squarespace-cdn.com/content/v1/5c362103f93fd4cf979dc893/1582563069366-CT3HMIG3PY17W6JAXK13/Modcon_Living-12.jpg?format=500w');"></div>
            <div class="content">
                <h3>Skill Sharing</h3>
                <p>Connect with expert "Pro" members to learn how to use specialized tools safely.</p>
            </div>
        </div>
        <div class="image-card">
            <div class="img-placeholder" style="background-image: url('https://myturn-prod-images-out.s3-us-west-2.amazonaws.com/3/3/item/42035/image/porter%20cable%20bn138%20nailer-DBE153A3-2A15-06C9-0256-77F89DD7F7A4-medium@2x.jpg');"></div>
            <div class="content">
                <h3>Expert Maintenance</h3>
                <p>Our technical team ensures every tool is safety-certified before it reaches your hands.</p>
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div style="margin-bottom: 30px;">
        <h3>TOOL LIBRARY SYSTEM</h3>
        <p>Building community, one tool at a time.</p>
    </div>
    <div class="nav-links" style="margin-bottom: 30px;">
        <a href="#" style="color: white;">Facebook</a>
        <a href="#" style="color: white;">Twitter</a>
        <a href="#" style="color: white;">Instagram</a>
    </div>
    <hr style="opacity: 0.3;">
    <p style="font-size: 13px; margin-top: 20px;">© 2026 Tool Library System | Faculty of Computers and Information - Helwan University</p>
</footer>

</body>
</html>