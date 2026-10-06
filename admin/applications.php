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
   UPDATE APPLICATION STATUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_application"])) {

    $application_id = intval($_POST["application_id"] ?? 0);
    $status = trim($_POST["status"] ?? "");
    $admin_remark = trim($_POST["admin_remark"] ?? "");

    $allowed_status = [
        "Pending",
        "Under Review",
        "Approved",
        "Rejected"
    ];

    if ($application_id <= 0) {

        $error_message = "Invalid application.";

    } elseif (!in_array($status, $allowed_status, true)) {

        $error_message = "Invalid application status.";

    } else {

        $stmt = $conn->prepare("
            UPDATE applications
            SET status = ?, admin_remark = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssi",
            $status,
            $admin_remark,
            $application_id
        );

        if ($stmt->execute()) {
            $success_message = "Application updated successfully.";
        } else {
            $error_message = "Unable to update application.";
        }

        $stmt->close();
    }
}


/* =========================
   DELETE APPLICATION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_application"])) {

    $application_id = intval($_POST["application_id"] ?? 0);

    if ($application_id <= 0) {

        $error_message = "Invalid application.";

    } else {

        /*
         * Documents and certificates have foreign keys
         * with ON DELETE CASCADE.
         */
        $stmt = $conn->prepare("
            DELETE FROM applications
            WHERE id = ?
        ");

        $stmt->bind_param("i", $application_id);

        if ($stmt->execute()) {
            $success_message = "Application deleted successfully.";
        } else {
            $error_message = "Unable to delete application.";
        }

        $stmt->close();
    }
}


/* =========================
   SEARCH & FILTER
========================= */

$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");


/* =========================
   STATISTICS
========================= */

$total_applications = 0;
$pending_applications = 0;
$review_applications = 0;
$approved_applications = 0;
$rejected_applications = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applications
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_applications = (int)$row["total"];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'Pending'
");

if ($result) {
    $row = $result->fetch_assoc();
    $pending_applications = (int)$row["total"];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'Under Review'
");

if ($result) {
    $row = $result->fetch_assoc();
    $review_applications = (int)$row["total"];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'Approved'
");

if ($result) {
    $row = $result->fetch_assoc();
    $approved_applications = (int)$row["total"];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'Rejected'
");

if ($result) {
    $row = $result->fetch_assoc();
    $rejected_applications = (int)$row["total"];
}


/* =========================
   APPLICATION LIST
========================= */

$applications = null;

if ($search !== "" && $status_filter !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.application_number,
            a.application_data,
            a.status,
            a.admin_remark,
            a.applied_at,
            a.updated_at,
            u.full_name,
            u.email,
            u.phone,
            u.address,
            s.service_name
        FROM applications a
        INNER JOIN users u
            ON a.user_id = u.id
        INNER JOIN services s
            ON a.service_id = s.id
        WHERE
            (
                a.application_number LIKE ?
                OR u.full_name LIKE ?
                OR u.email LIKE ?
                OR u.phone LIKE ?
                OR s.service_name LIKE ?
            )
            AND a.status = ?
        ORDER BY a.applied_at DESC
    ");

    $stmt->bind_param(
        "ssssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $status_filter
    );

    $stmt->execute();

    $applications = $stmt->get_result();

    $stmt->close();

} elseif ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.application_number,
            a.application_data,
            a.status,
            a.admin_remark,
            a.applied_at,
            a.updated_at,
            u.full_name,
            u.email,
            u.phone,
            u.address,
            s.service_name
        FROM applications a
        INNER JOIN users u
            ON a.user_id = u.id
        INNER JOIN services s
            ON a.service_id = s.id
        WHERE
            a.application_number LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR s.service_name LIKE ?
        ORDER BY a.applied_at DESC
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

    $applications = $stmt->get_result();

    $stmt->close();

} elseif ($status_filter !== "") {

    $stmt = $conn->prepare("
        SELECT
            a.id,
            a.application_number,
            a.application_data,
            a.status,
            a.admin_remark,
            a.applied_at,
            a.updated_at,
            u.full_name,
            u.email,
            u.phone,
            u.address,
            s.service_name
        FROM applications a
        INNER JOIN users u
            ON a.user_id = u.id
        INNER JOIN services s
            ON a.service_id = s.id
        WHERE a.status = ?
        ORDER BY a.applied_at DESC
    ");

    $stmt->bind_param(
        "s",
        $status_filter
    );

    $stmt->execute();

    $applications = $stmt->get_result();

    $stmt->close();

} else {

    $applications = $conn->query("
        SELECT
            a.id,
            a.application_number,
            a.application_data,
            a.status,
            a.admin_remark,
            a.applied_at,
            a.updated_at,
            u.full_name,
            u.email,
            u.phone,
            u.address,
            s.service_name
        FROM applications a
        INNER JOIN users u
            ON a.user_id = u.id
        INNER JOIN services s
            ON a.service_id = s.id
        ORDER BY a.applied_at DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Applications - Nagar Sewa Admin</title>

    <link rel="stylesheet" href="applications.css">

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

            <a href="applications.php" class="active">
                <span>📝</span>
                Applications
            </a>

            <a href="documents.php">
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
                <h1>Applications</h1>

                <p>
                    Manage and review citizen service applications.
                </p>
            </div>

            <div class="admin-info">

                <div class="admin-avatar">
                    <?php
                    echo strtoupper(
                        substr($_SESSION["admin_name"] ?? "A", 0, 1)
                    );
                    ?>
                </div>

                <div>
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["admin_name"] ?? "Administrator"
                        );
                        ?>
                    </strong>

                    <small>Administrator</small>
                </div>

            </div>

        </header>


        <section class="content">


            <!-- ALERTS -->

            <?php if ($success_message): ?>

                <div class="alert success-alert">
                    <span>✓</span>

                    <?php
                    echo htmlspecialchars($success_message);
                    ?>
                </div>

            <?php endif; ?>


            <?php if ($error_message): ?>

                <div class="alert error-alert">
                    <span>!</span>

                    <?php
                    echo htmlspecialchars($error_message);
                    ?>
                </div>

            <?php endif; ?>


            <!-- STATISTICS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        📝
                    </div>

                    <div>
                        <span>Total Applications</span>
                        <strong>
                            <?php echo $total_applications; ?>
                        </strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        ⏳
                    </div>

                    <div>
                        <span>Pending</span>
                        <strong>
                            <?php echo $pending_applications; ?>
                        </strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        🔎
                    </div>

                    <div>
                        <span>Under Review</span>
                        <strong>
                            <?php echo $review_applications; ?>
                        </strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <div>
                        <span>Approved</span>
                        <strong>
                            <?php echo $approved_applications; ?>
                        </strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon red">
                        ✕
                    </div>

                    <div>
                        <span>Rejected</span>
                        <strong>
                            <?php echo $rejected_applications; ?>
                        </strong>
                    </div>

                </div>

            </div>


            <!-- APPLICATION MANAGEMENT -->

            <div class="applications-card">

                <div class="card-header">

                    <div>
                        <h2>All Applications</h2>

                        <p>
                            Search and manage citizen applications.
                        </p>
                    </div>

                </div>


                <!-- SEARCH -->

                <form method="GET" class="filter-form">

                    <div class="search-box">

                        <span>🔍</span>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search application, citizen, email or service..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>


                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <option value="Pending"
                            <?php echo $status_filter === "Pending" ? "selected" : ""; ?>>
                            Pending
                        </option>

                        <option value="Under Review"
                            <?php echo $status_filter === "Under Review" ? "selected" : ""; ?>>
                            Under Review
                        </option>

                        <option value="Approved"
                            <?php echo $status_filter === "Approved" ? "selected" : ""; ?>>
                            Approved
                        </option>

                        <option value="Rejected"
                            <?php echo $status_filter === "Rejected" ? "selected" : ""; ?>>
                            Rejected
                        </option>

                    </select>


                    <button type="submit" class="filter-btn">
                        Search
                    </button>


                    <?php if ($search !== "" || $status_filter !== ""): ?>

                        <a href="applications.php" class="clear-btn">
                            Clear
                        </a>

                    <?php endif; ?>

                </form>


                <!-- TABLE -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Application</th>

                                <th>Citizen</th>

                                <th>Service</th>

                                <th>Applied Date</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($applications && $applications->num_rows > 0): ?>

                            <?php while ($application = $applications->fetch_assoc()): ?>

                                <tr>

                                    <!-- APPLICATION -->

                                    <td>

                                        <div class="application-number">

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $application["application_number"]
                                                );
                                                ?>
                                            </strong>

                                            <small>
                                                ID:
                                                <?php
                                                echo (int)$application["id"];
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
                                                        $application["full_name"],
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
                                                        $application["full_name"]
                                                    );
                                                    ?>
                                                </strong>

                                                <small>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $application["email"]
                                                    );
                                                    ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- SERVICE -->

                                    <td>

                                        <div class="service-name">

                                            <span>📋</span>

                                            <?php
                                            echo htmlspecialchars(
                                                $application["service_name"]
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $application["applied_at"]
                                                )
                                            );
                                            ?>
                                        </strong>

                                        <small class="date-time">

                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $application["applied_at"]
                                                )
                                            );
                                            ?>

                                        </small>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status_class = "";

                                        switch ($application["status"]) {

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

                                        }

                                        ?>

                                        <span class="status <?php echo $status_class; ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                $application["status"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div class="action-buttons">

                                            <button
                                                type="button"
                                                class="view-btn"
                                                onclick='viewApplication(
                                                    <?php
                                                    echo json_encode(
                                                        [
                                                            "application_number" => $application["application_number"],
                                                            "full_name" => $application["full_name"],
                                                            "email" => $application["email"],
                                                            "phone" => $application["phone"],
                                                            "address" => $application["address"],
                                                            "service_name" => $application["service_name"],
                                                            "application_data" => $application["application_data"],
                                                            "status" => $application["status"],
                                                            "admin_remark" => $application["admin_remark"],
                                                            "applied_at" => $application["applied_at"],
                                                            "updated_at" => $application["updated_at"]
                                                        ],
                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_AMP |
                                                        JSON_HEX_QUOT
                                                    );
                                                    ?>
                                                )'>
                                                View
                                            </button>


                                            <button
                                                type="button"
                                                class="edit-btn"
                                                onclick='openEditModal(
                                                    <?php
                                                    echo json_encode(
                                                        [
                                                            "id" => $application["id"],
                                                            "application_number" => $application["application_number"],
                                                            "status" => $application["status"],
                                                            "admin_remark" => $application["admin_remark"]
                                                        ],
                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_AMP |
                                                        JSON_HEX_QUOT
                                                    );
                                                    ?>
                                                )'>
                                                Update
                                            </button>


                                            <form
                                                method="POST"
                                                class="delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this application?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="application_id"
                                                    value="<?php echo (int)$application["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_application"
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

                                <td colspan="6" class="empty-state">

                                    <div class="empty-icon">
                                        📭
                                    </div>

                                    <h3>No applications found</h3>

                                    <p>
                                        There are no applications matching your search.
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


<!-- =========================
     VIEW APPLICATION MODAL
========================== -->

<div class="modal-overlay" id="viewModal">

    <div class="modal large-modal">

        <div class="modal-header">

            <div>
                <h2>Application Details</h2>
                <p>View complete application information.</p>
            </div>

            <button
                type="button"
                class="close-btn"
                onclick="closeViewModal()">
                ×
            </button>

        </div>


        <div class="modal-body">

            <div class="details-grid">

                <div class="detail-item">

                    <span>Application Number</span>

                    <strong id="view_application_number">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Service</span>

                    <strong id="view_service">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Citizen Name</span>

                    <strong id="view_name">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Email</span>

                    <strong id="view_email">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Phone</span>

                    <strong id="view_phone">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Address</span>

                    <strong id="view_address">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Status</span>

                    <strong id="view_status">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>Applied Date</span>

                    <strong id="view_applied">
                        -
                    </strong>

                </div>

            </div>


            <div class="data-section">

                <h3>Application Data</h3>

                <div
                    class="application-data"
                    id="view_application_data">
                    No application data available.
                </div>

            </div>


            <div class="data-section">

                <h3>Admin Remark</h3>

                <div
                    class="application-data"
                    id="view_admin_remark">
                    No remark added.
                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     EDIT APPLICATION MODAL
========================== -->

<div class="modal-overlay" id="editModal">

    <div class="modal">

        <div class="modal-header">

            <div>

                <h2>Update Application</h2>

                <p id="editApplicationNumber">
                    Application
                </p>

            </div>

            <button
                type="button"
                class="close-btn"
                onclick="closeEditModal()">
                ×
            </button>

        </div>


        <form method="POST">

            <div class="modal-body">

                <input
                    type="hidden"
                    name="application_id"
                    id="edit_application_id"
                >


                <div class="form-group">

                    <label for="status">
                        Application Status
                    </label>

                    <select
                        name="status"
                        id="edit_status"
                        required
                    >

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Under Review">
                            Under Review
                        </option>

                        <option value="Approved">
                            Approved
                        </option>

                        <option value="Rejected">
                            Rejected
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="admin_remark">
                        Admin Remark
                    </label>

                    <textarea
                        name="admin_remark"
                        id="edit_admin_remark"
                        rows="5"
                        placeholder="Enter remark for the citizen..."
                    ></textarea>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeEditModal()">
                    Cancel
                </button>

                <button
                    type="submit"
                    name="update_application"
                    class="save-btn">
                    Update Application
                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================
   VIEW APPLICATION
========================= */

function viewApplication(application) {

    document.getElementById("view_application_number").textContent =
        application.application_number || "-";

    document.getElementById("view_service").textContent =
        application.service_name || "-";

    document.getElementById("view_name").textContent =
        application.full_name || "-";

    document.getElementById("view_email").textContent =
        application.email || "-";

    document.getElementById("view_phone").textContent =
        application.phone || "-";

    document.getElementById("view_address").textContent =
        application.address || "-";

    document.getElementById("view_status").textContent =
        application.status || "-";

    document.getElementById("view_applied").textContent =
        application.applied_at || "-";


    let applicationData = application.application_data;

    if (applicationData) {

        try {

            const parsedData = JSON.parse(applicationData);

            if (
                typeof parsedData === "object" &&
                parsedData !== null
            ) {

                let html = "<div class='data-list'>";

                Object.keys(parsedData).forEach(function(key) {

                    html += `
                        <div class="data-row">
                            <span>${escapeHtml(formatKey(key))}</span>
                            <strong>${escapeHtml(String(parsedData[key]))}</strong>
                        </div>
                    `;

                });

                html += "</div>";

                document.getElementById(
                    "view_application_data"
                ).innerHTML = html;

            } else {

                document.getElementById(
                    "view_application_data"
                ).textContent = applicationData;

            }

        } catch (error) {

            document.getElementById(
                "view_application_data"
            ).textContent = applicationData;

        }

    } else {

        document.getElementById(
            "view_application_data"
        ).textContent = "No application data available.";

    }


    document.getElementById(
        "view_admin_remark"
    ).textContent =
        application.admin_remark || "No remark added.";


    document.getElementById("viewModal").classList.add("show");

}


/* =========================
   EDIT MODAL
========================= */

function openEditModal(application) {

    document.getElementById(
        "edit_application_id"
    ).value = application.id;

    document.getElementById(
        "edit_status"
    ).value = application.status;

    document.getElementById(
        "edit_admin_remark"
    ).value = application.admin_remark || "";

    document.getElementById(
        "editApplicationNumber"
    ).textContent =
        application.application_number;

    document.getElementById(
        "editModal"
    ).classList.add("show");

}


/* =========================
   CLOSE MODALS
========================= */

function closeViewModal() {

    document.getElementById(
        "viewModal"
    ).classList.remove("show");

}


function closeEditModal() {

    document.getElementById(
        "editModal"
    ).classList.remove("show");

}


/* =========================
   ESCAPE KEY
========================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeViewModal();

            closeEditModal();

        }

    }
);


/* =========================
   CLICK OUTSIDE MODAL
========================= */

document.querySelectorAll(".modal-overlay").forEach(
    function(overlay) {

        overlay.addEventListener(
            "click",
            function(event) {

                if (event.target === overlay) {

                    overlay.classList.remove("show");

                }

            }
        );

    }
);


/* =========================
   HELPER FUNCTIONS
========================= */

function formatKey(key) {

    return key
        .replace(/_/g, " ")
        .replace(/\b\w/g, function(letter) {
            return letter.toUpperCase();
        });

}


function escapeHtml(value) {

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}

</script>

</body>

</html>