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

// Get data from JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["order_id"]) || !isset($data["status"])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid order data."
    ]);
    exit();
}

$order_id = (int)$data["order_id"];
$status = trim($data["status"]);

// Allowed statuses
$allowed_statuses = [
    "Pending",
    "Confirmed",
    "Preparing",
    "Completed",
    "Cancelled"
];

if (!in_array($status, $allowed_statuses)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid order status."
    ]);
    exit();
}

// Update order status
$stmt = mysqli_prepare(
    $conn,
    "UPDATE orders SET status = ? WHERE order_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $status,
    $order_id
);

if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) >= 0) {

        echo json_encode([
            "success" => true,
            "message" => "Order status updated successfully."
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Order not found."
        ]);

    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to update order."
    ]);

}

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>