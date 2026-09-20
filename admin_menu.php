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


// Fetch menu items
$sql = "
    SELECT
        item_id,
        item_name,
        description,
        price,
        category
    FROM menu
    ORDER BY item_id ASC
";

$result = mysqli_query($conn, $sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch menu items."
    ]);

    exit();
}


$menu = [];


while ($row = mysqli_fetch_assoc($result)) {

    $menu[] = [

        "item_id" => (int)$row["item_id"],

        "item_name" => $row["item_name"],

        "description" => $row["description"],

        "price" => (float)$row["price"],

        "category" => $row["category"],

        "availability" => "Available"

    ];
}


$total_items = count($menu);

$available_items = $total_items;

$unavailable_items = 0;


echo json_encode([

    "success" => true,

    "menu" => $menu,

    "summary" => [

        "total_items" => $total_items,

        "available_items" => $available_items,

        "unavailable_items" => $unavailable_items

    ]

]);


mysqli_close($conn);

?>