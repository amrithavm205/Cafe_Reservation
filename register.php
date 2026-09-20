<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);

    // Check if email already exists
    $check = mysqli_prepare(
        $conn,
        "SELECT customer_id FROM users WHERE email = ?"
    );

    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {

        echo "<script>
                alert('Email already registered!');
                window.location.href='register.html';
              </script>";

    } else {

        // Insert customer details
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (name, email, phone) VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $phone);

        if (mysqli_stmt_execute($stmt)) {

            echo "<script>
                    alert('Registration Successful!');
                    window.location.href='login.html';
                  </script>";

        } else {

            echo "Registration failed: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_stmt_close($check);
}

mysqli_close($conn);

?>