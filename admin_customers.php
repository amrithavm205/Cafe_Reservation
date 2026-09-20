<?php

session_start();

include "db.php";

header("Content-Type: application/json");

// Check admin login
if (!isset($_SESSION["admin_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Admin login required."
    ]);

    exit();
}


// Get all customers
$sql = "
    SELECT
        u.customer_id,
        u.name,
        u.email,
        u.phone
    FROM users u
    ORDER BY u.customer_id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch customers."
    ]);

    exit();
}


$customers = [];


while ($row = mysqli_fetch_assoc($result)) {

    $customer_id = (int)$row["customer_id"];


    // Count reservations
    $reservation_sql = "
        SELECT COUNT(*) AS total
        FROM reservations
        WHERE customer_id = ?
    ";

    $stmt1 = mysqli_prepare($conn, $reservation_sql);

    mysqli_stmt_bind_param(
        $stmt1,
        "i",
        $customer_id
    );

    mysqli_stmt_execute($stmt1);

    $reservation_result =
        mysqli_stmt_get_result($stmt1);

    $reservation_data =
        mysqli_fetch_assoc($reservation_result);

    $reservation_count =
        (int)$reservation_data["total"];

    mysqli_stmt_close($stmt1);


    // Count orders
    $order_sql = "
        SELECT COUNT(*) AS total
        FROM orders
        WHERE customer_id = ?
    ";

    $stmt2 = mysqli_prepare($conn, $order_sql);

    mysqli_stmt_bind_param(
        $stmt2,
        "i",
        $customer_id
    );

    mysqli_stmt_execute($stmt2);

    $order_result =
        mysqli_stmt_get_result($stmt2);

    $order_data =
        mysqli_fetch_assoc($order_result);

    $order_count =
        (int)$order_data["total"];

    mysqli_stmt_close($stmt2);


    $customers[] = [

        "customer_id" => $customer_id,

        "name" => $row["name"],

        "email" => $row["email"],

        "phone" => $row["phone"],

        "date_joined" => "N/A",

        "reservations" => $reservation_count,

        "orders" => $order_count,

        "status" => "Active"

    ];
}


$total_customers = count($customers);


// Since customers table has no blocked/status column,
// all registered customers are currently shown as Active.
$active_customers = $total_customers;


echo json_encode([

    "success" => true,

    "customers" => $customers,

    "summary" => [

        "total_customers" => $total_customers,

        "active_customers" => $active_customers

    ]

]);


mysqli_close($conn);

?>