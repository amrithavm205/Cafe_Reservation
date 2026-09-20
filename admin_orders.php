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

/*
    Get all orders with customer details
*/
$sql = "
    SELECT 
        o.order_id,
        u.name AS customer_name,
        o.order_date,
        o.total_amount,
        o.status
    FROM orders o
    INNER JOIN users u
        ON o.customer_id = u.customer_id
    ORDER BY o.order_id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch orders."
    ]);
    exit();
}

$orders = [];

while ($row = mysqli_fetch_assoc($result)) {

    // Get items for this order
    $item_sql = "
        SELECT 
            m.item_name,
            oi.quantity
        FROM order_items oi
        INNER JOIN menu m
            ON oi.item_id = m.item_id
        WHERE oi.order_id = ?
    ";

    $stmt = mysqli_prepare($conn, $item_sql);
    mysqli_stmt_bind_param($stmt, "i", $row["order_id"]);
    mysqli_stmt_execute($stmt);

    $item_result = mysqli_stmt_get_result($stmt);

    $items = [];

    while ($item = mysqli_fetch_assoc($item_result)) {
        $items[] = $item["item_name"] . " x" . $item["quantity"];
    }

    mysqli_stmt_close($stmt);

    $orders[] = [
        "order_id" => $row["order_id"],
        "customer_name" => $row["customer_name"],
        "items" => implode(", ", $items),
        "order_date" => date("Y-m-d", strtotime($row["order_date"])),
        "order_time" => date("h:i A", strtotime($row["order_date"])),
        "total_amount" => $row["total_amount"],
        "payment" => "Pending",
        "status" => $row["status"]
    ];
}


/*
    Summary information
*/

$total_orders = 0;
$pending_orders = 0;
$preparing_orders = 0;
$total_revenue = 0;

$summary_sql = "
    SELECT 
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'Preparing' THEN 1 ELSE 0 END) AS preparing_orders,
        COALESCE(SUM(total_amount), 0) AS total_revenue
    FROM orders
";

$summary_result = mysqli_query($conn, $summary_sql);

if ($summary_result) {

    $summary = mysqli_fetch_assoc($summary_result);

    $total_orders = (int)$summary["total_orders"];
    $pending_orders = (int)$summary["pending_orders"];
    $preparing_orders = (int)$summary["preparing_orders"];
    $total_revenue = (float)$summary["total_revenue"];
}


/*
    Send response
*/

echo json_encode([
    "success" => true,
    "orders" => $orders,
    "summary" => [
        "total_orders" => $total_orders,
        "pending_orders" => $pending_orders,
        "preparing_orders" => $preparing_orders,
        "total_revenue" => $total_revenue
    ]
]);

mysqli_close($conn);

?>