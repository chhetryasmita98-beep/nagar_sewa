<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$admin_name = $_SESSION["admin_name"] ?? "Admin";

$message = "";
$message_type = "";

/* =========================
   ADD NOTICE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_notice"])) {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $notice_date = $_POST["notice_date"] ?? "";

    if ($title === "" || $description === "" || $notice_date === "") {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO notices (title, description, notice_date)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $title, $description, $notice_date);

        if ($stmt->execute()) {
            $message = "Notice added successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to add notice.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/* =========================
   UPDATE NOTICE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_notice"])) {

    $id = intval($_POST["notice_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $notice_date = $_POST["notice_date"] ?? "";

    if ($id <= 0 || $title === "" || $description === "" || $notice_date === "") {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "UPDATE notices
             SET title = ?, description = ?, notice_date = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $title,
            $description,
            $notice_date,
            $id
        );

        if ($stmt->execute()) {
            $message = "Notice updated successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to update notice.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/* =========================
   DELETE NOTICE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_notice"])) {

    $id = intval($_POST["notice_id"] ?? 0);

    if ($id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM notices WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Notice deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to delete notice.";
            $message_type = "error";
        }

        $stmt->close();
    }
}


/* =========================
   SEARCH
========================= */
$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT id, title, description, notice_date, created_at
         FROM notices
         WHERE title LIKE ?
            OR description LIKE ?
         ORDER BY notice_date DESC, id DESC"
    );

    $stmt->bind_param("ss", $search_value, $search_value);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query(
        "SELECT id, title, description, notice_date, created_at
         FROM notices
         ORDER BY notice_date DESC, id DESC"
    );
}


/* =========================
   STATISTICS
========================= */

$total_notices = 0;
$today_notices = 0;
$this_month = 0;
$upcoming_notices = 0;

$stats_result = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(notice_date = CURDATE()) AS today,
        SUM(
            YEAR(notice_date) = YEAR(CURDATE())
            AND MONTH(notice_date) = MONTH(CURDATE())
        ) AS this_month,
        SUM(notice_date >= CURDATE()) AS upcoming
     FROM notices"
);

if ($stats_result) {
    $stats = $stats_result->fetch_assoc();

    $total_notices = (int)($stats["total"] ?? 0);
    $today_notices = (int)($stats["today"] ?? 0);
    $this_month = (int)($stats["this_month"] ?? 0);
    $upcoming_notices = (int)($stats["upcoming"] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notices - Nagar Sewa Admin</title>

    <link rel="stylesheet" href="notices.css">
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

            <a href="complaints.php">
                <span>💬</span>
                Complaints
            </a>

            <a href="notices.php" class="active">
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
                <h1>Notices</h1>
                <p>Manage announcements and public notices</p>
            </div>

            <div class="admin-info">

                <div class="admin-avatar">
                    <?php
                    echo strtoupper(
                        substr($admin_name, 0, 1)
                    );
                    ?>
                </div>

                <div>
                    <strong>
                        <?php echo htmlspecialchars($admin_name); ?>
                    </strong>

                    <small>Administrator</small>
                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <section class="page-content">


            <!-- =========================
                 MESSAGE
            ========================== -->

            <?php if ($message !== ""): ?>

                <div class="alert <?php echo $message_type; ?>">

                    <span>
                        <?php echo $message_type === "success" ? "✓" : "⚠"; ?>
                    </span>

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <!-- =========================
                 STAT CARDS
            ========================== -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        📢
                    </div>

                    <div>
                        <span>Total Notices</span>
                        <h3><?php echo $total_notices; ?></h3>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        📅
                    </div>

                    <div>
                        <span>Today's Notices</span>
                        <h3><?php echo $today_notices; ?></h3>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        🗓️
                    </div>

                    <div>
                        <span>This Month</span>
                        <h3><?php echo $this_month; ?></h3>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        🔔
                    </div>

                    <div>
                        <span>Upcoming</span>
                        <h3><?php echo $upcoming_notices; ?></h3>
                    </div>

                </div>

            </div>


            <!-- =========================
                 TOP ACTIONS
            ========================== -->

            <div class="content-top">

                <div>

                    <h2>Notice Management</h2>

                    <p>
                        Create, update and remove public notices.
                    </p>

                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="openAddModal()"
                >
                    + Add New Notice
                </button>

            </div>


            <!-- =========================
                 SEARCH
            ========================== -->

            <div class="filter-box">

                <form method="GET" class="search-form">

                    <div class="search-input">

                        <span>🔍</span>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search notices..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>

                    <button type="submit" class="btn btn-search">
                        Search
                    </button>

                    <?php if ($search !== ""): ?>

                        <a
                            href="notices.php"
                            class="btn btn-reset"
                        >
                            Reset
                        </a>

                    <?php endif; ?>

                </form>

            </div>


            <!-- =========================
                 NOTICE TABLE
            ========================== -->

            <div class="table-card">

                <div class="table-header">

                    <div>
                        <h2>All Notices</h2>

                        <p>
                            <?php echo $result ? $result->num_rows : 0; ?>
                            notice(s) found
                        </p>
                    </div>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                        <tr>

                            <th>#</th>

                            <th>Notice</th>

                            <th>Description</th>

                            <th>Notice Date</th>

                            <th>Created</th>

                            <th>Actions</th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php if ($result && $result->num_rows > 0): ?>

                            <?php
                            $count = 1;
                            while ($notice = $result->fetch_assoc()):
                            ?>

                                <tr>

                                    <td>
                                        <?php echo $count++; ?>
                                    </td>


                                    <td>

                                        <div class="notice-title">

                                            <div class="notice-icon">
                                                📢
                                            </div>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $notice["title"]
                                                );
                                                ?>
                                            </strong>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="description-text">

                                            <?php
                                            $description =
                                                $notice["description"];

                                            if (strlen($description) > 100) {
                                                echo htmlspecialchars(
                                                    substr(
                                                        $description,
                                                        0,
                                                        100
                                                    )
                                                ) . "...";
                                            } else {
                                                echo htmlspecialchars(
                                                    $description
                                                );
                                            }
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <span class="date-badge">

                                            📅

                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $notice["notice_date"]
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $notice["created_at"]
                                            )
                                        );
                                        ?>

                                        <small class="time-text">

                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $notice["created_at"]
                                                )
                                            );
                                            ?>

                                        </small>

                                    </td>


                                    <td>

                                        <div class="action-buttons">

                                            <button
                                                type="button"
                                                class="action-btn view"
                                                onclick='viewNotice(
                                                    <?php
                                                    echo json_encode([
                                                        "title" => $notice["title"],
                                                        "description" => $notice["description"],
                                                        "notice_date" => $notice["notice_date"]
                                                    ]);
                                                    ?>
                                                )'
                                                title="View"
                                            >
                                                👁️
                                            </button>


                                            <button
                                                type="button"
                                                class="action-btn edit"
                                                onclick='openEditModal(
                                                    <?php
                                                    echo json_encode([
                                                        "id" => $notice["id"],
                                                        "title" => $notice["title"],
                                                        "description" => $notice["description"],
                                                        "notice_date" => $notice["notice_date"]
                                                    ]);
                                                    ?>
                                                )'
                                                title="Edit"
                                            >
                                                ✏️
                                            </button>


                                            <form
                                                method="POST"
                                                class="delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this notice?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="notice_id"
                                                    value="<?php echo $notice["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_notice"
                                                    class="action-btn delete"
                                                    title="Delete"
                                                >
                                                    🗑️
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
                                        📢
                                    </div>

                                    <h3>No Notices Found</h3>

                                    <p>
                                        No notices are available.
                                    </p>

                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        onclick="openAddModal()"
                                    >
                                        + Add Notice
                                    </button>

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
     ADD NOTICE MODAL
========================= -->

<div
    class="modal-overlay"
    id="addModal"
>

    <div class="modal">

        <div class="modal-header">

            <div>

                <h2>Add New Notice</h2>

                <p>Create a public announcement.</p>

            </div>

            <button
                type="button"
                class="close-btn"
                onclick="closeAddModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <div class="form-group">

                <label>
                    Notice Title
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="Enter notice title"
                    maxlength="200"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Notice Date
                    <span>*</span>
                </label>

                <input
                    type="date"
                    name="notice_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Description
                    <span>*</span>
                </label>

                <textarea
                    name="description"
                    rows="6"
                    placeholder="Enter notice details..."
                    required
                ></textarea>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="closeAddModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="add_notice"
                    class="btn btn-primary"
                >
                    Publish Notice
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================
     EDIT NOTICE MODAL
========================= -->

<div
    class="modal-overlay"
    id="editModal"
>

    <div class="modal">

        <div class="modal-header">

            <div>

                <h2>Edit Notice</h2>

                <p>Update notice information.</p>

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

            <input
                type="hidden"
                name="notice_id"
                id="edit_notice_id"
            >


            <div class="form-group">

                <label>
                    Notice Title
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="title"
                    id="edit_title"
                    maxlength="200"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Notice Date
                    <span>*</span>
                </label>

                <input
                    type="date"
                    name="notice_date"
                    id="edit_notice_date"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Description
                    <span>*</span>
                </label>

                <textarea
                    name="description"
                    id="edit_description"
                    rows="6"
                    required
                ></textarea>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="update_notice"
                    class="btn btn-primary"
                >
                    Update Notice
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================
     VIEW NOTICE MODAL
========================= -->

<div
    class="modal-overlay"
    id="viewModal"
>

    <div class="modal view-modal">

        <div class="modal-header">

            <div>

                <h2>Notice Details</h2>

                <p>View complete notice information.</p>

            </div>

            <button
                type="button"
                class="close-btn"
                onclick="closeViewModal()"
            >
                ×
            </button>

        </div>


        <div class="notice-preview">

            <div class="preview-icon">
                📢
            </div>

            <h2 id="view_title"></h2>

            <div class="preview-date">
                📅 <span id="view_date"></span>
            </div>

            <div
                class="preview-description"
                id="view_description"
            ></div>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-cancel"
                onclick="closeViewModal()"
            >
                Close
            </button>

        </div>

    </div>

</div>


<script>

/* =========================
   ADD MODAL
========================= */

function openAddModal() {

    document.getElementById("addModal")
        .classList.add("show");
}


function closeAddModal() {

    document.getElementById("addModal")
        .classList.remove("show");
}


/* =========================
   EDIT MODAL
========================= */

function openEditModal(notice) {

    document.getElementById("edit_notice_id").value =
        notice.id;

    document.getElementById("edit_title").value =
        notice.title;

    document.getElementById("edit_description").value =
        notice.description;

    document.getElementById("edit_notice_date").value =
        notice.notice_date;

    document.getElementById("editModal")
        .classList.add("show");
}


function closeEditModal() {

    document.getElementById("editModal")
        .classList.remove("show");
}


/* =========================
   VIEW MODAL
========================= */

function viewNotice(notice) {

    document.getElementById("view_title").textContent =
        notice.title;

    document.getElementById("view_description").textContent =
        notice.description;

    const date = new Date(
        notice.notice_date + "T00:00:00"
    );

    document.getElementById("view_date").textContent =
        date.toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );

    document.getElementById("viewModal")
        .classList.add("show");
}


function closeViewModal() {

    document.getElementById("viewModal")
        .classList.remove("show");
}


/* =========================
   CLOSE MODAL ON OUTSIDE CLICK
========================= */

window.addEventListener("click", function(event) {

    const addModal =
        document.getElementById("addModal");

    const editModal =
        document.getElementById("editModal");

    const viewModal =
        document.getElementById("viewModal");


    if (event.target === addModal) {
        closeAddModal();
    }

    if (event.target === editModal) {
        closeEditModal();
    }

    if (event.target === viewModal) {
        closeViewModal();
    }

});


/* =========================
   ESCAPE KEY
========================= */

document.addEventListener("keydown", function(event) {

    if (event.key === "Escape") {

        closeAddModal();
        closeEditModal();
        closeViewModal();

    }

});

</script>

</body>
</html>