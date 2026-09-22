<?php

session_start();
include "db.php";

/* ================= ADMIN LOGIN CHECK ================= */

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.html");
    exit();
}


/* ================= DASHBOARD STATISTICS ================= */

/* Total Customers */
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$row = mysqli_fetch_assoc($result);
$total_customers = $row["total"];


/* Total Reservations */
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservations");
$row = mysqli_fetch_assoc($result);
$total_reservations = $row["total"];


/* Total Orders */
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders");
$row = mysqli_fetch_assoc($result);
$total_orders = $row["total"];


/* Total Revenue */
$result = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM orders"
);

$row = mysqli_fetch_assoc($result);
$total_revenue = $row["revenue"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Brew&Desk | Admin Dashboard</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,Helvetica,sans-serif;
}

body{
    background:#f8f5f2;
    color:#333;
}


/* ================= SIDEBAR ================= */

.sidebar{

    position:fixed;
    left:0;
    top:0;
    width:250px;
    height:100vh;
    background:#2b1b12;
    color:white;
    padding:20px;
    z-index:1000;

}

.logo{

    font-size:25px;
    font-weight:bold;
    text-align:center;
    padding:10px 0 25px;
    border-bottom:1px solid rgba(255,255,255,.2);

}

.logo i{
    margin-right:5px;
}

.admin-title{

    text-align:center;
    margin:20px 0;
    color:#c89b6d;
    font-size:14px;
    font-weight:bold;
    text-transform:uppercase;
    letter-spacing:1px;

}


/* ================= NAVIGATION ================= */

.sidebar ul{
    list-style:none;
}

.sidebar ul li{
    margin-bottom:8px;
}

.sidebar ul li a{

    display:flex;
    align-items:center;
    gap:14px;
    padding:13px 15px;
    color:white;
    text-decoration:none;
    border-radius:7px;
    transition:.3s;

}

.sidebar ul li a:hover,
.sidebar ul li a.active{
    background:#c89b6d;
}

.sidebar ul li a i{
    width:20px;
    text-align:center;
}


/* ================= LOGOUT ================= */

.sidebar .logout{

    position:absolute;
    bottom:25px;
    left:20px;
    right:20px;

}

.logout a{

    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:12px;
    background:#b22222;
    color:white;
    text-decoration:none;
    border-radius:7px;
    font-weight:bold;

}

.logout a:hover{
    background:#8f1b1b;
}


/* ================= MAIN ================= */

.main{

    margin-left:250px;
    min-height:100vh;

}


/* ================= TOPBAR ================= */

.topbar{

    background:white;
    padding:18px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 10px rgba(0,0,0,.08);

}

.topbar h1{

    color:#2b1b12;
    font-size:26px;

}

.admin-profile{

    display:flex;
    align-items:center;
    gap:12px;

}

.admin-avatar{

    width:42px;
    height:42px;
    border-radius:50%;
    background:#c89b6d;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;

}

.admin-profile span{

    font-weight:bold;
    color:#2b1b12;

}


/* ================= CONTENT ================= */

.content{
    padding:30px;
}

.welcome{
    margin-bottom:25px;
}

.welcome h2{

    color:#2b1b12;
    margin-bottom:7px;

}

.welcome p{
    color:#666;
}


/* ================= STAT CARDS ================= */

.stats{

    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:30px;

}

.stat-card{

    background:white;
    padding:22px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    display:flex;
    align-items:center;
    gap:18px;
    transition:.3s;

}

.stat-card:hover{
    transform:translateY(-4px);
}

.stat-icon{

    width:55px;
    height:55px;
    border-radius:10px;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:22px;
    background:#f0e3d5;
    color:#2b1b12;

}

.stat-info h3{

    font-size:25px;
    color:#2b1b12;

}

.stat-info p{

    color:#777;
    font-size:14px;
    margin-top:3px;

}


/* ================= QUICK ACTIONS ================= */

.quick-actions{

    margin-top:25px;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);

}

.quick-actions h3{

    color:#2b1b12;
    margin-bottom:18px;

}

.action-buttons{

    display:flex;
    flex-wrap:wrap;
    gap:12px;

}

.action-buttons a{

    text-decoration:none;
    padding:11px 18px;
    background:#2b1b12;
    color:white;
    border-radius:7px;
    font-size:14px;
    transition:.3s;

}

.action-buttons a:hover{
    background:#c89b6d;
}


/* ================= RESPONSIVE ================= */

@media(max-width:1100px){

    .stats{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:800px){

    .sidebar{

        width:70px;
        padding:15px 10px;

    }

    .logo{

        font-size:0;
        border:none;

    }

    .logo i{
        font-size:25px;
    }

    .admin-title,
    .sidebar ul li a span,
    .logout a span{

        display:none;

    }

    .sidebar ul li a{

        justify-content:center;
        padding:13px;

    }

    .sidebar .logout{

        left:10px;
        right:10px;

    }

    .logout a{
        padding:13px;
    }

    .main{
        margin-left:70px;
    }

}

@media(max-width:600px){

    .topbar{
        padding:15px;
    }

    .topbar h1{
        font-size:21px;
    }

    .admin-profile span{
        display:none;
    }

    .content{
        padding:20px 15px;
    }

    .stats{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>


<!-- ================= SIDEBAR ================= -->

<aside class="sidebar">

<div class="logo">

<i class="fa-solid fa-mug-hot"></i>

Brew&Desk

</div>

<div class="admin-title">

Admin Panel

</div>

<ul>

<li>

<a href="admin_dashboard.php" class="active">

<i class="fa-solid fa-gauge"></i>

<span>Dashboard</span>

</a>

</li>

<li>

<a href="admin_reservation.html">

<i class="fa-solid fa-calendar-check"></i>

<span>Reservations</span>

</a>

</li>

<li>

<a href="admin_orders.html">

<i class="fa-solid fa-cart-shopping"></i>

<span>Orders</span>

</a>

</li>

<li>

<a href="admin_menu.html">

<i class="fa-solid fa-utensils"></i>

<span>Menu</span>

</a>

</li>

<li>

<a href="admin_customers.html">

<i class="fa-solid fa-users"></i>

<span>Customers</span>

</a>

</li>

<li>

<a href="admin_report.php" class="active">

<i class="fa-solid fa-chart-column"></i>

<span>Reports</span>

</a>

</li>

<li>

<a href="admin_profile.html">

<i class="fa-solid fa-user"></i>

<span>My Profile</span>

</a>

</li>

</ul>


<div class="logout">

<a href="admin_login.html" onclick="return logoutAdmin()">

<i class="fa-solid fa-right-from-bracket"></i>

<span>Logout</span>

</a>

</div>

</aside>


<!-- ================= MAIN ================= -->

<main class="main">


<!-- TOPBAR -->

<div class="topbar">

<h1>Dashboard</h1>

<div class="admin-profile">

<div class="admin-avatar">

<i class="fa-solid fa-user-shield"></i>

</div>

<span>Administrator</span>

</div>

</div>


<!-- CONTENT -->

<div class="content">


<!-- WELCOME -->

<div class="welcome">

<h2>Welcome, Admin! ☕</h2>

<p>

Manage your Brew&Desk reservations, orders, customers and menu from here.

</p>

</div>


<!-- ================= STATISTICS ================= -->

<div class="stats">


<!-- CUSTOMERS -->

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-users"></i>

</div>

<div class="stat-info">

<h3><?php echo $total_customers; ?></h3>

<p>Total Customers</p>

</div>

</div>


<!-- RESERVATIONS -->

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-calendar-check"></i>

</div>

<div class="stat-info">

<h3><?php echo $total_reservations; ?></h3>

<p>Total Reservations</p>

</div>

</div>


<!-- ORDERS -->

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-cart-shopping"></i>

</div>

<div class="stat-info">

<h3><?php echo $total_orders; ?></h3>

<p>Total Orders</p>

</div>

</div>


<!-- REVENUE -->

<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-indian-rupee-sign"></i>

</div>

<div class="stat-info">

<h3>₹<?php echo number_format($total_revenue, 2); ?></h3>

<p>Total Revenue</p>

</div>

</div>


</div>


<!-- ================= QUICK ACTIONS ================= -->

<div class="quick-actions">

<h3>

<i class="fa-solid fa-bolt"></i>

Quick Actions

</h3>

<div class="action-buttons">

<a href="admin_reservation.html">

<i class="fa-solid fa-calendar-check"></i>

&nbsp; Manage Reservations

</a>

<a href="admin_orders.html">

<i class="fa-solid fa-cart-shopping"></i>

&nbsp; Manage Orders

</a>

<a href="admin_menu.html">

<i class="fa-solid fa-utensils"></i>

&nbsp; Manage Menu

</a>

<a href="admin_customers.html">

<i class="fa-solid fa-users"></i>

&nbsp; View Customers

</a>

<a href="admin_report.php">

<i class="fa-solid fa-chart-column"></i>

&nbsp; View Reports

</a>

</div>

</div>


</div>

</main>


<script>

/* ================= LOGOUT CONFIRMATION ================= */

function logoutAdmin(){

    let confirmLogout =
    confirm("Are you sure you want to logout?");

    if(confirmLogout){

        alert("You have been logged out successfully.");

        return true;

    }

    return false;

}


/* ================= CONSOLE ================= */

window.onload=function(){

    console.log("Welcome to Brew&Desk Admin Dashboard");

};

</script>

</body>

</html>


<?php
mysqli_close($conn);
?>