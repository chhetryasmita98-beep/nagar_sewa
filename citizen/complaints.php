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
   SUBMIT COMPLAINT
========================================================= */

if (isset($_POST["submit_complaint"])) {

    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);

    if ($subject === "" || $message === "") {

        $error_message =
            "Please enter both subject and complaint message.";

    } elseif (strlen($subject) < 5) {

        $error_message =
            "Complaint subject must be at least 5 characters.";

    } elseif (strlen($message) < 10) {

        $error_message =
            "Complaint message must be at least 10 characters.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO complaints
            (user_id, subject, message)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "iss",
            $user_id,
            $subject,
            $message
        );

        if ($stmt->execute()) {

            $success_message =
                "Your complaint has been submitted successfully.";

        } else {

            $error_message =
                "Unable to submit complaint. Please try again.";
        }

        $stmt->close();
    }
}


/* =========================================================
   FETCH USER NAME
========================================================= */

$stmt = $conn->prepare(
    "SELECT full_name
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$stmt->close();


if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   FETCH COMPLAINTS
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        id,
        subject,
        message,
        status,
        admin_reply,
        created_at
     FROM complaints
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$complaints_result = $stmt->get_result();

$stmt->close();


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

    <title>Complaints - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="complaints.css"
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


            <a
                href="complaints.php"
                class="active"
            >

                <span>💬</span>

                Complaints

            </a>


            <a href="profile.php">

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
                    Complaints
                </h1>

                <p>
                    Submit and manage your complaints.
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
             CONTENT
        ================================================== -->

        <div class="content">


            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

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


            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

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
                 PAGE GRID
            ================================================== -->

            <div class="page-grid">


                <!-- =================================================
                     SUBMIT COMPLAINT
                ================================================== -->

                <section class="card complaint-form-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Submit a Complaint
                            </h2>

                            <p>
                                Tell us about your issue or concern.
                            </p>

                        </div>


                        <div class="card-icon">
                            💬
                        </div>

                    </div>


                    <form
                        method="POST"
                        class="complaint-form"
                    >


                        <div class="form-group">

                            <label>
                                Subject
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="subject"
                                placeholder="Enter complaint subject"
                                maxlength="200"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Complaint Message
                                <span>*</span>
                            </label>

                            <textarea
                                name="message"
                                placeholder="Describe your complaint in detail..."
                                required
                            ></textarea>

                        </div>


                        <div class="form-note">

                            <span>
                                ℹ️
                            </span>

                            Please provide accurate information so
                            the concerned department can investigate
                            your complaint properly.

                        </div>


                        <button
                            type="submit"
                            name="submit_complaint"
                            class="submit-button"
                        >
                            Submit Complaint
                        </button>


                    </form>

                </section>


                <!-- =================================================
                     COMPLAINT INFORMATION
                ================================================== -->

                <section class="card information-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Complaint Process
                            </h2>

                            <p>
                                How your complaint is handled.
                            </p>

                        </div>


                        <div class="card-icon process-icon">
                            📋
                        </div>

                    </div>


                    <div class="process-list">


                        <div class="process-item">

                            <div class="process-number">
                                1
                            </div>

                            <div>

                                <strong>
                                    Submit Complaint
                                </strong>

                                <p>
                                    Submit your issue through
                                    the online complaint form.
                                </p>

                            </div>

                        </div>


                        <div class="process-item">

                            <div class="process-number">
                                2
                            </div>

                            <div>

                                <strong>
                                    Review
                                </strong>

                                <p>
                                    The concerned department reviews
                                    your complaint.
                                </p>

                            </div>

                        </div>


                        <div class="process-item">

                            <div class="process-number">
                                3
                            </div>

                            <div>

                                <strong>
                                    Investigation
                                </strong>

                                <p>
                                    Your complaint is investigated
                                    and appropriate action is taken.
                                </p>

                            </div>

                        </div>


                        <div class="process-item">

                            <div class="process-number">
                                4
                            </div>

                            <div>

                                <strong>
                                    Resolution
                                </strong>

                                <p>
                                    You receive a response after
                                    your complaint is resolved.
                                </p>

                            </div>

                        </div>


                    </div>

                </section>


            </div>


            <!-- =================================================
                 COMPLAINT HISTORY
            ================================================== -->

            <section class="card complaint-history">


                <div class="card-header">

                    <div>

                        <h2>
                            My Complaints
                        </h2>

                        <p>
                            View the status and response of your complaints.
                        </p>

                    </div>


                    <div class="card-icon">
                        📑
                    </div>

                </div>


                <div class="complaint-list">


                    <?php if ($complaints_result->num_rows > 0): ?>


                        <?php while (
                            $complaint =
                            $complaints_result->fetch_assoc()
                        ): ?>


                            <div class="complaint-item">


                                <div class="complaint-top">


                                    <div>

                                        <h3>

                                            <?php
                                            echo htmlspecialchars(
                                                $complaint["subject"]
                                            );
                                            ?>

                                        </h3>


                                        <span class="complaint-date">

                                            Submitted:
                                            <?php
                                            echo date(
                                                "d M Y, h:i A",
                                                strtotime(
                                                    $complaint["created_at"]
                                                )
                                            );
                                            ?>

                                        </span>

                                    </div>


                                    <?php

                                    $status_class =
                                        strtolower(
                                            str_replace(
                                                " ",
                                                "-",
                                                $complaint["status"]
                                            )
                                        );

                                    ?>


                                    <span
                                        class="status <?php echo $status_class; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $complaint["status"]
                                        );
                                        ?>

                                    </span>


                                </div>


                                <div class="complaint-message">

                                    <strong>
                                        Your Complaint
                                    </strong>

                                    <p>

                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $complaint["message"]
                                            )
                                        );
                                        ?>

                                    </p>

                                </div>


                                <?php if (
                                    !empty(
                                        $complaint["admin_reply"]
                                    )
                                ): ?>

                                    <div class="admin-response">

                                        <strong>
                                            Admin Response
                                        </strong>

                                        <p>

                                            <?php
                                            echo nl2br(
                                                htmlspecialchars(
                                                    $complaint["admin_reply"]
                                                )
                                            );
                                            ?>

                                        </p>

                                    </div>

                                <?php else: ?>

                                    <div class="waiting-response">

                                        ⏳

                                        Your complaint is waiting
                                        for a response from the
                                        concerned department.

                                    </div>

                                <?php endif; ?>


                            </div>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <div class="empty-state">

                            <div class="empty-icon">
                                💬
                            </div>

                            <h3>
                                No Complaints Yet
                            </h3>

                            <p>
                                You have not submitted any complaints.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>

            </section>


        </div>

    </main>

</div>


</body>

</html>