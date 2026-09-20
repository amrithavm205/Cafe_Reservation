<?php

session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check customer login
    if (!isset($_SESSION["customer_id"])) {
        echo "<script>
                alert('Please login before making a reservation.');
                window.location.href='login.html';
              </script>";
        exit();
    }

    $customer_id = $_SESSION["customer_id"];

    $reservation_date = $_POST["reservation_date"];
    $reservation_time = $_POST["reservation_time"];
    $guests = (int)$_POST["guests"];
    $special_occasion = $_POST["special_occasion"] ?? "None";
    $special_request = trim($_POST["special_request"] ?? "");

    // Add special occasion to request
    if ($special_occasion != "None") {
        if (!empty($special_request)) {
            $special_request = $special_occasion . " - " . $special_request;
        } else {
            $special_request = $special_occasion;
        }
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {

        /*
         * Find a suitable available table.
         * Choose the smallest table that can accommodate
         * the number of guests.
         */
        $table_stmt = mysqli_prepare(
            $conn,
            "SELECT table_id, table_number
             FROM cafe_tables
             WHERE capacity >= ?
             AND status = 'Available'
             ORDER BY capacity ASC, table_id ASC
             LIMIT 1
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($table_stmt, "i", $guests);
        mysqli_stmt_execute($table_stmt);

        $table_result = mysqli_stmt_get_result($table_stmt);

        if (mysqli_num_rows($table_result) == 0) {

            mysqli_rollback($conn);

            echo "<script>
                    alert('Sorry! No suitable table is available for $guests guests.');
                    window.location.href='reservation.html';
                  </script>";
            exit();
        }

        $table = mysqli_fetch_assoc($table_result);

        $table_id = $table["table_id"];
        $table_number = $table["table_number"];

        mysqli_stmt_close($table_stmt);


        // Insert reservation with assigned table
        $reservation_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO reservations
            (customer_id, table_id, reservation_date, reservation_time, guests, special_request, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
        );

        mysqli_stmt_bind_param(
            $reservation_stmt,
            "iissis",
            $customer_id,
            $table_id,
            $reservation_date,
            $reservation_time,
            $guests,
            $special_request
        );

        if (!mysqli_stmt_execute($reservation_stmt)) {
            throw new Exception("Reservation could not be saved.");
        }

        mysqli_stmt_close($reservation_stmt);


        // Mark the table as reserved
        $update_table = mysqli_prepare(
            $conn,
            "UPDATE cafe_tables
             SET status = 'Reserved'
             WHERE table_id = ?"
        );

        mysqli_stmt_bind_param(
            $update_table,
            "i",
            $table_id
        );

        if (!mysqli_stmt_execute($update_table)) {
            throw new Exception("Table status could not be updated.");
        }

        mysqli_stmt_close($update_table);


        // Everything successful
        mysqli_commit($conn);

        echo "<script>
                alert('Reservation Confirmed Successfully!\\n\\nTable $table_number has been assigned automatically.');
                window.location.href='reservation_success.html';
              </script>";

    } catch (Exception $e) {

        mysqli_rollback($conn);

        echo "<script>
                alert('Reservation failed. Please try again.');
                window.location.href='reservation.html';
              </script>";
    }
}

mysqli_close($conn);

?>