<?php

session_start();
include "db.php";

if (!isset($_SESSION["customer_id"])) {
    echo "<script>
            alert('Please login to view your reservations.');
            window.location.href='login.html';
          </script>";
    exit();
}

$customer_id = $_SESSION["customer_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        r.reservation_id,
        r.reservation_date,
        r.reservation_time,
        r.guests,
        r.special_request,
        r.status,
        ct.table_number
     FROM reservations r
     LEFT JOIN cafe_tables ct
        ON r.table_id = ct.table_id
     WHERE r.customer_id = ?
     ORDER BY r.reservation_date DESC, r.reservation_time DESC"
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

<title>Brew&Desk | My Reservations</title>

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
color:white;
font-size:24px;
font-weight:bold;
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

.hero{
height:35vh;

background:
linear-gradient(rgba(0,0,0,.6),
rgba(0,0,0,.6)),
url("https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1600");

background-size:cover;
background-position:center;

display:flex;
justify-content:center;
align-items:center;

text-align:center;
color:white;
}

.hero h1{
font-size:50px;
margin-bottom:10px;
}

.hero p{
font-size:18px;
}

.container{
max-width:1100px;
margin:50px auto;
padding:20px;
}

.container h2{
text-align:center;
color:#2b1b12;
margin-bottom:30px;
}

.card{
background:white;
padding:30px;
margin-bottom:30px;
border-radius:15px;

box-shadow:
0 10px 25px rgba(0,0,0,.1);
}

.details{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
}

.detail-box{
background:#f8f5f2;
padding:18px;
border-radius:10px;
}

.detail-box h4{
margin-bottom:8px;
color:#2b1b12;
}

.detail-box p{
color:#666;
}

.status{
padding:8px 15px;
border-radius:20px;
display:inline-block;
font-weight:bold;
}

.confirmed{
background:#d4edda;
color:#155724;
}

.pending{
background:#fff3cd;
color:#856404;
}

.cancelled{
background:#f8d7da;
color:#721c24;
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
}

.social-icons a:hover{
color:#c89b6d;
}

.admin-login a{
text-decoration:none;
background:#c89b6d;
color:white;
padding:10px 18px;
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

.details{
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

<li>
<a href="index.html">Home</a>
</li>

<li>
<a href="menu.html">Menu</a>
</li>

<li>
<a href="reservation.html">Reservation</a>
</li>

<li>
<a href="contact.html">Contact</a>
</li>

</ul>

<div class="nav-buttons">

<a href="profile.html" class="login-btn">
Profile
</a>

</div>

</div>

</header>


<section class="hero">

<div>

<h1>My Reservations</h1>

<p>
View and manage all your table reservations.
</p>

</div>

</section>


<div class="container">

<h2>My Reservations</h2>


<?php

if (mysqli_num_rows($result) > 0) {

    while ($reservation = mysqli_fetch_assoc($result)) {

        $status = strtolower($reservation["status"]);

        if ($status == "confirmed") {
            $status_class = "confirmed";
        } elseif ($status == "cancelled") {
            $status_class = "cancelled";
        } else {
            $status_class = "pending";
        }

?>

<div class="card">

<h3 style="color:#2b1b12; margin-bottom:20px;">

<i class="fa-solid fa-calendar-check"></i>

Reservation #<?php echo $reservation["reservation_id"]; ?>

</h3>


<div class="details">


<div class="detail-box">

<h4>Table Number</h4>

<p>
<?php

if ($reservation["table_number"] !== null) {
    echo "Table " . htmlspecialchars($reservation["table_number"]);
} else {
    echo "Not Assigned";
}

?>
</p>

</div>


<div class="detail-box">

<h4>Date</h4>

<p>
<?php
echo date(
    "d M Y",
    strtotime($reservation["reservation_date"])
);
?>
</p>

</div>


<div class="detail-box">

<h4>Time</h4>

<p>
<?php
echo date(
    "h:i A",
    strtotime($reservation["reservation_time"])
);
?>
</p>

</div>


<div class="detail-box">

<h4>Guests</h4>

<p>
<?php echo htmlspecialchars($reservation["guests"]); ?>
</p>

</div>


<div class="detail-box">

<h4>Special Request</h4>

<p>

<?php

if (!empty($reservation["special_request"])) {
    echo htmlspecialchars($reservation["special_request"]);
} else {
    echo "None";
}

?>

</p>

</div>


<div class="detail-box">

<h4>Status</h4>

<span class="status <?php echo $status_class; ?>">

<?php
echo htmlspecialchars($reservation["status"]);
?>

</span>

</div>


</div>

</div>

<?php

    }

} else {

?>

<div class="card"
style="text-align:center; padding:50px 25px;">

<i class="fa-solid fa-calendar-xmark"
style="font-size:40px; color:#c89b6d; margin-bottom:15px;">
</i>

<h3 style="color:#2b1b12; margin-bottom:10px;">
No Reservations Yet
</h3>

<p style="color:#777;">
Your table reservations will appear here after you make a reservation.
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

<a href="#">
<i class="fab fa-facebook-f"></i>
</a>

<a href="#">
<i class="fab fa-instagram"></i>
</a>

<a href="#">
<i class="fab fa-x-twitter"></i>
</a>

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