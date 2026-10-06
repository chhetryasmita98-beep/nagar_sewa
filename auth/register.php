<?php

session_start();

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($address) ||
        empty($password)
    ) {
        $error = "Please fill in all fields.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    elseif (strlen($password) < 6) {
        $error = "Password must contain at least 6 characters.";
    }

    else {

        // Check whether email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "An account with this email already exists.";

        } else {

            // Secure password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, email, phone, address, password)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $full_name,
                $email,
                $phone,
                $address,
                $hashed_password
            );

            if ($stmt->execute()) {

                $success = "Registration successful. You can now login.";

            } else {

                $error = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Citizen Registration - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="register.css"
    >

</head>

<body>

<div class="register-page">

    <div class="register-container">

        <div class="register-header">

            <h1>Nagar Sewa</h1>

            <p>Create Citizen Account</p>

        </div>

        <?php if (!empty($error)): ?>

            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>

            <div class="message success">

                <?php echo htmlspecialchars($success); ?>

                <a href="login.php">
                    Login Now
                </a>

            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                    required
                >

            </div>


            <div class="form-group">

                <label for="address">
                    Address
                </label>

                <textarea
                    id="address"
                    name="address"
                    placeholder="Enter your address"
                    rows="3"
                    required
                ></textarea>

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="register-btn"
            >
                Create Account
            </button>

        </form>


        <div class="login-link">

            <p>
                Already have an account?
                <a href="login.php">Login</a>
            </p>

            <a
                href="../index.php"
                class="home-link"
            >
                ← Back to Home
            </a>

        </div>

    </div>

</div>

</body>

</html>