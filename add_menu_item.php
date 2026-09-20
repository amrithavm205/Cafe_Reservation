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


// Get JSON data
$data = json_decode(
    file_get_contents("php://input"),
    true
);


if (!$data) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid item data."
    ]);

    exit();
}


// Get values
$item_name = trim($data["item_name"] ?? "");
$category = strtolower(trim($data["category"] ?? ""));
$price = (float)($data["price"] ?? 0);


// Validate
if (
    empty($item_name) ||
    empty($category) ||
    $price <= 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter all required details."
    ]);

    exit();
}


// Allowed categories
$allowed_categories = [
    "iced",
    "hot",
    "toast",
    "dessert"
];


if (!in_array($category, $allowed_categories)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid category."
    ]);

    exit();
}


// Check whether item already exists
$check = mysqli_prepare(
    $conn,
    "SELECT item_id FROM menu WHERE item_name = ?"
);

mysqli_stmt_bind_param(
    $check,
    "s",
    $item_name
);

mysqli_stmt_execute($check);

mysqli_stmt_store_result($check);


if (mysqli_stmt_num_rows($check) > 0) {

    echo json_encode([
        "success" => false,
        "message" => "This menu item already exists."
    ]);

    mysqli_stmt_close($check);
    mysqli_close($conn);

    exit();
}

mysqli_stmt_close($check);


// Insert menu item
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO menu
    (item_name, description, price, category)
    VALUES (?, ?, ?, ?)"
);


// We don't have a description field in the Add form,
// so use a simple default description.
$description = "Freshly prepared at Brew&Desk";


mysqli_stmt_bind_param(
    $stmt,
    "ssds",
    $item_name,
    $description,
    $price,
    $category
);


if (mysqli_stmt_execute($stmt)) {

    echo json_encode([
        "success" => true,
        "message" => "Menu item added successfully!",
        "item_id" => mysqli_insert_id($conn)
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to add menu item."
    ]);
}


mysqli_stmt_close($stmt);
mysqli_close($conn);

?>