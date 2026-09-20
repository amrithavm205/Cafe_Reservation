<?php

session_start();

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        die("Please enter email and password.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT admin_id, email, password
         FROM admin
         WHERE email = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $admin = mysqli_fetch_assoc($result);

        if ($password === $admin["password"]) {

            $_SESSION["admin_id"] = $admin["admin_id"];
            $_SESSION["admin_email"] = $admin["email"];

            echo "<script>
                    alert('Admin Login Successful!');
                    window.location.href='admin_dashboard.php';
                  </script>";

        } else {

            echo "<script>
                    alert('Incorrect password.');
                    window.location.href='admin_login.html';
                  </script>";
        }

    } else {

        echo "<script>
                alert('Admin email not found.');
                window.location.href='admin_login.html';
              </script>";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

?>