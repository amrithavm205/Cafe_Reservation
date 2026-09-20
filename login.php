<?php

session_start();

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);

    if (empty($email)) {
        die("Please enter your email.");
    }

    // Check whether the email exists
    $stmt = mysqli_prepare(
        $conn,
        "SELECT customer_id, name, email, phone FROM users WHERE email = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    // If email is found
    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        // Store customer details in session
        $_SESSION["customer_id"] = $user["customer_id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["phone"] = $user["phone"];

        echo "<script>
                alert('Login Successful!');
                window.location.href='index.html';
              </script>";

    } else {

        // Email not found
        echo "<script>
                alert('Email not registered. Please register first.');
                window.location.href='login.html';
              </script>";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

?>