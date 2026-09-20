<?php

session_start();
include "db.php";

if (!isset($_SESSION["customer_id"])) {
    echo "<script>
            alert('Please login to view your orders.');
            window.location.href='login.html';
          </script>";
    exit();
}

$customer_id = $_SESSION["customer_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        GROUP_CONCAT(
            CONCAT(m.item_name, ' x ', oi.quantity)
            SEPARATOR ', '
        ) AS items
     FROM orders o
     JOIN order_items oi ON o.order_id = oi.order_id
     JOIN menu m ON oi.item_id = m.item_id
     WHERE o.customer_id = ?
     GROUP BY o.order_id
     ORDER BY o.order_date DESC"
);

mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Brew&Desk | My Orders</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial,sans-serif;
}

body{
background:#f8f5f2;
color:#333;
}

header{
background:#2b1b12;
padding:15px 40px;
position:sticky;
top:0;
z-index:1000;
}

.navbar{
display:flex;
justify-content:space-between;
align-items:center;
}

.logo{
font-size:24px;
font-weight:bold;
color:white;
}

.navbar ul{
display:flex;
gap:20px;
list-style:none;
}

.navbar ul li a{
text-decoration:none;
color:white;
transition:.3s;
}

.navbar ul li a:hover{
color:#c89b6d;
}

.nav-buttons a{
text-decoration:none;
padding:8px 15px;
border-radius:5px;
margin-left:10px;
font-weight:bold;
}

.login-btn{
background:white;
color:#2b1b12;
}

.register-btn{
background:#c89b6d;
color:white;
}

.hero{
height:35vh;
background:
linear-gradient(rgba(0,0,0,.6),
rgba(0,0,0,.6)),
url("https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1600");

background-size:cover;
background-position:center;

display:flex;
justify-content:center;
align-items:center;
text-align:center;
color:white;
}

.hero h1{
font-size:48px;
margin-bottom:10px;
}

.hero p{
font-size:18px;
}

.container{
max-width:1000px;
margin:50px auto;
padding:30px;
padding-bottom:70px;
}

.order-card{
background:white;
padding:25px;
margin-bottom:25px;
border-radius:15px;
box-shadow:0 5px 15px rgba(0,0,0,.1);
}

.order-card h3{
color:#2b1b12;
margin-bottom:15px;
}

.order-details{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
}

.detail{
background:#f8f5f2;
padding:15px;
border-radius:10px;
}

.detail strong{
display:block;
margin-bottom:8px;
color:#2b1b12;
}

.status{
display:inline-block;
padding:8px 15px;
border-radius:20px;
font-size:14px;
font-weight:bold;
margin-top:10px;
}

.pending{
background:#fff3cd;
color:#856404;
}

.preparing{
background:#fff3cd;
color:#856404;
}

.completed{
background:#d4edda;
color:#155724;
}

.cancelled{
background:#f8d7da;
color:#721c24;
}

.buttons{
margin-top:25px;
display:flex;
gap:15px;
flex-wrap:wrap;
}

.buttons button{
padding:12px 20px;
border:none;
border-radius:8px;
cursor:pointer;
font-size:15px;
color:white;
transition:.3s;
}

.reservation-btn{
background:#3c7a4c;
}

.buttons button:hover{
opacity:.9;
}

footer{
background:#2b1b12;
color:white;
padding:20px 40px;
margin-top:50px;
}

.footer-content{
display:flex;
justify-content:space-between;
align-items:center;
flex-wrap:wrap;
}

.social-icons a{
color:white;
margin:0 10px;
font-size:20px;
transition:.3s;
}

.social-icons a:hover{
color:#c89b6d;
}

.admin-login a{
text-decoration:none;
background:#c89b6d;
padding:10px 18px;
color:white;
border-radius:6px;
font-weight:bold;
}

@media(max-width:768px){

.navbar{
flex-direction:column;
gap:15px;
}

.navbar ul{
flex-wrap:wrap;
justify-content:center;
}

.order-details{
grid-template-columns:1fr;
}

.footer-content{
flex-direction:column;
gap:20px;
text-align:center;
}

}

</style>

</head>

<body>

<header>

<div class="navbar">

<div class="logo">
<i class="fa-solid fa-mug-hot"></i>
 Brew&Desk
</div>

<ul>
<li><a href="index.html">Home</a></li>
<li><a href="menu.html">Menu</a></li>
<li><a href="about.html">About Us</a></li>
<li><a href="reservation.html">Reservation</a></li>
<li><a href="contact.html">Contact</a></li>
</ul>

<div class="nav-buttons">
<a href="login.html" class="login-btn">Login</a>
<a href="register.html" class="register-btn">Register</a>
</div>

</div>

</header>

<section class="hero">

<div>
<h1>My Orders</h1>

<p>
View and manage all your Brew&Desk pre-orders.
</p>

</div>

</section>

<div class="container">

<?php

if (mysqli_num_rows($result) > 0) {

    while ($order = mysqli_fetch_assoc($result)) {

        $status = strtolower($order["status"]);

        if ($status == "pending") {
            $status_class = "pending";
        } elseif ($status == "preparing") {
            $status_class = "preparing";
        } elseif ($status == "completed") {
            $status_class = "completed";
        } elseif ($status == "cancelled") {
            $status_class = "cancelled";
        } else {
            $status_class = "pending";
        }

?>

<div class="order-card">

<h3>
<i class="fa-solid fa-receipt"></i>
Order #<?php echo $order["order_id"]; ?>
</h3>

<div class="order-details">

<div class="detail">

<strong>Items</strong>

<?php echo htmlspecialchars($order["items"]); ?>

</div>

<div class="detail">

<strong>Order Date</strong>

<?php echo date("d M Y, h:i A", strtotime($order["order_date"])); ?>

</div>

<div class="detail">

<strong>Total Amount</strong>

₹<?php echo number_format($order["total_amount"], 2); ?>

</div>

<div class="detail">

<strong>Status</strong>

<span class="status <?php echo $status_class; ?>">
<?php echo htmlspecialchars($order["status"]); ?>
</span>

</div>

</div>

</div>

<?php

    }

} else {

?>

<div class="order-card" style="text-align:center; padding:50px 25px;">

<i class="fa-solid fa-cart-shopping"
style="font-size:40px; color:#c89b6d; margin-bottom:15px;">
</i>

<h3>No Orders Yet</h3>

<p style="color:#777; margin-top:10px;">
Your pre-orders will appear here after you place an order.
</p>

</div>

<?php

}

?>

</div>

<footer>

<div class="footer-content">

<p>
&copy; 2026 Brew&Desk.
All Rights Reserved.
</p>

<div class="social-icons">

<a href="#"><i class="fab fa-facebook-f"></i></a>
<a href="#"><i class="fab fa-instagram"></i></a>
<a href="#"><i class="fab fa-x-twitter"></i></a>
<a href="#"><i class="fab fa-linkedin-in"></i></a>

</div>

<div class="admin-login">

<a href="admin_login.html">
Admin Login
</a>

</div>

</div>

</footer>

</body>
</html>

<?php

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>