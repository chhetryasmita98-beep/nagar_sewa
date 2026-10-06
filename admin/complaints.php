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
   UPDATE COMPLAINT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_complaint"])) {

    $complaint_id = intval($_POST["complaint_id"] ?? 0);
    $status = trim($_POST["status"] ?? "");
    $admin_reply = trim($_POST["admin_reply"] ?? "");

    $allowed_status = [
        "Pending",
        "In Progress",
        "Resolved",
        "Rejected"
    ];

    if ($complaint_id <= 0) {

        $error_message = "Invalid complaint.";

    } elseif (!in_array($status, $allowed_status, true)) {

        $error_message = "Invalid complaint status.";

    } else {

        $stmt = $conn->prepare("
            UPDATE complaints
            SET status = ?, admin_reply = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssi",
            $status,
            $admin_reply,
            $complaint_id
        );

        if ($stmt->execute()) {

            $success_message = "Complaint updated successfully.";

        } else {

            $error_message = "Unable to update complaint.";

        }

        $stmt->close();
    }
}


/* =========================
   DELETE COMPLAINT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_complaint"])) {

    $complaint_id = intval($_POST["complaint_id"] ?? 0);

    if ($complaint_id <= 0) {

        $error_message = "Invalid complaint.";

    } else {

        $stmt = $conn->prepare("
            DELETE FROM complaints
            WHERE id = ?
        ");

        $stmt->bind_param("i", $complaint_id);

        if ($stmt->execute()) {

            $success_message = "Complaint deleted successfully.";

        } else {

            $error_message = "Unable to delete complaint.";

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

$total_complaints = 0;
$pending_complaints = 0;
$in_progress_complaints = 0;
$resolved_complaints = 0;
$rejected_complaints = 0;


/* Total */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_complaints = (int)$row["total"];
}


/* Pending */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
    WHERE status = 'Pending'
");

if ($result) {

    $row = $result->fetch_assoc();

    $pending_complaints = (int)$row["total"];
}


/* In Progress */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
    WHERE status = 'In Progress'
");

if ($result) {

    $row = $result->fetch_assoc();

    $in_progress_complaints = (int)$row["total"];
}


/* Resolved */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
    WHERE status = 'Resolved'
");

if ($result) {

    $row = $result->fetch_assoc();

    $resolved_complaints = (int)$row["total"];
}


/* Rejected */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
    WHERE status = 'Rejected'
");

if ($result) {

    $row = $result->fetch_assoc();

    $rejected_complaints = (int)$row["total"];
}


/* =========================
   FETCH COMPLAINTS
========================= */

if ($search !== "" && $status_filter !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.subject,
            c.message,
            c.status,
            c.admin_reply,
            c.created_at,
            u.full_name,
            u.email,
            u.phone
        FROM complaints c

        INNER JOIN users u
            ON c.user_id = u.id

        WHERE
            (
                c.subject LIKE ?
                OR c.message LIKE ?
                OR c.admin_reply LIKE ?
                OR u.full_name LIKE ?
                OR u.email LIKE ?
                OR u.phone LIKE ?
            )
            AND c.status = ?

        ORDER BY c.created_at DESC
    ");

    $stmt->bind_param(
        "sssssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $status_filter
    );

    $stmt->execute();

    $complaints = $stmt->get_result();

    $stmt->close();

} elseif ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.subject,
            c.message,
            c.status,
            c.admin_reply,
            c.created_at,
            u.full_name,
            u.email,
            u.phone
        FROM complaints c

        INNER JOIN users u
            ON c.user_id = u.id

        WHERE
            c.subject LIKE ?
            OR c.message LIKE ?
            OR c.admin_reply LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?

        ORDER BY c.created_at DESC
    ");

    $stmt->bind_param(
        "ssssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $complaints = $stmt->get_result();

    $stmt->close();

} elseif ($status_filter !== "") {

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.subject,
            c.message,
            c.status,
            c.admin_reply,
            c.created_at,
            u.full_name,
            u.email,
            u.phone
        FROM complaints c

        INNER JOIN users u
            ON c.user_id = u.id

        WHERE c.status = ?

        ORDER BY c.created_at DESC
    ");

    $stmt->bind_param(
        "s",
        $status_filter
    );

    $stmt->execute();

    $complaints = $stmt->get_result();

    $stmt->close();

} else {

    $complaints = $conn->query("
        SELECT
            c.id,
            c.subject,
            c.message,
            c.status,
            c.admin_reply,
            c.created_at,
            u.full_name,
            u.email,
            u.phone
        FROM complaints c

        INNER JOIN users u
            ON c.user_id = u.id

        ORDER BY c.created_at DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints - Nagar Sewa Admin</title>

    <link rel="stylesheet" href="complaints.css">

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

            <a href="documents.php">
                <span>📎</span>
                Documents
            </a>

            <a href="certificates.php">
                <span>📜</span>
                Certificates
            </a>

            <a href="complaints.php" class="active">
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

                <h1>Complaints</h1>

                <p>
                    Manage citizen complaints and inquiries.
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


            <!-- =========================
                 STATISTICS
            ========================== -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        💬
                    </div>

                    <div>

                        <span>
                            Total Complaints
                        </span>

                        <strong>
                            <?php echo $total_complaints; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        ⏳
                    </div>

                    <div>

                        <span>
                            Pending
                        </span>

                        <strong>
                            <?php echo $pending_complaints; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        🔄
                    </div>

                    <div>

                        <span>
                            In Progress
                        </span>

                        <strong>
                            <?php echo $in_progress_complaints; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <div>

                        <span>
                            Resolved
                        </span>

                        <strong>
                            <?php echo $resolved_complaints; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon red">
                        ✕
                    </div>

                    <div>

                        <span>
                            Rejected
                        </span>

                        <strong>
                            <?php echo $rejected_complaints; ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =========================
                 COMPLAINTS CARD
            ========================== -->

            <div class="complaints-card">


                <div class="card-header">

                    <div>

                        <h2>
                            Citizen Complaints
                        </h2>

                        <p>
                            Review complaints and send replies to citizens.
                        </p>

                    </div>

                </div>


                <!-- SEARCH AND FILTER -->

                <form
                    method="GET"
                    class="filter-form"
                >


                    <div class="search-box">

                        <span>🔍</span>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search subject, message, citizen or email..."
                            value="<?php
                            echo htmlspecialchars($search);
                            ?>"
                        >

                    </div>


                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Pending"
                            <?php
                            echo $status_filter === "Pending"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="In Progress"
                            <?php
                            echo $status_filter === "In Progress"
                                ? "selected"
                                : "";
                            ?>
                        >
                            In Progress
                        </option>

                        <option
                            value="Resolved"
                            <?php
                            echo $status_filter === "Resolved"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Resolved
                        </option>

                        <option
                            value="Rejected"
                            <?php
                            echo $status_filter === "Rejected"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Rejected
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="filter-btn"
                    >
                        Search
                    </button>


                    <?php if (
                        $search !== "" ||
                        $status_filter !== ""
                    ): ?>

                        <a
                            href="complaints.php"
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
                                    Complaint
                                </th>

                                <th>
                                    Citizen
                                </th>

                                <th>
                                    Message
                                </th>

                                <th>
                                    Date
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


                        <?php if (
                            $complaints &&
                            $complaints->num_rows > 0
                        ): ?>


                            <?php while (
                                $complaint =
                                $complaints->fetch_assoc()
                            ): ?>


                                <tr>


                                    <!-- COMPLAINT -->

                                    <td>

                                        <div class="complaint-info">

                                            <div class="complaint-icon">
                                                💬
                                            </div>

                                            <div>

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $complaint["subject"]
                                                    );

                                                    ?>

                                                </strong>

                                                <small>

                                                    Complaint ID:
                                                    <?php
                                                    echo (int)$complaint["id"];
                                                    ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- CITIZEN -->

                                    <td>

                                        <div class="citizen-info">

                                            <div class="citizen-avatar">

                                                <?php

                                                echo strtoupper(
                                                    substr(
                                                        $complaint[
                                                            "full_name"
                                                        ],
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
                                                        $complaint[
                                                            "full_name"
                                                        ]
                                                    );

                                                    ?>

                                                </strong>

                                                <small>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $complaint[
                                                            "email"
                                                        ]
                                                    );

                                                    ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- MESSAGE -->

                                    <td>

                                        <div class="message-preview">

                                            <?php

                                            $message =
                                                $complaint["message"];

                                            if (
                                                strlen($message) > 80
                                            ) {

                                                echo htmlspecialchars(
                                                    substr(
                                                        $message,
                                                        0,
                                                        80
                                                    )
                                                ) . "...";

                                            } else {

                                                echo htmlspecialchars(
                                                    $message
                                                );

                                            }

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
                                                    $complaint[
                                                        "created_at"
                                                    ]
                                                )
                                            );

                                            ?>

                                        </strong>

                                        <small class="date-time">

                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $complaint[
                                                        "created_at"
                                                    ]
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
                                            $complaint["status"]
                                        ) {

                                            case "Pending":
                                                $status_class = "pending";
                                                break;

                                            case "In Progress":
                                                $status_class = "progress";
                                                break;

                                            case "Resolved":
                                                $status_class = "resolved";
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
                                                $complaint["status"]
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
                                                onclick='viewComplaint(
                                                    <?php

                                                    echo json_encode(
                                                        [
                                                            "id" =>
                                                                $complaint["id"],

                                                            "subject" =>
                                                                $complaint["subject"],

                                                            "message" =>
                                                                $complaint["message"],

                                                            "status" =>
                                                                $complaint["status"],

                                                            "admin_reply" =>
                                                                $complaint["admin_reply"],

                                                            "created_at" =>
                                                                $complaint["created_at"],

                                                            "full_name" =>
                                                                $complaint["full_name"],

                                                            "email" =>
                                                                $complaint["email"],

                                                            "phone" =>
                                                                $complaint["phone"]
                                                        ],

                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_AMP |
                                                        JSON_HEX_QUOT
                                                    );

                                                    ?>
                                                )'
                                            >
                                                View
                                            </button>


                                            <button
                                                type="button"
                                                class="edit-btn"
                                                onclick='openEditModal(
                                                    <?php

                                                    echo json_encode(
                                                        [
                                                            "id" =>
                                                                $complaint["id"],

                                                            "subject" =>
                                                                $complaint["subject"],

                                                            "status" =>
                                                                $complaint["status"],

                                                            "admin_reply" =>
                                                                $complaint["admin_reply"]
                                                        ],

                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_AMP |
                                                        JSON_HEX_QUOT
                                                    );

                                                    ?>
                                                )'
                                            >
                                                Update
                                            </button>


                                            <form
                                                method="POST"
                                                class="delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this complaint?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="complaint_id"
                                                    value="<?php
                                                    echo (int)$complaint["id"];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_complaint"
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
                                    colspan="6"
                                    class="empty-state"
                                >

                                    <div class="empty-icon">
                                        💬
                                    </div>

                                    <h3>
                                        No complaints found
                                    </h3>

                                    <p>

                                        <?php if ($search !== ""): ?>

                                            No complaints match your search.

                                        <?php else: ?>

                                            No complaints have been submitted yet.

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


<!-- =========================
     VIEW COMPLAINT MODAL
========================= -->

<div
    class="modal-overlay"
    id="viewModal"
>

    <div class="modal large-modal">


        <div class="modal-header">

            <div>

                <h2>
                    Complaint Details
                </h2>

                <p>
                    View complete complaint information.
                </p>

            </div>


            <button
                type="button"
                class="close-btn"
                onclick="closeViewModal()"
            >
                ×
            </button>

        </div>


        <div class="modal-body">


            <div class="details-grid">


                <div class="detail-item">

                    <span>
                        Complaint ID
                    </span>

                    <strong id="view_id">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Subject
                    </span>

                    <strong id="view_subject">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Citizen Name
                    </span>

                    <strong id="view_name">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Email
                    </span>

                    <strong id="view_email">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Phone
                    </span>

                    <strong id="view_phone">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Status
                    </span>

                    <strong id="view_status">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Submitted
                    </span>

                    <strong id="view_date">
                        -
                    </strong>

                </div>


            </div>


            <div class="data-section">

                <h3>
                    Complaint Message
                </h3>

                <div
                    class="complaint-message"
                    id="view_message"
                >
                    -
                </div>

            </div>


            <div class="data-section">

                <h3>
                    Admin Reply
                </h3>

                <div
                    class="complaint-message"
                    id="view_reply"
                >
                    No reply added.
                </div>

            </div>


        </div>

    </div>

</div>


<!-- =========================
     UPDATE COMPLAINT MODAL
========================= -->

<div
    class="modal-overlay"
    id="editModal"
>

    <div class="modal">


        <div class="modal-header">

            <div>

                <h2>
                    Update Complaint
                </h2>

                <p id="editComplaintSubject">
                    Complaint
                </p>

            </div>


            <button
                type="button"
                class="close-btn"
                onclick="closeEditModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">


            <div class="modal-body">


                <input
                    type="hidden"
                    name="complaint_id"
                    id="edit_complaint_id"
                >


                <div class="form-group">

                    <label>
                        Complaint Status
                    </label>

                    <select
                        name="status"
                        id="edit_status"
                        required
                    >

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="In Progress">
                            In Progress
                        </option>

                        <option value="Resolved">
                            Resolved
                        </option>

                        <option value="Rejected">
                            Rejected
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Admin Reply
                    </label>

                    <textarea
                        name="admin_reply"
                        id="edit_admin_reply"
                        rows="6"
                        placeholder="Write a reply to the citizen..."
                    ></textarea>

                </div>


            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    name="update_complaint"
                    class="save-btn"
                >
                    Update Complaint
                </button>

            </div>


        </form>

    </div>

</div>


<script>

/* =========================
   VIEW COMPLAINT
========================= */

function viewComplaint(complaint) {

    document.getElementById(
        "view_id"
    ).textContent =
        complaint.id || "-";


    document.getElementById(
        "view_subject"
    ).textContent =
        complaint.subject || "-";


    document.getElementById(
        "view_name"
    ).textContent =
        complaint.full_name || "-";


    document.getElementById(
        "view_email"
    ).textContent =
        complaint.email || "-";


    document.getElementById(
        "view_phone"
    ).textContent =
        complaint.phone || "-";


    document.getElementById(
        "view_status"
    ).textContent =
        complaint.status || "-";


    document.getElementById(
        "view_date"
    ).textContent =
        complaint.created_at || "-";


    document.getElementById(
        "view_message"
    ).textContent =
        complaint.message || "-";


    document.getElementById(
        "view_reply"
    ).textContent =
        complaint.admin_reply ||
        "No reply added.";


    document.getElementById(
        "viewModal"
    ).classList.add("show");

}


/* =========================
   EDIT COMPLAINT
========================= */

function openEditModal(complaint) {

    document.getElementById(
        "edit_complaint_id"
    ).value =
        complaint.id;


    document.getElementById(
        "edit_status"
    ).value =
        complaint.status;


    document.getElementById(
        "edit_admin_reply"
    ).value =
        complaint.admin_reply || "";


    document.getElementById(
        "editComplaintSubject"
    ).textContent =
        complaint.subject;


    document.getElementById(
        "editModal"
    ).classList.add("show");

}


/* =========================
   CLOSE VIEW MODAL
========================= */

function closeViewModal() {

    document.getElementById(
        "viewModal"
    ).classList.remove("show");

}


/* =========================
   CLOSE EDIT MODAL
========================= */

function closeEditModal() {

    document.getElementById(
        "editModal"
    ).classList.remove("show");

}


/* =========================
   ESC KEY
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
   CLICK OUTSIDE
========================= */

document.querySelectorAll(
    ".modal-overlay"
).forEach(
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

</script>

</body>

</html>