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
   ADD SERVICE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_service"])) {

    $service_name = trim($_POST["service_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $required_documents = trim($_POST["required_documents"] ?? "");
    $status = $_POST["status"] ?? "Active";

    if ($service_name === "") {

        $error_message = "Service name is required.";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO services
            (service_name, description, required_documents, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssss",
            $service_name,
            $description,
            $required_documents,
            $status
        );

        if ($stmt->execute()) {

            $success_message = "Service added successfully.";

        } else {

            $error_message = "Unable to add service.";
        }

        $stmt->close();
    }
}


/* =========================
   UPDATE SERVICE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_service"])) {

    $service_id = intval($_POST["service_id"] ?? 0);

    $service_name = trim($_POST["service_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $required_documents = trim($_POST["required_documents"] ?? "");
    $status = $_POST["status"] ?? "Active";

    if ($service_id <= 0) {

        $error_message = "Invalid service.";

    } elseif ($service_name === "") {

        $error_message = "Service name is required.";

    } else {

        $stmt = $conn->prepare("
            UPDATE services
            SET
                service_name = ?,
                description = ?,
                required_documents = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssi",
            $service_name,
            $description,
            $required_documents,
            $status,
            $service_id
        );

        if ($stmt->execute()) {

            $success_message = "Service updated successfully.";

        } else {

            $error_message = "Unable to update service.";
        }

        $stmt->close();
    }
}


/* =========================
   TOGGLE SERVICE STATUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_status"])) {

    $service_id = intval($_POST["service_id"] ?? 0);

    if ($service_id > 0) {

        $stmt = $conn->prepare("
            UPDATE services
            SET status =
                CASE
                    WHEN status = 'Active'
                    THEN 'Inactive'
                    ELSE 'Active'
                END
            WHERE id = ?
        ");

        $stmt->bind_param("i", $service_id);

        if ($stmt->execute()) {

            $success_message = "Service status updated successfully.";

        } else {

            $error_message = "Unable to update service status.";
        }

        $stmt->close();
    }
}


/* =========================
   DELETE SERVICE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_service"])) {

    $service_id = intval($_POST["service_id"] ?? 0);

    if ($service_id > 0) {

        /*
         * Check whether applications are linked
         * to this service before deleting.
         */

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM applications
            WHERE service_id = ?
        ");

        $stmt->bind_param("i", $service_id);

        $stmt->execute();

        $result = $stmt->get_result();

        $application_count = $result->fetch_assoc()["total"];

        $stmt->close();


        if ($application_count > 0) {

            $error_message =
                "This service cannot be deleted because applications are already linked to it. You can deactivate it instead.";

        } else {

            $stmt = $conn->prepare("
                DELETE FROM services
                WHERE id = ?
            ");

            $stmt->bind_param("i", $service_id);

            if ($stmt->execute()) {

                $success_message = "Service deleted successfully.";

            } else {

                $error_message = "Unable to delete service.";
            }

            $stmt->close();
        }
    }
}


/* =========================
   FETCH SERVICES
========================= */

$services = $conn->query("
    SELECT
        id,
        service_name,
        description,
        required_documents,
        status,
        created_at
    FROM services
    ORDER BY created_at DESC
");


/* =========================
   STATISTICS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM services
");

$total_services = $result->fetch_assoc()["total"];


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM services
    WHERE status = 'Active'
");

$active_services = $result->fetch_assoc()["total"];


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM services
    WHERE status = 'Inactive'
");

$inactive_services = $result->fetch_assoc()["total"];


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM applications
");

$total_applications = $result->fetch_assoc()["total"];


/* =========================
   ADMIN INFO
========================= */

$admin_name = $_SESSION["admin_name"] ?? "Administrator";

$admin_initial = strtoupper(
    substr($admin_name, 0, 1)
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

    <title>Services - Nagar Sewa Admin</title>

    <link
        rel="stylesheet"
        href="services.css"
    >

</head>

<body>

<div class="admin-wrapper">


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                🏛️
            </div>

            <div>

                <h2>Nagar Sewa</h2>

                <span>
                    Admin Panel
                </span>

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

            <a
                href="services.php"
                class="active"
            >
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

            <a href="notices.php">
                <span>📢</span>
                Notices
            </a>

            <a href="reports.php">
                <span>📈</span>
                Reports
            </a>

            <a
                href="logout.php"
                class="logout-link"
            >
                <span>🚪</span>
                Logout
            </a>

        </nav>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">

            <div>

                <h1>
                    Government Services
                </h1>

                <p>
                    Manage the services available to Nagar Sewa citizens.
                </p>

            </div>


            <div class="admin-info">

                <div class="admin-avatar">

                    <?php
                    echo htmlspecialchars(
                        $admin_initial
                    );
                    ?>

                </div>


                <div>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $admin_name
                        );
                        ?>

                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <div class="content">


            <!-- ================= ALERT ================= -->

            <?php if ($success_message): ?>

                <div class="alert success">

                    <span>✓</span>

                    <?php
                    echo htmlspecialchars(
                        $success_message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <?php if ($error_message): ?>

                <div class="alert error">

                    <span>!</span>

                    <?php
                    echo htmlspecialchars(
                        $error_message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <!-- ================= STATISTICS ================= -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        📋
                    </div>

                    <div class="stat-details">

                        <span>
                            Total Services
                        </span>

                        <strong>
                            <?php
                            echo $total_services;
                            ?>
                        </strong>

                        <small>
                            Registered services
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <div class="stat-details">

                        <span>
                            Active Services
                        </span>

                        <strong>
                            <?php
                            echo $active_services;
                            ?>
                        </strong>

                        <small>
                            Currently available
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        ⏸
                    </div>

                    <div class="stat-details">

                        <span>
                            Inactive Services
                        </span>

                        <strong>
                            <?php
                            echo $inactive_services;
                            ?>
                        </strong>

                        <small>
                            Currently disabled
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        📝
                    </div>

                    <div class="stat-details">

                        <span>
                            Applications
                        </span>

                        <strong>
                            <?php
                            echo $total_applications;
                            ?>
                        </strong>

                        <small>
                            Across all services
                        </small>

                    </div>

                </div>


            </div>


            <!-- ================= SERVICE HEADER ================= -->

            <div class="page-actions">

                <div>

                    <h2>
                        Available Services
                    </h2>

                    <p>
                        Add, update and manage government services.
                    </p>

                </div>


                <button
                    type="button"
                    class="add-button"
                    onclick="openAddModal()"
                >
                    + Add New Service
                </button>

            </div>


            <!-- ================= SERVICES GRID ================= -->

            <div class="services-grid">


                <?php if ($services && $services->num_rows > 0): ?>


                    <?php while ($service = $services->fetch_assoc()): ?>


                        <div class="service-card">


                            <!-- CARD TOP -->

                            <div class="service-top">

                                <div class="service-icon">
                                    📋
                                </div>


                                <?php if ($service["status"] === "Active"): ?>

                                    <span class="status-badge active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- SERVICE NAME -->

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $service["service_name"]
                                );
                                ?>

                            </h3>


                            <!-- DESCRIPTION -->

                            <p class="service-description">

                                <?php

                                if (
                                    !empty(
                                        $service["description"]
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $service["description"]
                                    );

                                } else {

                                    echo "No description provided.";

                                }

                                ?>

                            </p>


                            <!-- REQUIRED DOCUMENTS -->

                            <div class="documents-section">

                                <h4>
                                    Required Documents
                                </h4>


                                <?php

                                $documents = [];

                                if (
                                    !empty(
                                        $service["required_documents"]
                                    )
                                ) {

                                    $documents = preg_split(
                                        "/[,\\n]+/",
                                        $service["required_documents"]
                                    );

                                }

                                ?>


                                <?php if (!empty($documents)): ?>

                                    <ul>

                                        <?php foreach ($documents as $document): ?>

                                            <?php
                                            $document = trim($document);
                                            ?>

                                            <?php if ($document !== ""): ?>

                                                <li>
                                                    <span>✓</span>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $document
                                                    );
                                                    ?>
                                                </li>

                                            <?php endif; ?>

                                        <?php endforeach; ?>

                                    </ul>

                                <?php else: ?>

                                    <p class="no-documents">
                                        No documents specified.
                                    </p>

                                <?php endif; ?>

                            </div>


                            <!-- CREATED DATE -->

                            <div class="service-date">

                                Added:

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $service["created_at"]
                                    )
                                );

                                ?>

                            </div>


                            <!-- ACTIONS -->

                            <div class="service-actions">


                                <button
                                    type="button"
                                    class="edit-button"
                                    onclick='openEditModal(
                                        <?php
                                        echo json_encode(
                                            [
                                                "id" =>
                                                    $service["id"],
                                                "name" =>
                                                    $service["service_name"],
                                                "description" =>
                                                    $service["description"],
                                                "documents" =>
                                                    $service["required_documents"],
                                                "status" =>
                                                    $service["status"]
                                            ],
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_AMP |
                                            JSON_HEX_QUOT
                                        );
                                        ?>
                                    )'
                                >
                                    ✏️ Edit
                                </button>


                                <form
                                    method="POST"
                                    action="services.php"
                                    class="inline-form"
                                    onsubmit="return confirm('Are you sure you want to change the status of this service?');"
                                >

                                    <input
                                        type="hidden"
                                        name="service_id"
                                        value="<?php
                                        echo $service["id"];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="toggle_status"
                                        class="status-button"
                                    >

                                        <?php
                                        if (
                                            $service["status"] ===
                                            "Active"
                                        ) {
                                            echo "⏸ Disable";
                                        } else {
                                            echo "▶ Activate";
                                        }
                                        ?>

                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    action="services.php"
                                    class="inline-form"
                                    onsubmit="return confirm('Are you sure you want to delete this service?');"
                                >

                                    <input
                                        type="hidden"
                                        name="service_id"
                                        value="<?php
                                        echo $service["id"];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_service"
                                        class="delete-button"
                                    >
                                        🗑️
                                    </button>

                                </form>


                            </div>


                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            📋
                        </div>

                        <h3>
                            No services found
                        </h3>

                        <p>
                            Add your first government service.
                        </p>

                        <button
                            type="button"
                            class="add-button"
                            onclick="openAddModal()"
                        >
                            + Add Service
                        </button>

                    </div>


                <?php endif; ?>


            </div>


        </div>

    </main>

</div>


<!-- =========================
     ADD SERVICE MODAL
========================= -->

<div
    class="modal"
    id="addModal"
>

    <div class="modal-content">


        <div class="modal-header">

            <div>

                <h2>
                    Add New Service
                </h2>

                <p>
                    Create a new government service.
                </p>

            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeAddModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="services.php"
            class="service-form"
        >

            <div class="form-group">

                <label>
                    Service Name
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="service_name"
                    placeholder="e.g. Birth Certificate"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    placeholder="Enter service description..."
                ></textarea>

            </div>


            <div class="form-group">

                <label>
                    Required Documents
                </label>

                <textarea
                    name="required_documents"
                    rows="4"
                    placeholder="Enter documents separated by commas..."
                ></textarea>

                <small>
                    Example: Citizenship, Hospital Birth Record, Passport Photo
                </small>

            </div>


            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option value="Active">
                        Active
                    </option>

                    <option value="Inactive">
                        Inactive
                    </option>

                </select>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeAddModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="add_service"
                    class="submit-button"
                >
                    Add Service
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================
     EDIT SERVICE MODAL
========================= -->

<div
    class="modal"
    id="editModal"
>

    <div class="modal-content">


        <div class="modal-header">

            <div>

                <h2>
                    Edit Service
                </h2>

                <p>
                    Update government service information.
                </p>

            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeEditModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="services.php"
            class="service-form"
        >

            <input
                type="hidden"
                name="service_id"
                id="edit_service_id"
            >


            <div class="form-group">

                <label>
                    Service Name
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="service_name"
                    id="edit_service_name"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    id="edit_description"
                    rows="4"
                ></textarea>

            </div>


            <div class="form-group">

                <label>
                    Required Documents
                </label>

                <textarea
                    name="required_documents"
                    id="edit_required_documents"
                    rows="4"
                ></textarea>

                <small>
                    Separate multiple documents using commas.
                </small>

            </div>


            <div class="form-group">

                <label>
                    Status
                </label>

                <select
                    name="status"
                    id="edit_status"
                >

                    <option value="Active">
                        Active
                    </option>

                    <option value="Inactive">
                        Inactive
                    </option>

                </select>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="update_service"
                    class="submit-button"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================
   ADD MODAL
========================= */

function openAddModal() {

    document
        .getElementById("addModal")
        .classList.add("show");

}


function closeAddModal() {

    document
        .getElementById("addModal")
        .classList.remove("show");

}


/* =========================
   EDIT MODAL
========================= */

function openEditModal(service) {

    document.getElementById("edit_service_id").value =
        service.id;

    document.getElementById("edit_service_name").value =
        service.name;

    document.getElementById("edit_description").value =
        service.description;

    document.getElementById("edit_required_documents").value =
        service.documents;

    document.getElementById("edit_status").value =
        service.status;


    document
        .getElementById("editModal")
        .classList.add("show");

}


function closeEditModal() {

    document
        .getElementById("editModal")
        .classList.remove("show");

}


/* =========================
   CLOSE MODAL ON OUTSIDE CLICK
========================= */

window.addEventListener(
    "click",
    function(event) {

        const addModal =
            document.getElementById("addModal");

        const editModal =
            document.getElementById("editModal");


        if (event.target === addModal) {

            closeAddModal();

        }


        if (event.target === editModal) {

            closeEditModal();

        }

    }
);


/* =========================
   ESC KEY
========================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeAddModal();

            closeEditModal();

        }

    }
);

</script>

</body>

</html>