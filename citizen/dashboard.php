<?php

session_start();

require_once "../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Total applications
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$total_result = $stmt->get_result()->fetch_assoc();
$total_applications = $total_result["total"];

$stmt->close();


// Pending applications
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE user_id = ?
     AND status IN ('Pending', 'Under Review')"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$pending_result = $stmt->get_result()->fetch_assoc();
$pending_applications = $pending_result["total"];

$stmt->close();


// Approved applications
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE user_id = ?
     AND status = 'Approved'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$approved_result = $stmt->get_result()->fetch_assoc();
$approved_applications = $approved_result["total"];

$stmt->close();


// Rejected applications
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE user_id = ?
     AND status = 'Rejected'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$rejected_result = $stmt->get_result()->fetch_assoc();
$rejected_applications = $rejected_result["total"];

$stmt->close();


// Recent applications
$stmt = $conn->prepare(
    "SELECT
        applications.application_number,
        services.service_name,
        applications.status,
        applications.applied_at
     FROM applications
     INNER JOIN services
     ON applications.service_id = services.id
     WHERE applications.user_id = ?
     ORDER BY applications.applied_at DESC
     LIMIT 5"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$recent_applications = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Citizen Dashboard - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

</head>

<body>

<!-- Sidebar -->

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

        <a
            href="dashboard.php"
            class="active"
        >
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

    <!-- Top Header -->

    <header class="top-header">

    <div>

        <h1>
            Citizen Dashboard
        </h1>

        <p>
            Manage your Nagar Sewa services and applications.
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
    <!-- Welcome -->

    <section class="welcome-box">

        <div>

            <h2>
                Welcome, <?php echo htmlspecialchars($user_name); ?>!
            </h2>

            <p>
                Access government services and manage your
                applications from your dashboard.
            </p>

        </div>

        <a
            href="services.php"
            class="apply-button"
        >
            Apply for Service
        </a>

    </section>


    <!-- Statistics -->

    <section class="statistics">

        <div class="stat-card">

            <div class="stat-icon">
                📋
            </div>

            <div>

                <h3>
                    <?php echo $total_applications; ?>
                </h3>

                <p>Total Applications</p>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <h3>
                    <?php echo $pending_applications; ?>
                </h3>

                <p>Pending Applications</p>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <h3>
                    <?php echo $approved_applications; ?>
                </h3>

                <p>Approved Applications</p>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✕
            </div>

            <div>

                <h3>
                    <?php echo $rejected_applications; ?>
                </h3>

                <p>Rejected Applications</p>

            </div>

        </div>

    </section>


    <!-- Services -->

    <section class="section">

        <div class="section-header">

            <div>

                <h2>Government Services</h2>

                <p>
                    Apply for government services online.
                </p>

            </div>

            <a href="services.php">
                View All
            </a>

        </div>


        <div class="service-grid">

            <div class="service-card">

                <div class="service-icon">
                    👶
                </div>

                <h3>Birth Certificate</h3>

                <p>
                    Apply for a birth certificate online.
                </p>

                <a href="services.php">
                    Apply Now →
                </a>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    📄
                </div>

                <h3>Death Certificate</h3>

                <p>
                    Apply for a death certificate online.
                </p>

                <a href="services.php">
                    Apply Now →
                </a>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    💍
                </div>

                <h3>Marriage Registration</h3>

                <p>
                    Register your marriage online.
                </p>

                <a href="services.php">
                    Apply Now →
                </a>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    🏠
                </div>

                <h3>Migration Certificate</h3>

                <p>
                    Apply for migration or residence services.
                </p>

                <a href="services.php">
                    Apply Now →
                </a>

            </div>

        </div>

    </section>


    <!-- Recent Applications -->

    <section class="section">

        <div class="section-header">

            <div>

                <h2>Recent Applications</h2>

                <p>
                    Your latest service applications.
                </p>

            </div>

            <a href="track-application.php">
                View All
            </a>

        </div>


        <div class="table-container">

            <?php if ($recent_applications->num_rows > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>Application No.</th>

                            <th>Service</th>

                            <th>Status</th>

                            <th>Date</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while (
                        $application =
                        $recent_applications->fetch_assoc()
                    ): ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $application[
                                        "application_number"
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

                                $status =
                                    $application["status"];

                                $status_class =
                                    strtolower(
                                        str_replace(
                                            " ",
                                            "-",
                                            $status
                                        )
                                    );

                                ?>

                                <span
                                    class="status <?php
                                    echo $status_class;
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $status
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <?php
                                echo date(
                                    "M d, Y",
                                    strtotime(
                                        $application[
                                            "applied_at"
                                        ]
                                    )
                                );
                                ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="no-data">

                    <div class="no-data-icon">
                        📋
                    </div>

                    <h3>No Applications Yet</h3>

                    <p>
                        You haven't submitted any applications.
                    </p>

                    <a
                        href="services.php"
                        class="apply-button"
                    >
                        Apply for a Service
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- Quick Actions -->

    <section class="section">

        <div class="section-header">

            <div>

                <h2>Quick Actions</h2>

            </div>

        </div>


        <div class="quick-actions">

            <a href="services.php">
                <span>➕</span>
                <strong>New Application</strong>
                <small>Apply for a service</small>
            </a>

            <a href="track-application.php">
                <span>🔍</span>
                <strong>Track Application</strong>
                <small>Check application status</small>
            </a>

            <a href="notices.php">
                <span>📢</span>
                <strong>View Notices</strong>
                <small>Latest government notices</small>
            </a>

            <a href="complaints.php">
                <span>💬</span>
                <strong>Submit Complaint</strong>
                <small>Send your complaint</small>
            </a>

        </div>

    </section>


    <footer>

        <p>
            &copy; 2026 Nagar Sewa. All Rights Reserved.
        </p>

    </footer>

</main>

</body>

</html>

<?php

$stmt->close();

?>