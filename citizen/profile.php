<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$success_message = "";
$error_message = "";


/* =========================================================
   FETCH USER
========================================================= */

$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, address, password, created_at
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   UPDATE PROFILE
========================================================= */

if (isset($_POST["update_profile"])) {

    $full_name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    if ($full_name === "" || $phone === "") {

        $error_message = "Full name and phone number are required.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE users
             SET full_name = ?, phone = ?, address = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $full_name,
            $phone,
            $address,
            $user_id
        );

        if ($stmt->execute()) {

            $_SESSION["user_name"] = $full_name;

            $user["full_name"] = $full_name;
            $user["phone"] = $phone;
            $user["address"] = $address;

            $success_message =
                "Profile information updated successfully.";

        } else {

            $error_message =
                "Unable to update profile. Please try again.";
        }

        $stmt->close();
    }
}


/* =========================================================
   CHANGE PASSWORD
========================================================= */

if (isset($_POST["change_password"])) {

    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];


    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $error_message =
            "Please fill in all password fields.";

    } elseif (
        !password_verify(
            $current_password,
            $user["password"]
        )
    ) {

        $error_message =
            "Current password is incorrect.";

    } elseif (strlen($new_password) < 6) {

        $error_message =
            "New password must be at least 6 characters long.";

    } elseif ($new_password !== $confirm_password) {

        $error_message =
            "New passwords do not match.";

    } else {

        $hashed_password = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "si",
            $hashed_password,
            $user_id
        );

        if ($stmt->execute()) {

            $success_message =
                "Password changed successfully.";

            $user["password"] = $hashed_password;

        } else {

            $error_message =
                "Unable to change password. Please try again.";
        }

        $stmt->close();
    }
}


/* =========================================================
   USER INITIAL
========================================================= */

$first_letter = strtoupper(
    substr($user["full_name"], 0, 1)
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="profile.css"
    >

</head>

<body>


<div class="page-wrapper">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <div class="logo">

            <div class="logo-icon">
                🏛️
            </div>

            <div>

                <h2>
                    Nagar Sewa
                </h2>

                <span>
                    Citizen Portal
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">


            <a href="dashboard.php">

                <span>🏠</span>

                Dashboard

            </a>


            <a href="services.php">

                <span>📋</span>

                Government Services

            </a>


            <a href="track-application.php">

                <span>🔍</span>

                Track Application

            </a>


            <a href="notices.php">

                <span>📢</span>

                Notices

            </a>


            <a href="complaints.php">

                <span>💬</span>

                Complaints

            </a>


            <a
                href="profile.php"
                class="active"
            >

                <span>👤</span>

                My Profile

            </a>


            <a
                href="../auth/logout.php"
                class="logout-link"
            >

                <span>🚪</span>

                Logout

            </a>


        </nav>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             HEADER
        ================================================== -->

        <header class="top-header">


            <div>

                <h1>
                    My Profile
                </h1>

                <p>
                    Manage your personal information and account settings.
                </p>

            </div>


            <div class="user-info">


                <div class="user-avatar">

                    <?php
                    echo htmlspecialchars(
                        $first_letter
                    );
                    ?>

                </div>


                <div>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $user["full_name"]
                        );
                        ?>

                    </strong>

                    <small>
                        Citizen
                    </small>

                </div>


            </div>


        </header>


        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <div class="content">


            <!-- SUCCESS MESSAGE -->

            <?php if ($success_message !== ""): ?>

                <div class="alert success-alert">

                    <div class="alert-symbol">
                        ✓
                    </div>

                    <div>
                        <?php
                        echo htmlspecialchars(
                            $success_message
                        );
                        ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error_message !== ""): ?>

                <div class="alert error-alert">

                    <div class="alert-symbol">
                        !
                    </div>

                    <div>
                        <?php
                        echo htmlspecialchars(
                            $error_message
                        );
                        ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 PROFILE OVERVIEW
            ================================================== -->

            <section class="profile-overview card">


                <div class="large-avatar">

                    <?php
                    echo htmlspecialchars(
                        $first_letter
                    );
                    ?>

                </div>


                <div class="profile-overview-info">

                    <h2>

                        <?php
                        echo htmlspecialchars(
                            $user["full_name"]
                        );
                        ?>

                    </h2>


                    <p>

                        <?php
                        echo htmlspecialchars(
                            $user["email"]
                        );
                        ?>

                    </p>


                    <span class="citizen-badge">
                        Citizen Account
                    </span>

                </div>


                <div class="member-info">

                    <span>
                        Member Since
                    </span>

                    <strong>

                        <?php
                        echo date(
                            "d M Y",
                            strtotime(
                                $user["created_at"]
                            )
                        );
                        ?>

                    </strong>

                </div>


            </section>


            <!-- =================================================
                 PROFILE GRID
            ================================================== -->

            <div class="profile-grid">


                <!-- =================================================
                     PERSONAL INFORMATION
                ================================================== -->

                <section class="card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Personal Information
                            </h2>

                            <p>
                                Update your basic account information.
                            </p>

                        </div>


                        <div class="card-icon">
                            👤
                        </div>

                    </div>


                    <form
                        method="POST"
                        class="profile-form"
                    >


                        <div class="form-group">

                            <label>
                                Full Name
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                value="<?php
                                echo htmlspecialchars(
                                    $user["full_name"]
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Email Address
                            </label>

                            <input
                                type="email"
                                value="<?php
                                echo htmlspecialchars(
                                    $user["email"]
                                );
                                ?>"
                                disabled
                            >

                            <small>
                                Email address cannot be changed.
                            </small>

                        </div>


                        <div class="form-group">

                            <label>
                                Phone Number
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="phone"
                                value="<?php
                                echo htmlspecialchars(
                                    $user["phone"]
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Address
                            </label>

                            <textarea
                                name="address"
                                placeholder="Enter your address"
                            ><?php
                            echo htmlspecialchars(
                                $user["address"] ?? ""
                            );
                            ?></textarea>

                        </div>


                        <button
                            type="submit"
                            name="update_profile"
                            class="primary-btn"
                        >
                            Update Profile
                        </button>


                    </form>

                </section>


                <!-- =================================================
                     CHANGE PASSWORD
                ================================================== -->

                <section class="card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Change Password
                            </h2>

                            <p>
                                Keep your account secure.
                            </p>

                        </div>


                        <div class="card-icon password-icon">
                            🔒
                        </div>

                    </div>


                    <form
                        method="POST"
                        class="profile-form"
                    >


                        <div class="security-note">

                            <div class="security-icon">
                                🔐
                            </div>

                            <div>

                                <strong>
                                    Security Tip
                                </strong>

                                <p>
                                    Use a strong password with at least
                                    6 characters and do not share it
                                    with anyone.
                                </p>

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Current Password
                                <span>*</span>
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                placeholder="Enter current password"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                New Password
                                <span>*</span>
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                placeholder="Enter new password"
                                minlength="6"
                                required
                            >

                            <small>
                                Minimum 6 characters.
                            </small>

                        </div>


                        <div class="form-group">

                            <label>
                                Confirm New Password
                                <span>*</span>
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                placeholder="Confirm new password"
                                minlength="6"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            name="change_password"
                            class="primary-btn"
                        >
                            Change Password
                        </button>


                    </form>

                </section>


            </div>


            <!-- =================================================
                 ACCOUNT INFORMATION
            ================================================== -->

            <section class="account-info-card card">


                <div class="card-header">

                    <div>

                        <h2>
                            Account Information
                        </h2>

                        <p>
                            Overview of your Nagar Sewa account.
                        </p>

                    </div>


                    <div class="card-icon">
                        ℹ️
                    </div>

                </div>


                <div class="account-details">


                    <div class="account-detail">

                        <span>
                            Account ID
                        </span>

                        <strong>
                            #<?php
                            echo htmlspecialchars(
                                $user["id"]
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="account-detail">

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $user["email"]
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="account-detail">

                        <span>
                            Account Type
                        </span>

                        <strong class="account-type">
                            Citizen
                        </strong>

                    </div>


                    <div class="account-detail">

                        <span>
                            Registered Date
                        </span>

                        <strong>
                            <?php
                            echo date(
                                "d M Y",
                                strtotime(
                                    $user["created_at"]
                                )
                            );
                            ?>
                        </strong>

                    </div>


                </div>

            </section>


        </div>

    </main>

</div>


</body>

</html>