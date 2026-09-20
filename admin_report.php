<?php

session_start();
include "db.php";

/* ================= ADMIN LOGIN CHECK ================= */

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.html");
    exit();
}


/* ================= REPORT PERIOD ================= */

$period = $_GET["period"] ?? "month";

if ($period == "week") {

    $start_date = date("Y-m-d", strtotime("monday this week"));
    $end_date = date("Y-m-d", strtotime("sunday this week"));
    $period_title = "This Week";

} elseif ($period == "year") {

    $start_date = date("Y-01-01");
    $end_date = date("Y-12-31");
    $period_title = "This Year";

} else {

    $start_date = date("Y-m-01");
    $end_date = date("Y-m-t");
    $period_title = "This Month";

}


/* ================= SUMMARY ================= */

/* Orders */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(total_amount),0) AS revenue
     FROM orders
     WHERE DATE(order_date)
     BETWEEN '$start_date' AND '$end_date'"
);

$row = mysqli_fetch_assoc($result);

$total_orders = $row["total"];
$total_revenue = $row["revenue"];


/* Reservations */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM reservations
     WHERE DATE(reservation_date)
     BETWEEN '$start_date' AND '$end_date'"
);

$row = mysqli_fetch_assoc($result);

$total_reservations = $row["total"];


/* Customers */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$row = mysqli_fetch_assoc($result);

$total_customers = $row["total"];


/* ================= CHART DATA ================= */

$chart_labels = [];
$chart_values = [];


if ($period == "week") {

    for ($i = 0; $i < 7; $i++) {

        $date = date(
            "Y-m-d",
            strtotime($start_date . " +" . $i . " days")
        );

        $chart_labels[] = date("D", strtotime($date));

        $result = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM orders
             WHERE DATE(order_date) = '$date'"
        );

        $row = mysqli_fetch_assoc($result);

        $chart_values[] = (int)$row["total"];
    }

} elseif ($period == "year") {

    for ($i = 1; $i <= 12; $i++) {

        $chart_labels[] = date(
            "M",
            mktime(0, 0, 0, $i, 1)
        );

        $year = date("Y");

        $result = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM orders
             WHERE YEAR(order_date) = '$year'
             AND MONTH(order_date) = '$i'"
        );

        $row = mysqli_fetch_assoc($result);

        $chart_values[] = (int)$row["total"];
    }

} else {

    $days = date("t", strtotime($start_date));

    for ($i = 1; $i <= $days; $i += 7) {

        $from_day = $i;
        $to_day = min($i + 6, $days);

        $from_date = date(
            "Y-m-d",
            strtotime(date("Y-m-", strtotime($start_date)) . $from_day)
        );

        $to_date = date(
            "Y-m-d",
            strtotime(date("Y-m-", strtotime($start_date)) . $to_day)
        );

        $chart_labels[] = "Week " . ceil($i / 7);

        $result = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM orders
             WHERE DATE(order_date)
             BETWEEN '$from_date' AND '$to_date'"
        );

        $row = mysqli_fetch_assoc($result);

        $chart_values[] = (int)$row["total"];
    }
}


$max_chart = max($chart_values);

if ($max_chart == 0) {
    $max_chart = 1;
}


/* ================= POPULAR ITEMS ================= */

$popular_items = [];

$result = mysqli_query(
    $conn,
    "SELECT m.item_name,
            SUM(oi.quantity) AS total_quantity
     FROM order_items oi
     INNER JOIN menu m
     ON oi.item_id = m.item_id
     INNER JOIN orders o
     ON oi.order_id = o.order_id
     WHERE DATE(o.order_date)
     BETWEEN '$start_date' AND '$end_date'
     GROUP BY m.item_id, m.item_name
     ORDER BY total_quantity DESC
     LIMIT 5"
);

while ($row = mysqli_fetch_assoc($result)) {
    $popular_items[] = $row;
}


/* ================= RECENT REVENUE ================= */

$recent_revenue = [];

$result = mysqli_query(
    $conn,
    "SELECT DATE(o.order_date) AS report_date,
            COUNT(o.order_id) AS orders_count,
            SUM(o.total_amount) AS revenue,
            CASE
                WHEN SUM(o.status <> 'Completed') = 0
                THEN 'Completed'
                ELSE 'Pending'
            END AS report_status
     FROM orders o
     WHERE DATE(o.order_date)
     BETWEEN '$start_date' AND '$end_date'
     GROUP BY DATE(o.order_date)
     ORDER BY report_date DESC
     LIMIT 10"
);

while ($row = mysqli_fetch_assoc($result)) {

    $date = $row["report_date"];

    $reservation_result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM reservations
         WHERE DATE(reservation_date) = '$date'"
    );

    $reservation_row = mysqli_fetch_assoc($reservation_result);

    $row["reservations_count"] = $reservation_row["total"];

    $recent_revenue[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Brew&Desk | Reports</title>

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
}


/* ================= MAIN ================= */

.main{

    margin-left:250px;

    min-height:100vh;
}


/* ================= TOPBAR ================= */

.topbar{

    height:75px;

    background:white;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;

    box-shadow:0 2px 8px rgba(0,0,0,.08);
}

.topbar h1{
    font-size:25px;
}

.admin-profile{

    display:flex;

    align-items:center;

    gap:10px;

    font-weight:bold;
}

.admin-avatar{

    width:40px;
    height:40px;

    border-radius:50%;

    background:#c89b6d;

    color:white;

    display:flex;

    align-items:center;

    justify-content:center;
}


/* ================= CONTENT ================= */

.content{
    padding:30px;
}


/* ================= WELCOME ================= */

.welcome{

    background:white;

    padding:25px;

    border-radius:12px;

    margin-bottom:25px;

    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

.welcome h2{
    margin-bottom:8px;
}

.welcome p{
    color:#777;
}


/* ================= FILTER ================= */

.filter-box{

    background:white;

    padding:20px;

    border-radius:12px;

    margin-bottom:25px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

.filter-box select{

    padding:10px 15px;

    border:1px solid #ddd;

    border-radius:6px;

    font-size:15px;
}

.filter-box button{

    padding:10px 18px;

    border:none;

    border-radius:6px;

    background:#2b1b12;

    color:white;

    cursor:pointer;

    margin-left:8px;
}

.filter-box button:hover{
    background:#c89b6d;
}


/* ================= STATISTICS ================= */

.stats{

    display:grid;

    grid-template-columns:
    repeat(4,1fr);

    gap:20px;

    margin-bottom:25px;
}

.stat-card{

    background:white;

    padding:22px;

    border-radius:12px;

    display:flex;

    align-items:center;

    gap:15px;

    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

.stat-icon{

    width:50px;
    height:50px;

    border-radius:10px;

    background:#f0e3d5;

    color:#2b1b12;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:20px;
}

.stat-info h3{
    font-size:24px;
    margin-bottom:4px;
}

.stat-info p{
    color:#777;
    font-size:14px;
}


/* ================= SECTIONS ================= */

.section{

    background:white;

    padding:25px;

    border-radius:12px;

    margin-bottom:25px;

    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

.section-title{

    display:flex;

    align-items:center;

    gap:10px;

    margin-bottom:25px;
}

.section-title i{
    color:#c89b6d;
}


/* ================= CHART ================= */

.chart{

    height:270px;

    display:flex;

    align-items:flex-end;

    gap:15px;

    padding:20px 10px;

    border-bottom:1px solid #ddd;
}

.bar-box{

    flex:1;

    height:100%;

    display:flex;

    flex-direction:column;

    justify-content:flex-end;

    align-items:center;

    gap:8px;
}

.bar{

    width:100%;

    max-width:55px;

    background:#c89b6d;

    border-radius:6px 6px 0 0;

    min-height:3px;

    transition:.3s;
}

.bar:hover{
    background:#2b1b12;
}

.bar-value{

    font-size:12px;

    font-weight:bold;
}

.bar-label{

    font-size:12px;

    color:#777;
}


/* ================= POPULAR ITEMS ================= */

.popular-list{

    display:grid;

    gap:12px;
}

.popular-item{

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:15px;

    background:#f8f5f2;

    border-radius:8px;
}

.item-name{
    font-weight:bold;
}

.item-count{
    color:#8b5e3c;
    font-weight:bold;
}


/* ================= TABLE ================= */

.table-container{
    overflow-x:auto;
}

table{

    width:100%;

    border-collapse:collapse;
}

th,td{

    padding:14px;

    text-align:left;

    border-bottom:1px solid #eee;
}

th{

    background:#f8f5f2;

    color:#555;
}

.status{

    padding:5px 10px;

    border-radius:15px;

    font-size:12px;

    background:#e7f5e7;

    color:#267326;
}


/* ================= NO DATA ================= */

.no-data{

    text-align:center;

    padding:25px;

    color:#777;
}


/* ================= RESPONSIVE ================= */

@media(max-width:1000px){

    .stats{
        grid-template-columns:
        repeat(2,1fr);
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

    .main{
        margin-left:70px;
    }

}

@media(max-width:600px){

    .stats{
        grid-template-columns:1fr;
    }

    .content{
        padding:20px 15px;
    }

    .filter-box{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
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

<a href="admin_dashboard.php">

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

<a href="admin_login.html">

<i class="fa-solid fa-right-from-bracket"></i>

<span>Logout</span>

</a>

</div>

</aside>



<!-- ================= MAIN ================= -->

<main class="main">


<!-- TOPBAR -->

<div class="topbar">

<h1>Reports & Analysis</h1>

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

<h2>Reports & Analysis 📊</h2>

<p>

View your Brew&Desk business performance and activity.

</p>

</div>



<!-- FILTER -->

<div class="filter-box">

<div>

<strong>Report Period:</strong>

</div>


<form method="GET">

<select name="period">

<option value="week"
<?php if($period=="week") echo "selected"; ?>>

Weekly

</option>

<option value="month"
<?php if($period=="month") echo "selected"; ?>>

Monthly

</option>

<option value="year"
<?php if($period=="year") echo "selected"; ?>>

Yearly

</option>

</select>


<button type="submit">

<i class="fa-solid fa-arrows-rotate"></i>

Refresh

</button>

</form>

</div>



<!-- STATISTICS -->

<div class="stats">


<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-cart-shopping"></i>

</div>

<div class="stat-info">

<h3><?php echo $total_orders; ?></h3>

<p>Total Orders</p>

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-calendar-check"></i>

</div>

<div class="stat-info">

<h3><?php echo $total_reservations; ?></h3>

<p>Reservations</p>

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-users"></i>

</div>

<div class="stat-info">

<h3><?php echo $total_customers; ?></h3>

<p>Customers</p>

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-indian-rupee-sign"></i>

</div>

<div class="stat-info">

<h3>

₹<?php echo number_format($total_revenue,2); ?>

</h3>

<p>Total Revenue</p>

</div>

</div>


</div>



<!-- ORDERS CHART -->

<div class="section">

<div class="section-title">

<i class="fa-solid fa-chart-column"></i>

<h2>

Orders Overview - <?php echo $period_title; ?>

</h2>

</div>


<div class="chart">

<?php

for($i=0; $i<count($chart_values); $i++){

    $height =
        ($chart_values[$i] / $max_chart) * 210;

?>

<div class="bar-box">

<div class="bar-value">

<?php echo $chart_values[$i]; ?>

</div>

<div class="bar"
style="height:<?php echo $height; ?>px;">

</div>

<div class="bar-label">

<?php echo htmlspecialchars($chart_labels[$i]); ?>

</div>

</div>

<?php } ?>

</div>

</div>



<!-- POPULAR ITEMS -->

<div class="section">

<div class="section-title">

<i class="fa-solid fa-fire"></i>

<h2>Popular Items</h2>

</div>


<?php if(count($popular_items) > 0){ ?>

<div class="popular-list">

<?php foreach($popular_items as $item){ ?>

<div class="popular-item">

<span class="item-name">

<?php echo htmlspecialchars($item["item_name"]); ?>

</span>

<span class="item-count">

<?php echo $item["total_quantity"]; ?> orders

</span>

</div>

<?php } ?>

</div>

<?php } else { ?>

<div class="no-data">

No popular item data available for this period.

</div>

<?php } ?>

</div>



<!-- RECENT REVENUE -->

<div class="section">

<div class="section-title">

<i class="fa-solid fa-money-bill-trend-up"></i>

<h2>Recent Revenue</h2>

</div>


<div class="table-container">

<table>

<thead>

<tr>

<th>Date</th>

<th>Orders</th>

<th>Reservations</th>

<th>Revenue</th>

<th>Status</th>

</tr>

</thead>


<tbody>

<?php if(count($recent_revenue) > 0){ ?>

<?php foreach($recent_revenue as $row){ ?>

<tr>

<td>

<?php

echo date(
    "d M Y",
    strtotime($row["report_date"])
);

?>

</td>


<td>

<?php echo $row["orders_count"]; ?>

</td>


<td>

<?php echo $row["reservations_count"]; ?>

</td>


<td>

₹<?php

echo number_format(
    $row["revenue"],
    2
);

?>

</td>


<td>

<span class="status">

<?php echo $row["report_status"]; ?>

</span>

</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>

<td colspan="5" class="no-data">

No revenue data available for this period.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>


</div>

</main>


</body>

</html>

<?php

mysqli_close($conn);

?>