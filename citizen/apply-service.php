<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$service_id = isset($_GET["service_id"])
    ? (int) $_GET["service_id"]
    : 0;


/* =========================================================
   CHECK SERVICE
========================================================= */

if ($service_id <= 0) {
    header("Location: services.php");
    exit;
}


/* =========================================================
   FETCH USER
========================================================= */

$stmt = $conn->prepare(
    "SELECT full_name, email, phone, address
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
   FETCH SERVICE
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        id,
        service_name,
        description,
        required_documents
     FROM services
     WHERE id = ?
     AND status = 'Active'"
);

$stmt->bind_param("i", $service_id);

$stmt->execute();

$service_result = $stmt->get_result();

$service = $service_result->fetch_assoc();

$stmt->close();


if (!$service) {
    header("Location: services.php");
    exit;
}


/* =========================================================
   FORM VARIABLES
========================================================= */

$error_message = "";

$full_name = $user["full_name"];
$email = $user["email"];
$phone = $user["phone"];
$address = $user["address"];


/* =========================================================
   SUBMIT APPLICATION
========================================================= */

if (isset($_POST["submit_application"])) {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    $additional_info = trim(
        $_POST["additional_info"] ?? ""
    );


    /* =====================================================
       VALIDATION
    ====================================================== */

    if (
        $full_name === "" ||
        $email === "" ||
        $phone === "" ||
        $address === ""
    ) {

        $error_message =
            "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_message =
            "Please enter a valid email address.";

    } elseif (strlen($phone) < 7) {

        $error_message =
            "Please enter a valid phone number.";

    } else {


        /* =================================================
           APPLICATION NUMBER
        ================================================== */

        do {

            $application_number =
                "NS-" .
                date("Ymd") .
                "-" .
                strtoupper(
                    substr(
                        uniqid(),
                        -6
                    )
                );


            $check = $conn->prepare(
                "SELECT id
                 FROM applications
                 WHERE application_number = ?"
            );

            $check->bind_param(
                "s",
                $application_number
            );

            $check->execute();

            $check_result =
                $check->get_result();

            $exists =
                $check_result->num_rows > 0;

            $check->close();

        } while ($exists);


        /* =================================================
           APPLICATION DATA
        ================================================== */

        $application_data = json_encode(
            [
                "full_name" => $full_name,
                "email" => $email,
                "phone" => $phone,
                "address" => $address,
                "additional_info" => $additional_info
            ],
            JSON_UNESCAPED_UNICODE
        );


        /* =================================================
           INSERT APPLICATION
        ================================================== */

        $stmt = $conn->prepare(
            "INSERT INTO applications
            (
                user_id,
                service_id,
                application_number,
                application_data,
                status
            )
            VALUES (?, ?, ?, ?, 'Pending')"
        );


        $stmt->bind_param(
            "iiss",
            $user_id,
            $service_id,
            $application_number,
            $application_data
        );


        if ($stmt->execute()) {

            $application_id =
                $stmt->insert_id;

            $stmt->close();


            /* =============================================
               REDIRECT TO DOCUMENT UPLOAD
            ============================================== */

            header(
                "Location: upload-document.php?application_id=" .
                $application_id
            );

            exit;

        } else {

            $error_message =
                "Unable to submit application. Please try again.";

            $stmt->close();
        }
    }
}


/* =========================================================
   REQUIRED DOCUMENTS
========================================================= */

$required_documents = [];

if (!empty($service["required_documents"])) {

    $required_documents =
        explode(
            ",",
            $service["required_documents"]
        );
}


/* =========================================================
   USER INITIAL
========================================================= */

$first_letter = strtoupper(
    substr(
        $user["full_name"],
        0,
        1
    )
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

    <title>
        Apply for Service - Nagar Sewa
    </title>

    <link
        rel="stylesheet"
        href="apply-service.css"
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


            <a
                href="services.php"
                class="active"
            >

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
                    Apply for Service
                </h1>

                <p>
                    Complete the application form carefully.
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
                 BACK BUTTON
            ================================================== -->

            <a
                href="services.php"
                class="back-link"
            >
                ← Back to Government Services
            </a>


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
                 SERVICE INFORMATION
            ================================================== -->

            <div class="service-banner">


                <div class="service-banner-icon">
                    📋
                </div>


                <div>

                    <span>
                        Applying for
                    </span>

                    <h2>

                        <?php
                        echo htmlspecialchars(
                            $service["service_name"]
                        );
                        ?>

                    </h2>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $service["description"]
                        );
                        ?>

                    </p>

                </div>


            </div>


            <!-- =================================================
                 MAIN GRID
            ================================================== -->

            <div class="application-grid">


                <!-- =================================================
                     APPLICATION FORM
                ================================================== -->

                <section class="card form-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Application Details
                            </h2>

                            <p>
                                Enter your personal information.
                            </p>

                        </div>

                        <div class="card-icon">
                            📝
                        </div>

                    </div>


                    <form
                        method="POST"
                        class="application-form"
                    >


                        <!-- FULL NAME -->

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
                                    $full_name
                                );
                                ?>"
                                placeholder="Enter your full name"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label>
                                Email Address
                                <span>*</span>
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?php
                                echo htmlspecialchars(
                                    $email
                                );
                                ?>"
                                placeholder="Enter your email"
                                required
                            >

                        </div>


                        <!-- PHONE -->

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
                                    $phone
                                );
                                ?>"
                                placeholder="Enter your phone number"
                                required
                            >

                        </div>


                        <!-- ADDRESS -->

                        <div class="form-group">

                            <label>
                                Address
                                <span>*</span>
                            </label>

                            <textarea
                                name="address"
                                placeholder="Enter your current address"
                                required
                            ><?php
                            echo htmlspecialchars(
                                $address
                            );
                            ?></textarea>

                        </div>


                        <!-- ADDITIONAL INFORMATION -->

                        <div class="form-group">

                            <label>
                                Additional Information
                            </label>

                            <textarea
                                name="additional_info"
                                placeholder="Enter any additional information related to your application..."
                            ></textarea>

                        </div>


                        <!-- NOTICE -->

                        <div class="form-notice">

                            <span>
                                ℹ️
                            </span>

                            <div>

                                <strong>
                                    Important
                                </strong>

                                <p>
                                    Please verify all the information
                                    before submitting your application.
                                    Incorrect information may delay
                                    the approval process.
                                </p>

                            </div>

                        </div>


                        <!-- BUTTONS -->

                        <div class="form-buttons">

                            <a
                                href="services.php"
                                class="cancel-button"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                name="submit_application"
                                class="submit-button"
                            >

                                Submit Application

                                <span>
                                    →
                                </span>

                            </button>

                        </div>


                    </form>

                </section>


                <!-- =================================================
                     SERVICE DETAILS
                ================================================== -->

                <aside class="right-column">


                    <!-- REQUIRED DOCUMENTS -->

                    <section class="card documents-card">


                        <div class="card-header">

                            <div>

                                <h2>
                                    Required Documents
                                </h2>

                                <p>
                                    Prepare these documents.
                                </p>

                            </div>

                            <div class="card-icon document-icon">
                                📎
                            </div>

                        </div>


                        <div class="documents-list">


                            <?php if (
                                count($required_documents) > 0
                            ): ?>


                                <?php foreach (
                                    $required_documents
                                    as $index => $document
                                ): ?>


                                    <?php

                                    $document =
                                        trim($document);

                                    if (
                                        $document === ""
                                    ) {
                                        continue;
                                    }

                                    ?>


                                    <div class="document-item">


                                        <div class="document-number">

                                            <?php
                                            echo $index + 1;
                                            ?>

                                        </div>


                                        <div>

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $document
                                                );
                                                ?>

                                            </strong>

                                            <span>
                                                Required
                                            </span>

                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <div class="no-documents">

                                    No specific documents
                                    are listed for this service.

                                </div>


                            <?php endif; ?>


                        </div>


                    </section>


                    <!-- APPLICATION PROCESS -->

                    <section class="card process-card">


                        <div class="card-header">

                            <div>

                                <h2>
                                    Application Process
                                </h2>

                                <p>
                                    What happens next?
                                </p>

                            </div>

                            <div class="card-icon process-icon">
                                🔄
                            </div>

                        </div>


                        <div class="process-list">


                            <div class="process-item">

                                <div class="process-number">
                                    1
                                </div>

                                <div>

                                    <strong>
                                        Submit Application
                                    </strong>

                                    <p>
                                        Fill in your details and
                                        submit the application.
                                    </p>

                                </div>

                            </div>


                            <div class="process-item">

                                <div class="process-number">
                                    2
                                </div>

                                <div>

                                    <strong>
                                        Upload Documents
                                    </strong>

                                    <p>
                                        Upload the required supporting
                                        documents.
                                    </p>

                                </div>

                            </div>


                            <div class="process-item">

                                <div class="process-number">
                                    3
                                </div>

                                <div>

                                    <strong>
                                        Verification
                                    </strong>

                                    <p>
                                        The concerned department
                                        reviews your application.
                                    </p>

                                </div>

                            </div>


                            <div class="process-item">

                                <div class="process-number">
                                    4
                                </div>

                                <div>

                                    <strong>
                                        Approval
                                    </strong>

                                    <p>
                                        Approved applications can
                                        proceed to certificate issuance.
                                    </p>

                                </div>

                            </div>


                        </div>

                    </section>


                </aside>


            </div>


        </div>

    </main>

</div>


</body>

</html>