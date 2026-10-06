<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$success_message = "";
$error_message = "";


/* =========================================================
   ISSUE CERTIFICATE
========================================================= */

if (isset($_POST["issue_certificate"])) {

    $application_id = (int) $_POST["application_id"];

    $certificate_number = trim(
        $_POST["certificate_number"]
    );

    if ($application_id <= 0) {

        $error_message = "Invalid application.";

    } elseif ($certificate_number === "") {

        $error_message = "Please enter a certificate number.";

    } else {

        /* Check application */

        $stmt = $conn->prepare(
            "SELECT
                a.id,
                a.status
             FROM applications a
             WHERE a.id = ?"
        );

        $stmt->bind_param(
            "i",
            $application_id
        );

        $stmt->execute();

        $application_result =
            $stmt->get_result();

        $application =
            $application_result->fetch_assoc();

        $stmt->close();


        if (!$application) {

            $error_message =
                "Application not found.";

        } elseif ($application["status"] !== "Approved") {

            $error_message =
                "Certificate can only be issued for approved applications.";

        } else {

            /* Check duplicate certificate */

            $stmt = $conn->prepare(
                "SELECT id
                 FROM certificates
                 WHERE certificate_number = ?"
            );

            $stmt->bind_param(
                "s",
                $certificate_number
            );

            $stmt->execute();

            $duplicate_result =
                $stmt->get_result();

            $duplicate =
                $duplicate_result->num_rows > 0;

            $stmt->close();


            if ($duplicate) {

                $error_message =
                    "This certificate number already exists.";

            } else {

                /* Check whether certificate already exists */

                $stmt = $conn->prepare(
                    "SELECT id
                     FROM certificates
                     WHERE application_id = ?"
                );

                $stmt->bind_param(
                    "i",
                    $application_id
                );

                $stmt->execute();

                $existing_result =
                    $stmt->get_result();

                $existing =
                    $existing_result->num_rows > 0;

                $stmt->close();


                if ($existing) {

                    $error_message =
                        "A certificate has already been issued for this application.";

                } else {

                    $certificate_file = "";


                    /* =================================================
                       UPLOAD CERTIFICATE
                    ================================================== */

                    if (
                        isset($_FILES["certificate_file"])
                        &&
                        $_FILES["certificate_file"]["error"]
                        === UPLOAD_ERR_OK
                    ) {

                        $file_name =
                            $_FILES["certificate_file"]["name"];

                        $file_tmp =
                            $_FILES["certificate_file"]["tmp_name"];

                        $file_size =
                            $_FILES["certificate_file"]["size"];

                        $file_ext =
                            strtolower(
                                pathinfo(
                                    $file_name,
                                    PATHINFO_EXTENSION
                                )
                            );


                        $allowed_extensions = [
                            "pdf"
                        ];


                        if (
                            !in_array(
                                $file_ext,
                                $allowed_extensions
                            )
                        ) {

                            $error_message =
                                "Only PDF certificate files are allowed.";

                        } elseif ($file_size > 5 * 1024 * 1024) {

                            $error_message =
                                "Certificate file must be less than 5 MB.";

                        } else {

                            $upload_directory =
                                "../uploads/certificates/";


                            if (
                                !is_dir(
                                    $upload_directory
                                )
                            ) {

                                mkdir(
                                    $upload_directory,
                                    0777,
                                    true
                                );
                            }


                            $new_file_name =
                                "certificate_" .
                                $application_id .
                                "_" .
                                time() .
                                ".pdf";


                            $target_path =
                                $upload_directory .
                                $new_file_name;


                            if (
                                move_uploaded_file(
                                    $file_tmp,
                                    $target_path
                                )
                            ) {

                                $certificate_file =
                                    "uploads/certificates/" .
                                    $new_file_name;

                            } else {

                                $error_message =
                                    "Failed to upload certificate file.";
                            }
                        }
                    }


                    /* =================================================
                       INSERT CERTIFICATE
                    ================================================== */

                    if ($error_message === "") {

                        $stmt = $conn->prepare(
                            "INSERT INTO certificates
                            (
                                application_id,
                                certificate_number,
                                certificate_file
                            )
                            VALUES (?, ?, ?)"
                        );

                        $stmt->bind_param(
                            "iss",
                            $application_id,
                            $certificate_number,
                            $certificate_file
                        );


                        if ($stmt->execute()) {

                            $success_message =
                                "Certificate issued successfully.";

                        } else {

                            $error_message =
                                "Unable to issue certificate.";

                        }

                        $stmt->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   DELETE CERTIFICATE
========================================================= */

if (isset($_POST["delete_certificate"])) {

    $certificate_id =
        (int) $_POST["certificate_id"];


    $stmt = $conn->prepare(
        "SELECT certificate_file
         FROM certificates
         WHERE id = ?"
    );

    $stmt->bind_param(
        "i",
        $certificate_id
    );

    $stmt->execute();

    $file_result =
        $stmt->get_result();

    $certificate =
        $file_result->fetch_assoc();

    $stmt->close();


    if ($certificate) {

        $stmt = $conn->prepare(
            "DELETE FROM certificates
             WHERE id = ?"
        );

        $stmt->bind_param(
            "i",
            $certificate_id
        );


        if ($stmt->execute()) {

            if (
                !empty(
                    $certificate["certificate_file"]
                )
            ) {

                $file_path =
                    "../" .
                    $certificate["certificate_file"];

                if (
                    file_exists($file_path)
                ) {

                    unlink($file_path);
                }
            }

            $success_message =
                "Certificate deleted successfully.";

        } else {

            $error_message =
                "Unable to delete certificate.";
        }

        $stmt->close();
    }
}


/* =========================================================
   FETCH APPROVED APPLICATIONS
========================================================= */

$approved_applications = $conn->query(
    "SELECT
        a.id,
        a.application_number,
        a.status,
        a.applied_at,
        u.full_name,
        u.email,
        s.service_name
     FROM applications a
     INNER JOIN users u
        ON a.user_id = u.id
     INNER JOIN services s
        ON a.service_id = s.id
     LEFT JOIN certificates c
        ON a.id = c.application_id
     WHERE a.status = 'Approved'
       AND c.id IS NULL
     ORDER BY a.updated_at DESC"
);


/* =========================================================
   FETCH ISSUED CERTIFICATES
========================================================= */

$certificates = $conn->query(
    "SELECT
        c.id,
        c.application_id,
        c.certificate_number,
        c.certificate_file,
        c.issued_at,
        a.application_number,
        u.full_name,
        u.email,
        s.service_name
     FROM certificates c
     INNER JOIN applications a
        ON c.application_id = a.id
     INNER JOIN users u
        ON a.user_id = u.id
     INNER JOIN services s
        ON a.service_id = s.id
     ORDER BY c.issued_at DESC"
);


/* =========================================================
   STATISTICS
========================================================= */

$total_certificates_result =
    $conn->query(
        "SELECT COUNT(*) AS total
         FROM certificates"
    );

$total_certificates =
    $total_certificates_result
        ->fetch_assoc()["total"];


$pending_certificates_result =
    $conn->query(
        "SELECT COUNT(*) AS total
         FROM applications a
         LEFT JOIN certificates c
            ON a.id = c.application_id
         WHERE a.status = 'Approved'
           AND c.id IS NULL"
    );

$pending_certificates =
    $pending_certificates_result
        ->fetch_assoc()["total"];


/* =========================================================
   ADMIN NAME
========================================================= */

$admin_name =
    $_SESSION["admin_name"] ?? "Administrator";

$admin_initial =
    strtoupper(
        substr(
            $admin_name,
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
        Certificates - Nagar Sewa Admin
    </title>

    <link
        rel="stylesheet"
        href="certificates.css"
    >

</head>

<body>


<div class="admin-wrapper">


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


            <a
                href="certificates.php"
                class="active"
            >
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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOP HEADER
        ================================================== -->

        <header class="top-header">


            <div>

                <h1>
                    Certificates
                </h1>

                <p>
                    Issue and manage citizen certificates.
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


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="content">


            <!-- =================================================
                 ALERTS
            ================================================== -->

            <?php if ($success_message !== ""): ?>

                <div class="alert success-alert">

                    <span class="alert-icon">
                        ✓
                    </span>

                    <?php
                    echo htmlspecialchars(
                        $success_message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <?php if ($error_message !== ""): ?>

                <div class="alert error-alert">

                    <span class="alert-icon">
                        !
                    </span>

                    <?php
                    echo htmlspecialchars(
                        $error_message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        📜
                    </div>

                    <div>

                        <span>
                            Total Certificates
                        </span>

                        <strong>
                            <?php
                            echo $total_certificates;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        ⏳
                    </div>

                    <div>

                        <span>
                            Awaiting Certificate
                        </span>

                        <strong>
                            <?php
                            echo $pending_certificates;
                            ?>
                        </strong>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 APPROVED APPLICATIONS
            ================================================== -->

            <section class="card">


                <div class="card-header">

                    <div>

                        <h2>
                            Approved Applications
                        </h2>

                        <p>
                            Applications that are ready for certificate issuance.
                        </p>

                    </div>

                    <div class="card-icon">
                        📜
                    </div>

                </div>


                <div class="table-wrapper">


                    <?php if (
                        $approved_applications
                        &&
                        $approved_applications->num_rows > 0
                    ): ?>


                        <table>


                            <thead>

                                <tr>

                                    <th>
                                        Application No.
                                    </th>

                                    <th>
                                        Citizen
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Applied Date
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php while (
                                    $application =
                                    $approved_applications->fetch_assoc()
                                ): ?>


                                    <tr>


                                        <td>

                                            <strong class="application-number">

                                                <?php
                                                echo htmlspecialchars(
                                                    $application[
                                                        "application_number"
                                                    ]
                                                );
                                                ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <div class="citizen-cell">

                                                <div class="small-avatar">

                                                    <?php
                                                    echo strtoupper(
                                                        substr(
                                                            $application[
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
                                                            $application[
                                                                "full_name"
                                                            ]
                                                        );
                                                        ?>

                                                    </strong>

                                                    <small>

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $application[
                                                                "email"
                                                            ]
                                                        );
                                                        ?>

                                                    </small>

                                                </div>

                                            </div>

                                        </td>


                                        <td>

                                            <span class="service-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $application[
                                                        "service_name"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $application[
                                                        "applied_at"
                                                    ]
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <button
                                                type="button"
                                                class="issue-button"
                                                onclick="openIssueModal(
                                                    <?php
                                                    echo (int)
                                                        $application["id"];
                                                    ?>,
                                                    '<?php
                                                    echo htmlspecialchars(
                                                        $application[
                                                            "application_number"
                                                        ],
                                                        ENT_QUOTES
                                                    );
                                                    ?>',
                                                    '<?php
                                                    echo htmlspecialchars(
                                                        $application[
                                                            "full_name"
                                                        ],
                                                        ENT_QUOTES
                                                    );
                                                    ?>',
                                                    '<?php
                                                    echo htmlspecialchars(
                                                        $application[
                                                            "service_name"
                                                        ],
                                                        ENT_QUOTES
                                                    );
                                                    ?>'
                                                )"
                                            >
                                                Issue Certificate
                                            </button>

                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>

                        </table>


                    <?php else: ?>


                        <div class="empty-state small-empty">

                            <div class="empty-icon">
                                📋
                            </div>

                            <h3>
                                No Approved Applications
                            </h3>

                            <p>
                                There are currently no approved
                                applications waiting for certificates.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>

            </section>


            <!-- =================================================
                 ISSUED CERTIFICATES
            ================================================== -->

            <section class="card issued-card">


                <div class="card-header">

                    <div>

                        <h2>
                            Issued Certificates
                        </h2>

                        <p>
                            View and manage certificates already issued.
                        </p>

                    </div>

                    <div class="card-icon green-icon">
                        ✓
                    </div>

                </div>


                <div class="table-wrapper">


                    <?php if (
                        $certificates
                        &&
                        $certificates->num_rows > 0
                    ): ?>


                        <table>


                            <thead>

                                <tr>

                                    <th>
                                        Certificate No.
                                    </th>

                                    <th>
                                        Application No.
                                    </th>

                                    <th>
                                        Citizen
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Issued Date
                                    </th>

                                    <th>
                                        Certificate
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php while (
                                    $certificate =
                                    $certificates->fetch_assoc()
                                ): ?>


                                    <tr>


                                        <td>

                                            <strong class="certificate-number">

                                                <?php
                                                echo htmlspecialchars(
                                                    $certificate[
                                                        "certificate_number"
                                                    ]
                                                );
                                                ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <span class="application-number">

                                                <?php
                                                echo htmlspecialchars(
                                                    $certificate[
                                                        "application_number"
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $certificate[
                                                    "full_name"
                                                ]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $certificate[
                                                    "service_name"
                                                ]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $certificate[
                                                        "issued_at"
                                                    ]
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>


                                            <?php if (
                                                !empty(
                                                    $certificate[
                                                        "certificate_file"
                                                    ]
                                                )
                                            ): ?>


                                                <a
                                                    href="../<?php
                                                    echo htmlspecialchars(
                                                        $certificate[
                                                            "certificate_file"
                                                        ]
                                                    );
                                                    ?>"
                                                    target="_blank"
                                                    class="view-button"
                                                >
                                                    View PDF
                                                </a>


                                            <?php else: ?>


                                                <span class="no-file">
                                                    No File
                                                </span>


                                            <?php endif; ?>


                                        </td>


                                        <td>


                                            <form
                                                method="POST"
                                                onsubmit="return confirm(
                                                    'Are you sure you want to delete this certificate?'
                                                );"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="certificate_id"
                                                    value="<?php
                                                    echo (int)
                                                        $certificate["id"];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_certificate"
                                                    class="delete-button"
                                                >
                                                    Delete
                                                </button>

                                            </form>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>

                        </table>


                    <?php else: ?>


                        <div class="empty-state">

                            <div class="empty-icon">
                                📜
                            </div>

                            <h3>
                                No Certificates Issued
                            </h3>

                            <p>
                                Issued certificates will appear here.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>

            </section>


        </div>

    </main>

</div>


<!-- =========================================================
     ISSUE CERTIFICATE MODAL
========================================================= -->

<div
    id="issueModal"
    class="modal"
>


    <div class="modal-content">


        <div class="modal-header">

            <div>

                <h2>
                    Issue Certificate
                </h2>

                <p>
                    Enter certificate information.
                </p>

            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeIssueModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            class="certificate-form"
        >


            <input
                type="hidden"
                name="application_id"
                id="application_id"
            >


            <div class="selected-application">

                <div>

                    <span>
                        Application Number
                    </span>

                    <strong id="modal_application_number">
                        -
                    </strong>

                </div>


                <div>

                    <span>
                        Citizen
                    </span>

                    <strong id="modal_citizen_name">
                        -
                    </strong>

                </div>


                <div>

                    <span>
                        Service
                    </span>

                    <strong id="modal_service_name">
                        -
                    </strong>

                </div>

            </div>


            <div class="form-group">

                <label>
                    Certificate Number
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="certificate_number"
                    placeholder="e.g. NS-CERT-2026-0001"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Certificate PDF
                </label>

                <input
                    type="file"
                    name="certificate_file"
                    accept=".pdf,application/pdf"
                >

                <small>
                    Only PDF files are allowed. Maximum size: 5 MB.
                </small>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-modal"
                    onclick="closeIssueModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    name="issue_certificate"
                    class="issue-submit"
                >
                    Issue Certificate
                </button>

            </div>


        </form>

    </div>

</div>


<script>

function openIssueModal(
    applicationId,
    applicationNumber,
    citizenName,
    serviceName
) {

    document.getElementById(
        "application_id"
    ).value = applicationId;


    document.getElementById(
        "modal_application_number"
    ).textContent = applicationNumber;


    document.getElementById(
        "modal_citizen_name"
    ).textContent = citizenName;


    document.getElementById(
        "modal_service_name"
    ).textContent = serviceName;


    document.getElementById(
        "issueModal"
    ).classList.add("show");
}


function closeIssueModal() {

    document.getElementById(
        "issueModal"
    ).classList.remove("show");
}


window.onclick = function(event) {

    const modal =
        document.getElementById("issueModal");

    if (event.target === modal) {

        closeIssueModal();
    }
};

</script>


</body>

</html>