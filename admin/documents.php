<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$success_message = "";
$error_message = "";


/* =========================
   DELETE DOCUMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_document"])) {

    $document_id = intval($_POST["document_id"] ?? 0);

    if ($document_id <= 0) {

        $error_message = "Invalid document.";

    } else {

        // Get file path before deleting
        $stmt = $conn->prepare("
            SELECT file_path
            FROM documents
            WHERE id = ?
        ");

        $stmt->bind_param("i", $document_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $document = $result->fetch_assoc();

        $stmt->close();

        if (!$document) {

            $error_message = "Document not found.";

        } else {

            $stmt = $conn->prepare("
                DELETE FROM documents
                WHERE id = ?
            ");

            $stmt->bind_param("i", $document_id);

            if ($stmt->execute()) {

                // Delete physical file
                $file_path = "../" . $document["file_path"];

                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                $success_message = "Document deleted successfully.";

            } else {

                $error_message = "Unable to delete document.";

            }

            $stmt->close();
        }
    }
}


/* =========================
   SEARCH
========================= */

$search = trim($_GET["search"] ?? "");


/* =========================
   STATISTICS
========================= */

$total_documents = 0;
$today_documents = 0;
$this_month_documents = 0;
$total_applications_with_documents = 0;


/* Total Documents */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM documents
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_documents = (int)$row["total"];
}


/* Today's Documents */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM documents
    WHERE DATE(uploaded_at) = CURDATE()
");

if ($result) {

    $row = $result->fetch_assoc();

    $today_documents = (int)$row["total"];
}


/* This Month */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM documents
    WHERE
        MONTH(uploaded_at) = MONTH(CURDATE())
        AND YEAR(uploaded_at) = YEAR(CURDATE())
");

if ($result) {

    $row = $result->fetch_assoc();

    $this_month_documents = (int)$row["total"];
}


/* Applications With Documents */

$result = $conn->query("
    SELECT COUNT(DISTINCT application_id) AS total
    FROM documents
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_applications_with_documents = (int)$row["total"];
}


/* =========================
   DOCUMENT LIST
========================= */

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            d.id,
            d.document_name,
            d.file_path,
            d.uploaded_at,
            a.id AS application_id,
            a.application_number,
            a.status AS application_status,
            u.full_name,
            u.email,
            s.service_name
        FROM documents d

        INNER JOIN applications a
            ON d.application_id = a.id

        INNER JOIN users u
            ON a.user_id = u.id

        INNER JOIN services s
            ON a.service_id = s.id

        WHERE
            d.document_name LIKE ?
            OR a.application_number LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR s.service_name LIKE ?

        ORDER BY d.uploaded_at DESC
    ");

    $stmt->bind_param(
        "sssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $documents = $stmt->get_result();

    $stmt->close();

} else {

    $documents = $conn->query("
        SELECT
            d.id,
            d.document_name,
            d.file_path,
            d.uploaded_at,
            a.id AS application_id,
            a.application_number,
            a.status AS application_status,
            u.full_name,
            u.email,
            s.service_name
        FROM documents d

        INNER JOIN applications a
            ON d.application_id = a.id

        INNER JOIN users u
            ON a.user_id = u.id

        INNER JOIN services s
            ON a.service_id = s.id

        ORDER BY d.uploaded_at DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documents - Nagar Sewa Admin</title>

    <link rel="stylesheet" href="documents.css">

</head>

<body>

<div class="admin-wrapper">


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
                <span>Admin Panel</span>
            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php">
                <span>📊</span>
                Dashboard
            </a>

            <a href="users.php">
                <span>👥</span>
                Users
            </a>

            <a href="services.php">
                <span>📋</span>
                Services
            </a>

            <a href="applications.php">
                <span>📝</span>
                Applications
            </a>

            <a href="documents.php" class="active">
                <span>📎</span>
                Documents
            </a>

            <a href="certificates.php">
                <span>📜</span>
                Certificates
            </a>

            <a href="complaints.php">
                <span>💬</span>
                Complaints
            </a>

            <a href="notices.php">
                <span>📢</span>
                Notices
            </a>

            <a href="reports.php">
                <span>📈</span>
                Reports
            </a>

            <a href="logout.php" class="logout-link">
                <span>🚪</span>
                Logout
            </a>

        </nav>

    </aside>


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">

            <div>

                <h1>Documents</h1>

                <p>
                    Manage documents submitted by citizens.
                </p>

            </div>


            <div class="admin-info">

                <div class="admin-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $_SESSION["admin_name"] ?? "A",
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
                            $_SESSION["admin_name"] ??
                            "Administrator"
                        );

                        ?>

                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>

        </header>


        <section class="content">


            <!-- =========================
                 ALERTS
            ========================== -->

            <?php if ($success_message): ?>

                <div class="alert success-alert">

                    <span>✓</span>

                    <?php

                    echo htmlspecialchars(
                        $success_message
                    );

                    ?>

                </div>

            <?php endif; ?>


            <?php if ($error_message): ?>

                <div class="alert error-alert">

                    <span>!</span>

                    <?php

                    echo htmlspecialchars(
                        $error_message
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- =========================
                 STATISTICS
            ========================== -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        📎
                    </div>

                    <div>

                        <span>
                            Total Documents
                        </span>

                        <strong>
                            <?php echo $total_documents; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        📅
                    </div>

                    <div>

                        <span>
                            Uploaded Today
                        </span>

                        <strong>
                            <?php echo $today_documents; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        📈
                    </div>

                    <div>

                        <span>
                            This Month
                        </span>

                        <strong>
                            <?php echo $this_month_documents; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        📝
                    </div>

                    <div>

                        <span>
                            Applications
                        </span>

                        <strong>
                            <?php
                            echo $total_applications_with_documents;
                            ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =========================
                 DOCUMENT CARD
            ========================== -->

            <div class="documents-card">


                <div class="card-header">

                    <div>

                        <h2>
                            Submitted Documents
                        </h2>

                        <p>
                            View and manage citizen uploaded documents.
                        </p>

                    </div>

                </div>


                <!-- =========================
                     SEARCH
                ========================== -->

                <form
                    method="GET"
                    class="search-form"
                >

                    <div class="search-box">

                        <span>🔍</span>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search document, application, citizen or service..."
                            value="<?php
                            echo htmlspecialchars($search);
                            ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        class="search-btn"
                    >
                        Search
                    </button>


                    <?php if ($search !== ""): ?>

                        <a
                            href="documents.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </form>


                <!-- =========================
                     TABLE
                ========================== -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Document
                                </th>

                                <th>
                                    Application
                                </th>

                                <th>
                                    Citizen
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Uploaded
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


                        <?php if ($documents && $documents->num_rows > 0): ?>


                            <?php while ($document = $documents->fetch_assoc()): ?>


                                <tr>


                                    <!-- DOCUMENT -->

                                    <td>

                                        <div class="document-info">

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

                                                    Document ID:
                                                    <?php
                                                    echo (int)$document["id"];
                                                    ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- APPLICATION -->

                                    <td>

                                        <div class="application-info">

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $document[
                                                        "application_number"
                                                    ]
                                                );

                                                ?>

                                            </strong>

                                            <small>

                                                ID:
                                                <?php
                                                echo (int)$document[
                                                    "application_id"
                                                ];
                                                ?>

                                            </small>

                                        </div>

                                    </td>


                                    <!-- CITIZEN -->

                                    <td>

                                        <div class="citizen-info">

                                            <div class="citizen-avatar">

                                                <?php

                                                echo strtoupper(
                                                    substr(
                                                        $document["full_name"],
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
                                                        $document["full_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <small>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $document["email"]
                                                    );

                                                    ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- SERVICE -->

                                    <td>

                                        <div class="service-name">

                                            <span>
                                                📋
                                            </span>

                                            <?php

                                            echo htmlspecialchars(
                                                $document["service_name"]
                                            );

                                            ?>

                                        </div>

                                    </td>


                                    <!-- UPLOAD DATE -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $document["uploaded_at"]
                                                )
                                            );

                                            ?>

                                        </strong>

                                        <small class="date-time">

                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $document["uploaded_at"]
                                                )
                                            );

                                            ?>

                                        </small>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status_class = "";

                                        switch (
                                            $document["application_status"]
                                        ) {

                                            case "Pending":
                                                $status_class = "pending";
                                                break;

                                            case "Under Review":
                                                $status_class = "review";
                                                break;

                                            case "Approved":
                                                $status_class = "approved";
                                                break;

                                            case "Rejected":
                                                $status_class = "rejected";
                                                break;

                                            default:
                                                $status_class = "pending";
                                        }

                                        ?>


                                        <span
                                            class="status <?php
                                            echo $status_class;
                                            ?>"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $document[
                                                    "application_status"
                                                ]
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div class="action-buttons">


                                            <?php

                                            $safe_file_path =
                                                "../" .
                                                ltrim(
                                                    $document["file_path"],
                                                    "/"
                                                );

                                            ?>


                                            <a
                                                href="<?php
                                                echo htmlspecialchars(
                                                    $safe_file_path
                                                );
                                                ?>"
                                                target="_blank"
                                                class="view-btn"
                                            >
                                                View
                                            </a>


                                            <a
                                                href="<?php
                                                echo htmlspecialchars(
                                                    $safe_file_path
                                                );
                                                ?>"
                                                download
                                                class="download-btn"
                                            >
                                                Download
                                            </a>


                                            <form
                                                method="POST"
                                                class="delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this document?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="document_id"
                                                    value="<?php
                                                    echo (int)$document["id"];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_document"
                                                    class="delete-btn"
                                                >
                                                    Delete
                                                </button>

                                            </form>


                                        </div>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-state"
                                >

                                    <div class="empty-icon">
                                        📭
                                    </div>

                                    <h3>
                                        No documents found
                                    </h3>

                                    <p>

                                        <?php if ($search !== ""): ?>

                                            No documents match your search.

                                        <?php else: ?>

                                            No documents have been uploaded yet.

                                        <?php endif; ?>

                                    </p>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


            </div>


        </section>

    </main>

</div>

</body>

</html>