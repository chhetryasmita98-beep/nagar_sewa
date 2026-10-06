<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];
$application_id = isset($_GET["application_id"])
    ? (int) $_GET["application_id"]
    : 0;

if ($application_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

/* Get application information */
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.application_number,
        a.status,
        a.applied_at,
        s.service_name,
        s.required_documents
    FROM applications a
    INNER JOIN services s ON a.service_id = s.id
    WHERE a.id = ? AND a.user_id = ?
");

$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

if (!$application) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$message_type = "";

/* Handle document upload */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_FILES["document"])) {
        $message = "Please select a document.";
        $message_type = "error";
    } else {

        $file = $_FILES["document"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $message = "There was an error uploading the file.";
            $message_type = "error";
        } else {

            $original_name = basename($file["name"]);
            $file_size = $file["size"];
            $tmp_name = $file["tmp_name"];

            $file_extension = strtolower(
                pathinfo($original_name, PATHINFO_EXTENSION)
            );

            /* Allowed file types */
            $allowed_extensions = [
                "pdf",
                "jpg",
                "jpeg",
                "png"
            ];

            /* Maximum size = 5 MB */
            $max_size = 5 * 1024 * 1024;

            if (!in_array($file_extension, $allowed_extensions)) {

                $message = "Only PDF, JPG, JPEG and PNG files are allowed.";
                $message_type = "error";

            } elseif ($file_size > $max_size) {

                $message = "File size must not exceed 5 MB.";
                $message_type = "error";

            } else {

                /* Create upload folder if it does not exist */
                $upload_directory = "../uploads/documents/";

                if (!is_dir($upload_directory)) {
                    mkdir($upload_directory, 0777, true);
                }

                /*
                 * Generate unique filename
                 */
                $new_file_name =
                    "app_" .
                    $application_id .
                    "_" .
                    time() .
                    "_" .
                    uniqid() .
                    "." .
                    $file_extension;

                $file_path = $upload_directory . $new_file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {

                    /*
                     * Store relative path in database
                     */
                    $database_path =
                        "uploads/documents/" . $new_file_name;

                    $stmt = $conn->prepare("
                        INSERT INTO documents
                        (
                            application_id,
                            document_name,
                            file_path
                        )
                        VALUES (?, ?, ?)
                    ");

                    $stmt->bind_param(
                        "iss",
                        $application_id,
                        $original_name,
                        $database_path
                    );

                    if ($stmt->execute()) {

                        $message =
                            "Document uploaded successfully.";

                        $message_type = "success";

                    } else {

                        /*
                         * Delete file if database insertion fails
                         */
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }

                        $message =
                            "Document could not be saved.";
                        $message_type = "error";
                    }

                    $stmt->close();

                } else {

                    $message =
                        "Failed to move uploaded file.";
                    $message_type = "error";
                }
            }
        }
    }
}

/* Get uploaded documents */
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

$stmt->bind_param("i", $application_id);
$stmt->execute();

$documents_result = $stmt->get_result();

$documents = [];

while ($row = $documents_result->fetch_assoc()) {
    $documents[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Upload Documents - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="upload-document.css"
    >

</head>

<body>

<div class="page-wrapper">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="logo">
            <div class="logo-icon">🏛️</div>

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


    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <header class="top-header">

            <div>
                <h1>Upload Documents</h1>

                <p>
                    Upload the required documents for your application.
                </p>
            </div>

            <div class="user-info">

                <div class="user-avatar">
                    <?php
                    echo strtoupper(
                        substr($_SESSION["user_name"], 0, 1)
                    );
                    ?>
                </div>

                <div>
                    <strong>
                        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                    </strong>

                    <small>Citizen</small>
                </div>

            </div>

        </header>


        <section class="content-area">

            <!-- Progress -->
            <div class="progress-section">

                <div class="progress-step completed">
                    <div class="step-number">✓</div>
                    <span>Application</span>
                </div>

                <div class="progress-line completed-line"></div>

                <div class="progress-step active">
                    <div class="step-number">2</div>
                    <span>Documents</span>
                </div>

                <div class="progress-line"></div>

                <div class="progress-step">
                    <div class="step-number">3</div>
                    <span>Confirmation</span>
                </div>

            </div>


            <!-- Message -->
            <?php if ($message != ""): ?>

                <div class="message <?php echo $message_type; ?>">

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php endif; ?>


            <div class="document-layout">

                <!-- Application Information -->
                <div class="application-card">

                    <div class="card-header">
                        <h2>Application Details</h2>
                    </div>

                    <div class="application-info">

                        <div class="info-item">

                            <span>Application Number</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $application["application_number"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="info-item">

                            <span>Service</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $application["service_name"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="info-item">

                            <span>Status</span>

                            <span class="status pending">
                                <?php
                                echo htmlspecialchars(
                                    $application["status"]
                                );
                                ?>
                            </span>

                        </div>

                    </div>


                    <div class="required-documents">

                        <h3>Required Documents</h3>

                        <?php

                        $required_documents =
                            $application["required_documents"];

                        $document_list =
                            array_map(
                                "trim",
                                explode(
                                    ",",
                                    $required_documents
                                )
                            );

                        ?>

                        <ul>

                            <?php foreach ($document_list as $document): ?>

                                <li>
                                    <span>✓</span>

                                    <?php
                                    echo htmlspecialchars($document);
                                    ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                </div>


                <!-- Upload Card -->
                <div class="upload-card">

                    <div class="card-header">

                        <h2>Upload Document</h2>

                        <p>
                            Select a document from your computer.
                        </p>

                    </div>


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        class="upload-form"
                    >

                        <div class="upload-box">

                            <div class="upload-icon">
                                📄
                            </div>

                            <h3>
                                Choose a document
                            </h3>

                            <p>
                                PDF, JPG, JPEG or PNG
                            </p>

                            <p>
                                Maximum size: 5 MB
                            </p>

                            <label
                                for="document"
                                class="choose-button"
                            >
                                Choose File
                            </label>

                            <input
                                type="file"
                                name="document"
                                id="document"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                            >

                            <div
                                id="file-name"
                                class="file-name"
                            >
                                No file selected
                            </div>

                        </div>


                        <button
                            type="submit"
                            class="upload-button"
                        >
                            Upload Document
                        </button>

                    </form>

                </div>

            </div>


            <!-- Uploaded Documents -->
            <div class="uploaded-section">

                <div class="section-heading">

                    <div>
                        <h2>Uploaded Documents</h2>

                        <p>
                            Documents already submitted for this application.
                        </p>
                    </div>

                </div>


                <?php if (count($documents) > 0): ?>

                    <div class="documents-table-wrapper">

                        <table class="documents-table">

                            <thead>

                                <tr>

                                    <th>#</th>
                                    <th>Document Name</th>
                                    <th>Uploaded Date</th>
                                    <th>Action</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php
                                $number = 1;
                                ?>

                                <?php foreach ($documents as $document): ?>

                                    <tr>

                                        <td>
                                            <?php echo $number++; ?>
                                        </td>

                                        <td>

                                            <div class="document-name">

                                                <span class="file-icon">
                                                    📄
                                                </span>

                                                <?php
                                                echo htmlspecialchars(
                                                    $document["document_name"]
                                                );
                                                ?>

                                            </div>

                                        </td>

                                        <td>

                                            <?php
                                            echo date(
                                                "d M Y, h:i A",
                                                strtotime(
                                                    $document["uploaded_at"]
                                                )
                                            );
                                            ?>

                                        </td>

                                        <td>

                                            <a
                                                href="../<?php echo htmlspecialchars($document["file_path"]); ?>"
                                                target="_blank"
                                                class="view-button"
                                            >
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-documents">

                        <div class="empty-icon">
                            📂
                        </div>

                        <h3>
                            No documents uploaded yet
                        </h3>

                        <p>
                            Upload the required documents above.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- Continue -->
            <div class="continue-section">

                <a
                    href="track-application.php"
                    class="continue-button"
                >
                    Continue to Application Tracking →
                </a>

            </div>

        </section>

    </main>

</div>


<script>

const documentInput =
    document.getElementById("document");

const fileName =
    document.getElementById("file-name");

documentInput.addEventListener(
    "change",
    function () {

        if (this.files.length > 0) {

            fileName.textContent =
                this.files[0].name;

        } else {

            fileName.textContent =
                "No file selected";

        }

    }
);

</script>

</body>

</html>