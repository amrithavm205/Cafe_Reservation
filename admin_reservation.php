<?php

session_start();

include "db.php";

header("Content-Type: application/json");


/* ================= CHECK ADMIN LOGIN ================= */

if (!isset($_SESSION["admin_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Admin login required."
    ]);

    exit();
}


/* ================= GET RESERVATIONS ================= */

$sql = "
    SELECT
        r.reservation_id,
        u.name AS customer_name,
        u.phone,
        u.email,
        r.reservation_date,
        r.reservation_time,
        r.guests,
        r.special_request
    FROM reservations r
    INNER JOIN users u
        ON r.customer_id = u.customer_id
    ORDER BY r.reservation_id DESC
";


$result = mysqli_query($conn, $sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch reservations."
    ]);

    exit();
}


$reservations = [];


while ($row = mysqli_fetch_assoc($result)) {

    $reservations[] = [

        "reservation_id" =>
            $row["reservation_id"],

        "customer_name" =>
            $row["customer_name"],

        "phone" =>
            $row["phone"],

        "email" =>
            $row["email"],

        "reservation_date" =>
            date(
                "d-m-Y",
                strtotime($row["reservation_date"])
            ),

        "reservation_time" =>
            date(
                "h:i A",
                strtotime($row["reservation_time"])
            ),

        "guests" =>
            $row["guests"],

        "table" =>
            "Auto Assign",

        "status" =>
            "Pending",

        "special_request" =>
            $row["special_request"]

    ];
}


/* ================= SUMMARY ================= */

$total_reservations =
    count($reservations);

$pending_reservations = 0;

$approved_reservations = 0;


foreach ($reservations as $reservation) {

    if ($reservation["status"] == "Pending") {

        $pending_reservations++;

    }

    if ($reservation["status"] == "Approved") {

        $approved_reservations++;

    }

}


/* ================= RESPONSE ================= */

echo json_encode([

    "success" => true,

    "reservations" => $reservations,

    "summary" => [

        "total_reservations" =>
            $total_reservations,

        "pending_reservations" =>
            $pending_reservations,

        "approved_reservations" =>
            $approved_reservations

    ]

]);


mysqli_close($conn);

?>