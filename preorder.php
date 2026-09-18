<?php

session_start();
include "db.php";

header("Content-Type: application/json");

// Check login
if (!isset($_SESSION["customer_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login before confirming your order."
    ]);
    exit();
}

$customer_id = $_SESSION["customer_id"];

// Get data from JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["cart"]) || empty($data["cart"])) {
    echo json_encode([
        "success" => false,
        "message" => "Your pre-order cart is empty."
    ]);
    exit();
}

$cart = $data["cart"];

// Get payment method
$payment_method = $data["payment_method"] ?? "";

if (empty($payment_method)) {
    echo json_encode([
        "success" => false,
        "message" => "Please select a payment method."
    ]);
    exit();
}

// Allow only these payment methods
if ($payment_method !== "UPI" && $payment_method !== "Cash") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid payment method."
    ]);
    exit();
}

mysqli_begin_transaction($conn);

try {

    $total_amount = 0;
    $order_items = [];

    // Get item details from database
    foreach ($cart as $item) {

        $item_name = $item["name"];
        $quantity = (int)$item["quantity"];

        if ($quantity <= 0) {
            continue;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT item_id, price FROM menu WHERE item_name = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $item_name
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 0) {
            throw new Exception(
                "Item not found: " . $item_name
            );
        }

        $menu_item = mysqli_fetch_assoc($result);

        $item_id = $menu_item["item_id"];
        $price = $menu_item["price"];

        $total_amount += $price * $quantity;

        $order_items[] = [
            "item_id" => $item_id,
            "quantity" => $quantity,
            "price" => $price
        ];

        mysqli_stmt_close($stmt);
    }

    if (empty($order_items)) {
        throw new Exception(
            "No valid items in the cart."
        );
    }


    // ==========================
    // CREATE ORDER
    // ==========================

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO orders
        (customer_id, order_date, total_amount, status)
        VALUES (?, NOW(), ?, 'Pending')"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "id",
        $customer_id,
        $total_amount
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Failed to create order."
        );
    }

    $order_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);


    // ==========================
    // ADD ORDER ITEMS
    // ==========================

    foreach ($order_items as $item) {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO order_items
            (order_id, item_id, quantity, price)
            VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iiid",
            $order_id,
            $item["item_id"],
            $item["quantity"],
            $item["price"]
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(
                "Failed to save order items."
            );
        }

        mysqli_stmt_close($stmt);
    }


    // ==========================
    // SAVE PAYMENT
    // ==========================

    $payment_status = "Pending";

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO payments
        (order_id, amount, payment_method, payment_status, payment_date)
        VALUES (?, ?, ?, ?, NOW())"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "idss",
        $order_id,
        $total_amount,
        $payment_method,
        $payment_status
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Failed to save payment."
        );
    }

    mysqli_stmt_close($stmt);


    // ==========================
    // CONFIRM EVERYTHING
    // ==========================

    mysqli_commit($conn);

    echo json_encode([
        "success" => true,
        "message" => "Order and payment saved successfully!",
        "order_id" => $order_id,
        "total" => $total_amount,
        "payment_method" => $payment_method,
        "payment_status" => $payment_status
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

mysqli_close($conn);

?>