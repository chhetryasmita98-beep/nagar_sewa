<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$applications = [];

/* Get all applications of logged-in citizen */
$stmt = $conn->prepare("
    SELECT
        a.id,
        a.application_number,
        a.status,
        a.admin_remark,
        a.applied_at,
        a.updated_at,
        s.service_name
    FROM applications a
    INNER JOIN services s
        ON a.service_id = s.id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

$stmt->close();


/* Selected application */
$selected_application = null;
$selected_documents = [];
$selected_certificate = null;

if (isset($_GET["application_id"])) {

    $application_id = (int) $_GET["application_id"];

    /* Get selected application */
    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.application_number,
            a.status,
            a.admin_remark,
            a.applied_at,
            a.updated_at,
            a.application_data,
            s.service_name
        FROM applications a
        INNER JOIN services s
            ON a.service_id = s.id
        WHERE a.id = ?
        AND a.user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $application_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $selected_application = $result->fetch_assoc();

    $stmt->close();


    /* Get uploaded documents */
    if ($selected_application) {

        $stmt = $conn->prepare("
            SELECT
                id,
                document_name,
                file_path,
                uploaded_at
            FROM documents
            WHERE application_id = ?
            ORDER BY uploaded_at DESC
        ");

        $stmt->bind_param(
            "i",
            $application_id
        );

        $stmt->execute();

        $documents_result = $stmt->get_result();

        while ($row = $documents_result->fetch_assoc()) {
            $selected_documents[] = $row;
        }

        $stmt->close();


        /* Get certificate if available */
        $stmt = $conn->prepare("
            SELECT
                id,
                certificate_number,
                certificate_file,
                issued_at
            FROM certificates
            WHERE application_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $application_id
        );

        $stmt->execute();

        $certificate_result = $stmt->get_result();

        $selected_certificate =
            $certificate_result->fetch_assoc();

        $stmt->close();
    }
}


/* Status helper */
function getStatusClass($status)
{
    switch ($status) {

        case "Approved":
            return "approved";

        case "Rejected":
            return "rejected";

        case "Under Review":
            return "review";

        default:
            return "pending";
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

    <title>Track Application - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="track-application.css"
    >

</head>

<body>

<div class="page-wrapper">

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                🏛️
            </div>

            <div>
                <h2>Nagar Sewa</h2>
                <span>Citizen Portal</span>
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

            <a
                href="track-application.php"
                class="active"
            >
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


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">

        <!-- Header -->

        <header class="top-header">

            <div>

                <h1>Track Application</h1>

                <p>
                    Check the status of your government service applications.
                </p>

            </div>


            <div class="user-info">

                <div class="user-avatar">

                    <?php
                    echo strtoupper(
                        substr(
                            $_SESSION["user_name"],
                            0,
                            1
                        )
                    );
                    ?>

                </div>

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["user_name"]
                        );
                        ?>
                    </strong>

                    <small>
                        Citizen
                    </small>

                </div>

            </div>

        </header>


        <section class="content-area">


            <!-- =========================
                 SEARCH
            ========================== -->

            <div class="search-card">

                <div class="search-icon">
                    🔍
                </div>

                <div class="search-content">

                    <h2>
                        Your Applications
                    </h2>

                    <p>
                        Select an application below to view its details and status.
                    </p>

                </div>

            </div>


            <!-- =========================
                 APPLICATION LIST
            ========================== -->

            <div class="applications-card">

                <div class="card-header">

                    <h2>
                        Application History
                    </h2>

                    <span>
                        <?php echo count($applications); ?>
                        Application(s)
                    </span>

                </div>


                <?php if (count($applications) > 0): ?>

                    <div class="table-wrapper">

                        <table class="applications-table">

                            <thead>

                                <tr>

                                    <th>
                                        Application No.
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Applied Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($applications as $application): ?>

                                    <tr>

                                        <td>

                                            <strong class="application-number">

                                                <?php
                                                echo htmlspecialchars(
                                                    $application["application_number"]
                                                );
                                                ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $application["service_name"]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $application["applied_at"]
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <span
                                                class="status <?php echo getStatusClass($application["status"]); ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $application["status"]
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="track-application.php?application_id=<?php echo $application["id"]; ?>"
                                                class="view-button"
                                            >
                                                View Details
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            📂
                        </div>

                        <h3>
                            No Applications Found
                        </h3>

                        <p>
                            You have not submitted any applications yet.
                        </p>

                        <a
                            href="services.php"
                            class="primary-button"
                        >
                            Browse Services
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =========================
                 SELECTED APPLICATION
            ========================== -->

            <?php if ($selected_application): ?>

                <div class="details-card">

                    <div class="details-header">

                        <div>

                            <span>
                                Application Details
                            </span>

                            <h2>

                                <?php
                                echo htmlspecialchars(
                                    $selected_application["application_number"]
                                );
                                ?>

                            </h2>

                        </div>


                        <span
                            class="status large-status <?php echo getStatusClass($selected_application["status"]); ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $selected_application["status"]
                            );
                            ?>

                        </span>

                    </div>


                    <!-- =========================
                         STATUS TIMELINE
                    ========================== -->

                    <div class="timeline">

                        <div
                            class="timeline-item completed"
                        >

                            <div class="timeline-icon">
                                ✓
                            </div>

                            <div>

                                <h3>
                                    Application Submitted
                                </h3>

                                <p>
                                    Your application has been successfully submitted.
                                </p>

                                <small>

                                    <?php
                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $selected_application["applied_at"]
                                        )
                                    );
                                    ?>

                                </small>

                            </div>

                        </div>


                        <div
                            class="timeline-item
                            <?php
                            if (
                                $selected_application["status"] === "Under Review" ||
                                $selected_application["status"] === "Approved"
                            ) {
                                echo "completed";
                            } else {
                                echo "waiting";
                            }
                            ?>"
                        >

                            <div class="timeline-icon">
                                2
                            </div>

                            <div>

                                <h3>
                                    Under Review
                                </h3>

                                <p>
                                    Your application will be checked by the municipality.
                                </p>

                            </div>

                        </div>


                        <div
                            class="timeline-item
                            <?php
                            if (
                                $selected_application["status"] === "Approved"
                            ) {
                                echo "completed";
                            } elseif (
                                $selected_application["status"] === "Rejected"
                            ) {
                                echo "rejected-step";
                            } else {
                                echo "waiting";
                            }
                            ?>"
                        >

                            <div class="timeline-icon">

                                <?php
                                if (
                                    $selected_application["status"] === "Rejected"
                                ) {
                                    echo "!";
                                } elseif (
                                    $selected_application["status"] === "Approved"
                                ) {
                                    echo "✓";
                                } else {
                                    echo "3";
                                }
                                ?>

                            </div>

                            <div>

                                <h3>

                                    <?php
                                    if (
                                        $selected_application["status"] === "Rejected"
                                    ) {
                                        echo "Application Rejected";
                                    } else {
                                        echo "Application Decision";
                                    }
                                    ?>

                                </h3>

                                <p>

                                    <?php
                                    if (
                                        $selected_application["status"] === "Approved"
                                    ) {
                                        echo "Your application has been approved.";
                                    } elseif (
                                        $selected_application["status"] === "Rejected"
                                    ) {
                                        echo "Your application has been rejected.";
                                    } else {
                                        echo "Waiting for the final decision.";
                                    }
                                    ?>

                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- =========================
                         APPLICATION INFORMATION
                    ========================== -->

                    <div class="information-section">

                        <h3>
                            Application Information
                        </h3>


                        <div class="information-grid">

                            <div class="information-item">

                                <span>
                                    Service
                                </span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $selected_application["service_name"]
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="information-item">

                                <span>
                                    Application Number
                                </span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $selected_application["application_number"]
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="information-item">

                                <span>
                                    Applied Date
                                </span>

                                <strong>
                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $selected_application["applied_at"]
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="information-item">

                                <span>
                                    Last Updated
                                </span>

                                <strong>
                                    <?php
                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $selected_application["updated_at"]
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>

                    </div>


                    <!-- =========================
                         ADMIN REMARK
                    ========================== -->

                    <?php
                    if (
                        !empty(
                            $selected_application["admin_remark"]
                        )
                    ):
                    ?>

                        <div class="remark-section">

                            <h3>
                                Admin Remark
                            </h3>

                            <div class="remark-box">

                                <span class="remark-icon">
                                    💬
                                </span>

                                <p>

                                    <?php
                                    echo nl2br(
                                        htmlspecialchars(
                                            $selected_application["admin_remark"]
                                        )
                                    );
                                    ?>

                                </p>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- =========================
                         DOCUMENTS
                    ========================== -->

                    <div class="documents-section">

                        <h3>
                            Uploaded Documents
                        </h3>


                        <?php if (count($selected_documents) > 0): ?>

                            <div class="document-list">

                                <?php foreach ($selected_documents as $document): ?>

                                    <div class="document-item">

                                        <div class="document-left">

                                            <div class="document-icon">
                                                📄
                                            </div>

                                            <div>

                                                <strong>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $document["document_name"]
                                                    );
                                                    ?>

                                                </strong>

                                                <small>

                                                    Uploaded:

                                                    <?php
                                                    echo date(
                                                        "d M Y",
                                                        strtotime(
                                                            $document["uploaded_at"]
                                                        )
                                                    );
                                                    ?>

                                                </small>

                                            </div>

                                        </div>


                                        <a
                                            href="../<?php echo htmlspecialchars($document["file_path"]); ?>"
                                            target="_blank"
                                            class="document-view"
                                        >
                                            View
                                        </a>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="no-documents">
                                No documents uploaded.
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- =========================
                         CERTIFICATE
                    ========================== -->

                    <?php if (
                        $selected_application["status"] === "Approved"
                        && $selected_certificate
                    ): ?>

                        <div class="certificate-section">

                            <div class="certificate-icon">
                                🏆
                            </div>

                            <div class="certificate-content">

                                <h3>
                                    Certificate Available
                                </h3>

                                <p>
                                    Your application has been approved.
                                    You can download your certificate.
                                </p>

                                <strong>
                                    Certificate No:
                                    <?php
                                    echo htmlspecialchars(
                                        $selected_certificate["certificate_number"]
                                    );
                                    ?>
                                </strong>

                            </div>


                            <?php if (
                                !empty(
                                    $selected_certificate["certificate_file"]
                                )
                            ): ?>

                                <a
                                    href="../<?php echo htmlspecialchars($selected_certificate["certificate_file"]); ?>"
                                    target="_blank"
                                    class="download-button"
                                >
                                    Download Certificate
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php elseif (
                        $selected_application["status"] === "Approved"
                    ): ?>

                        <div class="approved-message">

                            <span>
                                ✓
                            </span>

                            <div>

                                <strong>
                                    Application Approved
                                </strong>

                                <p>
                                    Your application has been approved.
                                    The certificate will be available once issued.
                                </p>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>

            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>