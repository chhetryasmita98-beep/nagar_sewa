<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$admin_name = $_SESSION["admin_name"] ?? "Admin";

/* =========================
   BASIC STATISTICS
========================= */

$total_users = 0;
$total_services = 0;
$total_applications = 0;
$total_certificates = 0;
$total_complaints = 0;

$pending_applications = 0;
$under_review = 0;
$approved_applications = 0;
$rejected_applications = 0;

$pending_complaints = 0;
$resolved_complaints = 0;


/* USERS */

$result = $conn->query("SELECT COUNT(*) AS total FROM users");

if ($result) {
    $row = $result->fetch_assoc();
    $total_users = (int)$row["total"];
}


/* SERVICES */

$result = $conn->query("SELECT COUNT(*) AS total FROM services");

if ($result) {
    $row = $result->fetch_assoc();
    $total_services = (int)$row["total"];
}


/* APPLICATIONS */

$result = $conn->query("SELECT COUNT(*) AS total FROM applications");

if ($result) {
    $row = $result->fetch_assoc();
    $total_applications = (int)$row["total"];
}


/* CERTIFICATES */

$result = $conn->query("SELECT COUNT(*) AS total FROM certificates");

if ($result) {
    $row = $result->fetch_assoc();
    $total_certificates = (int)$row["total"];
}


/* COMPLAINTS */

$result = $conn->query("SELECT COUNT(*) AS total FROM complaints");

if ($result) {
    $row = $result->fetch_assoc();
    $total_complaints = (int)$row["total"];
}


/* APPLICATION STATUS */

$result = $conn->query(
    "SELECT
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Under Review') AS under_review,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Rejected') AS rejected
     FROM applications"
);

if ($result) {

    $row = $result->fetch_assoc();

    $pending_applications = (int)($row["pending"] ?? 0);
    $under_review = (int)($row["under_review"] ?? 0);
    $approved_applications = (int)($row["approved"] ?? 0);
    $rejected_applications = (int)($row["rejected"] ?? 0);
}


/* COMPLAINT STATUS */

$result = $conn->query(
    "SELECT
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Resolved') AS resolved
     FROM complaints"
);

if ($result) {

    $row = $result->fetch_assoc();

    $pending_complaints = (int)($row["pending"] ?? 0);
    $resolved_complaints = (int)($row["resolved"] ?? 0);
}


/* =========================
   ACTIVE SERVICES
========================= */

$active_services = 0;
$inactive_services = 0;

$result = $conn->query(
    "SELECT
        SUM(status = 'Active') AS active,
        SUM(status = 'Inactive') AS inactive
     FROM services"
);

if ($result) {

    $row = $result->fetch_assoc();

    $active_services = (int)($row["active"] ?? 0);
    $inactive_services = (int)($row["inactive"] ?? 0);
}


/* =========================
   MONTHLY APPLICATIONS
========================= */

$monthly_labels = [];
$monthly_values = [];

for ($i = 5; $i >= 0; $i--) {

    $date = new DateTime();

    $date->modify("-$i month");

    $month_number = $date->format("m");
    $year = $date->format("Y");

    $monthly_labels[] = $date->format("M Y");

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM applications
         WHERE MONTH(applied_at) = ?
         AND YEAR(applied_at) = ?"
    );

    $stmt->bind_param(
        "ii",
        $month_number,
        $year
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $monthly_values[] = (int)($row["total"] ?? 0);

    $stmt->close();
}


/* =========================
   SERVICE-WISE APPLICATIONS
========================= */

$service_names = [];
$service_application_counts = [];

$result = $conn->query(
    "SELECT
        s.service_name,
        COUNT(a.id) AS total
     FROM services s
     LEFT JOIN applications a
        ON s.id = a.service_id
     GROUP BY s.id, s.service_name
     ORDER BY total DESC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $service_names[] = $row["service_name"];

        $service_application_counts[] =
            (int)$row["total"];
    }
}


/* =========================
   RECENT APPLICATIONS
========================= */

$recent_applications = $conn->query(
    "SELECT
        a.application_number,
        a.status,
        a.applied_at,
        u.full_name,
        s.service_name
     FROM applications a
     INNER JOIN users u
        ON a.user_id = u.id
     INNER JOIN services s
        ON a.service_id = s.id
     ORDER BY a.applied_at DESC
     LIMIT 10"
);


/* =========================
   RECENT COMPLAINTS
========================= */

$recent_complaints = $conn->query(
    "SELECT
        c.subject,
        c.status,
        c.created_at,
        u.full_name
     FROM complaints c
     INNER JOIN users u
        ON c.user_id = u.id
     ORDER BY c.created_at DESC
     LIMIT 8"
);


/* =========================
   TODAY'S DATA
========================= */

$today_applications = 0;
$today_users = 0;
$today_certificates = 0;
$today_complaints = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE DATE(applied_at) = CURDATE()"
);

if ($result) {
    $today_applications =
        (int)$result->fetch_assoc()["total"];
}


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE DATE(created_at) = CURDATE()"
);

if ($result) {
    $today_users =
        (int)$result->fetch_assoc()["total"];
}


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM certificates
     WHERE DATE(issued_at) = CURDATE()"
);

if ($result) {
    $today_certificates =
        (int)$result->fetch_assoc()["total"];
}


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM complaints
     WHERE DATE(created_at) = CURDATE()"
);

if ($result) {
    $today_complaints =
        (int)$result->fetch_assoc()["total"];
}


/* =========================
   JSON FOR CHARTS
========================= */

$monthly_labels_json =
    json_encode($monthly_labels);

$monthly_values_json =
    json_encode($monthly_values);

$service_names_json =
    json_encode($service_names);

$service_counts_json =
    json_encode($service_application_counts);

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
        Reports - Nagar Sewa Admin
    </title>

    <link
        rel="stylesheet"
        href="reports.css"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>

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

            <a href="reports.php" class="active">
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


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">

            <div>

                <h1>
                    Reports & Analytics
                </h1>

                <p>
                    Monitor Nagar Sewa system performance
                    and activities
                </p>

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


        <!-- PAGE CONTENT -->

        <section class="page-content">


            <!-- =========================
                 OVERVIEW CARDS
            ========================== -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        👥
                    </div>

                    <div>

                        <span>
                            Total Citizens
                        </span>

                        <h3>
                            <?php
                            echo $total_users;
                            ?>
                        </h3>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        📝
                    </div>

                    <div>

                        <span>
                            Applications
                        </span>

                        <h3>
                            <?php
                            echo $total_applications;
                            ?>
                        </h3>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        📜
                    </div>

                    <div>

                        <span>
                            Certificates
                        </span>

                        <h3>
                            <?php
                            echo $total_certificates;
                            ?>
                        </h3>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        💬
                    </div>

                    <div>

                        <span>
                            Complaints
                        </span>

                        <h3>
                            <?php
                            echo $total_complaints;
                            ?>
                        </h3>

                    </div>

                </div>

            </div>


            <!-- =========================
                 TODAY SUMMARY
            ========================== -->

            <div class="section-title">

                <div>

                    <h2>
                        Today's Activity
                    </h2>

                    <p>
                        Summary of today's system activity
                    </p>

                </div>

            </div>


            <div class="today-grid">


                <div class="today-card">

                    <div class="today-icon">
                        📝
                    </div>

                    <div>

                        <span>
                            Applications Today
                        </span>

                        <strong>
                            <?php
                            echo $today_applications;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="today-card">

                    <div class="today-icon">
                        👤
                    </div>

                    <div>

                        <span>
                            New Citizens Today
                        </span>

                        <strong>
                            <?php
                            echo $today_users;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="today-card">

                    <div class="today-icon">
                        📜
                    </div>

                    <div>

                        <span>
                            Certificates Issued
                        </span>

                        <strong>
                            <?php
                            echo $today_certificates;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="today-card">

                    <div class="today-icon">
                        💬
                    </div>

                    <div>

                        <span>
                            Complaints Today
                        </span>

                        <strong>
                            <?php
                            echo $today_complaints;
                            ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =========================
                 CHARTS
            ========================== -->

            <div class="charts-grid">


                <!-- MONTHLY APPLICATIONS -->

                <div class="chart-card">

                    <div class="chart-header">

                        <div>

                            <h2>
                                Monthly Applications
                            </h2>

                            <p>
                                Application activity
                                over the last 6 months
                            </p>

                        </div>

                    </div>

                    <div class="chart-container">

                        <canvas
                            id="monthlyChart"
                        ></canvas>

                    </div>

                </div>


                <!-- SERVICE APPLICATIONS -->

                <div class="chart-card">

                    <div class="chart-header">

                        <div>

                            <h2>
                                Applications by Service
                            </h2>

                            <p>
                                Applications received
                                for each service
                            </p>

                        </div>

                    </div>

                    <div class="chart-container">

                        <canvas
                            id="serviceChart"
                        ></canvas>

                    </div>

                </div>

            </div>


            <!-- =========================
                 STATUS REPORT
            ========================== -->

            <div class="section-title">

                <div>

                    <h2>
                        Application Status Report
                    </h2>

                    <p>
                        Current application processing status
                    </p>

                </div>

            </div>


            <div class="status-grid">


                <div class="status-card pending">

                    <div class="status-icon">
                        ⏳
                    </div>

                    <div>

                        <span>
                            Pending
                        </span>

                        <strong>
                            <?php
                            echo $pending_applications;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="status-card review">

                    <div class="status-icon">
                        🔍
                    </div>

                    <div>

                        <span>
                            Under Review
                        </span>

                        <strong>
                            <?php
                            echo $under_review;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="status-card approved">

                    <div class="status-icon">
                        ✓
                    </div>

                    <div>

                        <span>
                            Approved
                        </span>

                        <strong>
                            <?php
                            echo $approved_applications;
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="status-card rejected">

                    <div class="status-icon">
                        ✕
                    </div>

                    <div>

                        <span>
                            Rejected
                        </span>

                        <strong>
                            <?php
                            echo $rejected_applications;
                            ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =========================
                 SERVICE & COMPLAINT SUMMARY
            ========================== -->

            <div class="two-column-grid">


                <!-- SERVICE SUMMARY -->

                <div class="report-card">

                    <div class="report-card-header">

                        <div>

                            <h2>
                                Service Summary
                            </h2>

                            <p>
                                Current service availability
                            </p>

                        </div>

                    </div>


                    <div class="summary-list">

                        <div class="summary-item">

                            <div>

                                <span>
                                    Total Services
                                </span>

                                <small>
                                    All registered services
                                </small>

                            </div>

                            <strong>
                                <?php
                                echo $total_services;
                                ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <div>

                                <span>
                                    Active Services
                                </span>

                                <small>
                                    Currently available
                                </small>

                            </div>

                            <strong class="green-text">
                                <?php
                                echo $active_services;
                                ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <div>

                                <span>
                                    Inactive Services
                                </span>

                                <small>
                                    Currently unavailable
                                </small>

                            </div>

                            <strong class="red-text">
                                <?php
                                echo $inactive_services;
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- COMPLAINT SUMMARY -->

                <div class="report-card">

                    <div class="report-card-header">

                        <div>

                            <h2>
                                Complaint Summary
                            </h2>

                            <p>
                                Citizen complaint overview
                            </p>

                        </div>

                    </div>


                    <div class="summary-list">

                        <div class="summary-item">

                            <div>

                                <span>
                                    Total Complaints
                                </span>

                                <small>
                                    All submitted complaints
                                </small>

                            </div>

                            <strong>
                                <?php
                                echo $total_complaints;
                                ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <div>

                                <span>
                                    Pending Complaints
                                </span>

                                <small>
                                    Awaiting action
                                </small>

                            </div>

                            <strong class="orange-text">
                                <?php
                                echo $pending_complaints;
                                ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <div>

                                <span>
                                    Resolved Complaints
                                </span>

                                <small>
                                    Successfully resolved
                                </small>

                            </div>

                            <strong class="green-text">
                                <?php
                                echo $resolved_complaints;
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================
                 RECENT APPLICATIONS
            ========================== -->

            <div class="report-card full-card">

                <div class="report-card-header">

                    <div>

                        <h2>
                            Recent Applications
                        </h2>

                        <p>
                            Latest applications submitted
                            by citizens
                        </p>

                    </div>

                    <a
                        href="applications.php"
                        class="view-all"
                    >
                        View All →
                    </a>

                </div>


                <div class="table-wrapper">

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
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php
                        if (
                            $recent_applications &&
                            $recent_applications->num_rows > 0
                        ):
                        ?>

                            <?php
                            while (
                                $application =
                                $recent_applications->fetch_assoc()
                            ):
                            ?>

                                <tr>

                                    <td>
                                        <strong>
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
                                        <?php
                                        echo htmlspecialchars(
                                            $application[
                                                "full_name"
                                            ]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $application[
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
                                                $application[
                                                    "applied_at"
                                                ]
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status-badge <?php
                                            echo strtolower(
                                                str_replace(
                                                    " ",
                                                    "-",
                                                    $application[
                                                        "status"
                                                    ]
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $application[
                                                    "status"
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-state"
                                >
                                    No applications found.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- =========================
                 RECENT COMPLAINTS
            ========================== -->

            <div class="report-card full-card">

                <div class="report-card-header">

                    <div>

                        <h2>
                            Recent Complaints
                        </h2>

                        <p>
                            Latest citizen complaints
                        </p>

                    </div>

                    <a
                        href="complaints.php"
                        class="view-all"
                    >
                        View All →
                    </a>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                        <tr>

                            <th>
                                Subject
                            </th>

                            <th>
                                Citizen
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php
                        if (
                            $recent_complaints &&
                            $recent_complaints->num_rows > 0
                        ):
                        ?>

                            <?php
                            while (
                                $complaint =
                                $recent_complaints->fetch_assoc()
                            ):
                            ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $complaint[
                                                    "subject"
                                                ]
                                            );
                                            ?>
                                        </strong>

                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $complaint[
                                                "full_name"
                                            ]
                                        );
                                        ?>
                                    </td>

                                    <td>
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
                                    </td>

                                    <td>

                                        <span
                                            class="status-badge complaint-<?php
                                            echo strtolower(
                                                str_replace(
                                                    " ",
                                                    "-",
                                                    $complaint[
                                                        "status"
                                                    ]
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $complaint[
                                                    "status"
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="empty-state"
                                >
                                    No complaints found.
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


<script>

/* =========================
   MONTHLY APPLICATION CHART
========================= */

const monthlyLabels =
    <?php echo $monthly_labels_json; ?>;

const monthlyValues =
    <?php echo $monthly_values_json; ?>;

const monthlyCanvas =
    document.getElementById("monthlyChart");


new Chart(
    monthlyCanvas,
    {
        type: "line",

        data: {

            labels: monthlyLabels,

            datasets: [
                {
                    label: "Applications",

                    data: monthlyValues,

                    borderWidth: 3,

                    fill: false,

                    tension: 0.4,

                    pointRadius: 4,

                    pointHoverRadius: 6
                }
            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    }
);


/* =========================
   SERVICE CHART
========================= */

const serviceNames =
    <?php echo $service_names_json; ?>;

const serviceCounts =
    <?php echo $service_counts_json; ?>;

const serviceCanvas =
    document.getElementById("serviceChart");


new Chart(
    serviceCanvas,
    {
        type: "bar",

        data: {

            labels: serviceNames,

            datasets: [
                {
                    label: "Applications",

                    data: serviceCounts,

                    borderWidth: 1,

                    borderRadius: 5
                }
            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                },

                x: {

                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }

                }

            }

        }

    }
);

</script>

</body>

</html>