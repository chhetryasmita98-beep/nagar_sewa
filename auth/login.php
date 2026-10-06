<?php

session_start();

require_once "../config/database.php";

$error = "";

if (isset($_SESSION["user_id"])) {
    header("Location: ../citizen/dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if ($stmt) {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["full_name"];
                    $_SESSION["user_email"] = $user["email"];

                    header("Location: ../citizen/dashboard.php");
                    exit;

                } else {

                    $error = "Incorrect email or password.";

                }

            } else {

                $error = "Incorrect email or password.";

            }

            $stmt->close();

        } else {

            $error = "Something went wrong. Please try again.";

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

    <title>Citizen Login | Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="login.css"
    >

</head>

<body>

<div class="login-page">

    <!-- ================= LEFT SECTION ================= -->

    <div class="login-info">

        <div class="info-content">

            <a href="../index.php" class="brand">

                <div class="brand-logo">
                    🏛️
                </div>

                <div>
                    <h2>Nagar Sewa</h2>
                    <span>Digital Municipal Services</span>
                </div>

            </a>


            <div class="info-main">

                <div class="welcome-badge">
                    ● Citizen Portal
                </div>

                <h1>
                    Government Services,
                    <span>Made Simple.</span>
                </h1>

                <p>
                    Access municipal services online, submit
                    applications, upload documents, track your
                    application and download approved certificates.
                </p>


                <div class="features">

                    <div class="feature">

                        <div class="feature-icon">
                            📋
                        </div>

                        <div>
                            <strong>Apply Online</strong>

                            <span>
                                Submit municipal service applications
                                from anywhere.
                            </span>
                        </div>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            🔍
                        </div>

                        <div>
                            <strong>Track Applications</strong>

                            <span>
                                Check your application status anytime.
                            </span>
                        </div>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            📜
                        </div>

                        <div>
                            <strong>Digital Certificates</strong>

                            <span>
                                Download approved certificates online.
                            </span>
                        </div>

                    </div>

                </div>

            </div>


            <div class="info-footer">

                <span>
                    © <?php echo date("Y"); ?> Nagar Sewa
                </span>

                <span>
                    Secure Citizen Portal
                </span>

            </div>

        </div>

    </div>


    <!-- ================= LOGIN SECTION ================= -->

    <div class="login-section">

        <div class="mobile-logo">

            <div class="mobile-logo-icon">
                🏛️
            </div>

            <div>
                <strong>Nagar Sewa</strong>
                <span>Citizen Portal</span>
            </div>

        </div>


        <div class="login-card">

            <div class="login-heading">

                <div class="login-icon">
                    👤
                </div>

                <div>

                    <h1>
                        Welcome Back
                    </h1>

                    <p>
                        Login to your citizen account
                    </p>

                </div>

            </div>


            <?php if (!empty($error)): ?>

                <div class="error-message">

                    <span class="error-icon">
                        !
                    </span>

                    <span>
                        <?php echo htmlspecialchars($error); ?>
                    </span>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                class="login-form"
            >

                <!-- Email -->

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
                            placeholder="Enter your email"
                            value="<?php echo htmlspecialchars($email ?? ""); ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <!-- Password -->

                <div class="form-group">

                    <div class="password-label">

                        <label for="password">
                            Password
                        </label>

                    </div>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Show password"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <!-- Login -->

                <button
                    type="submit"
                    class="login-button"
                >

                    <span>
                        Login to Account
                    </span>

                    <span class="button-arrow">
                        →
                    </span>

                </button>

            </form>


            <!-- Register -->

            <div class="register-section">

                <p>
                    Don't have an account?
                </p>

                <a href="register.php">
                    Create a Citizen Account
                    →
                </a>

            </div>


            <!-- Security -->

            <div class="security-message">

                <span>
                    🔒
                </span>

                <p>
                    Your information is securely protected.
                </p>

            </div>


            <!-- Home -->

            <a
                href="../index.php"
                class="back-link"
            >
                ← Back to Nagar Sewa
            </a>

        </div>

    </div>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const button =
        document.querySelector(".password-toggle");

    if (password.type === "password") {

        password.type = "text";

        button.textContent = "🙈";

        button.setAttribute(
            "aria-label",
            "Hide password"
        );

    } else {

        password.type = "password";

        button.textContent = "👁";

        button.setAttribute(
            "aria-label",
            "Show password"
        );

    }

}

</script>

</body>

</html>