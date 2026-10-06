<?php

session_start();

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password
             FROM admins
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        $stmt->close();

        if (
            $admin &&
            password_verify($password, $admin["password"])
        ) {

            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_name"] = $admin["full_name"];
            $_SESSION["admin_email"] = $admin["email"];

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid admin email or password.";
        }
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

    <title>Admin Login - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="login.css"
    >

</head>

<body>

<div class="login-page">

    <!-- Left Information Section -->

    <div class="login-info">

        <div class="info-content">

            <div class="brand">

                <div class="brand-logo">
                    NS
                </div>

                <div>

                    <h1>Nagar Sewa</h1>

                    <span>
                        Administration Portal
                    </span>

                </div>

            </div>


            <div class="info-main">

                <h2>
                    Manage Government Services
                </h2>

                <p>
                    Access the Nagar Sewa administration panel
                    to manage citizen applications, services,
                    documents and complaints.
                </p>


                <div class="features">

                    <div class="feature">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Application Management
                            </strong>

                            <span>
                                Review and process citizen applications.
                            </span>

                        </div>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Citizen Management
                            </strong>

                            <span>
                                Manage registered citizen accounts.
                            </span>

                        </div>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Service Management
                            </strong>

                            <span>
                                Manage available government services.
                            </span>

                        </div>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Reports & Monitoring
                            </strong>

                            <span>
                                Monitor applications and system activity.
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <div class="info-footer">

                © <?php echo date("Y"); ?> Nagar Sewa.
                Administration Portal.

            </div>

        </div>

    </div>


    <!-- Right Login Section -->

    <div class="login-section">

        <div class="login-card">


            <div class="mobile-logo">

                <div class="mobile-logo-icon">
                    NS
                </div>

                <div>

                    <h2>
                        Nagar Sewa
                    </h2>

                    <span>
                        Admin Portal
                    </span>

                </div>

            </div>


            <div class="login-heading">

                <h2>
                    Admin Login
                </h2>

                <p>
                    Sign in to access the administration dashboard.
                </p>

            </div>


            <?php if ($error): ?>

                <div class="error-message">

                    <span class="error-icon">
                        !
                    </span>

                    <span>
                        <?php
                        echo htmlspecialchars($error);
                        ?>
                    </span>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                class="login-form"
            >


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            ✉
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter admin email"
                            value="<?php
                            echo htmlspecialchars(
                                $_POST["email"] ?? ""
                            );
                            ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter admin password"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="login-button"
                >
                    Sign In to Admin Panel
                </button>


            </form>


            <div class="security-message">

                <span>
                    🔒
                </span>

                <p>
                    This is a restricted administration area.
                    Authorized personnel only.
                </p>

            </div>


            <div class="back-link">

                <a href="../index.php">
                    ← Back to Nagar Sewa
                </a>

            </div>


        </div>

    </div>

</div>

</body>

</html>