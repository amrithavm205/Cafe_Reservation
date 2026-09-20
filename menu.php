<?php

include "db.php";

header("Content-Type: application/json");

$sql = "
    SELECT item_id, item_name, description, price, category
    FROM menu
    ORDER BY item_id ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to load menu."
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
        "category" => $row["category"]
    ];
}

echo json_encode([
    "success" => true,
    "menu" => $menu
]);

mysqli_close($conn);

?>