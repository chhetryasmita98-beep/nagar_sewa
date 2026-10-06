<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$admin_name = $_SESSION["admin_name"] ?? "Administrator";
$admin_initial = strtoupper(substr($admin_name, 0, 1));

/* =========================
   DASHBOARD STATISTICS
========================= */

// Total users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$total_users = $result->fetch_assoc()["total"];

// Total services
$result = $conn->query("SELECT COUNT(*) AS total FROM services");
$total_services = $result->fetch_assoc()["total"];

// Total applications
$result = $conn->query("SELECT COUNT(*) AS total FROM applications");
$total_applications = $result->fetch_assoc()["total"];

// Pending applications
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM applications 
    WHERE status = 'Pending'
");
$pending_applications = $result->fetch_assoc()["total"];

// Under review
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM applications 
    WHERE status = 'Under Review'
");
$under_review = $result->fetch_assoc()["total"];

// Approved applications
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM applications 
    WHERE status = 'Approved'
");
$approved_applications = $result->fetch_assoc()["total"];

// Rejected applications
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM applications 
    WHERE status = 'Rejected'
");
$rejected_applications = $result->fetch_assoc()["total"];

// Complaints
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM complaints
");
$total_complaints = $result->fetch_assoc()["total"];

// Pending complaints
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM complaints
    WHERE status = 'Pending'
");
$pending_complaints = $result->fetch_assoc()["total"];

// Certificates
$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM certificates
");
$total_certificates = $result->fetch_assoc()["total"];


/* =========================
   RECENT APPLICATIONS
========================= */

$recent_applications = $conn->query("
    SELECT 
        a.id,
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
    LIMIT 8
");


/* =========================
   RECENT COMPLAINTS
========================= */

$recent_complaints = $conn->query("
    SELECT 
        c.id,
        c.subject,
        c.status,
        c.created_at,
        u.full_name
    FROM complaints c
    INNER JOIN users u 
        ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Nagar Sewa</title>

    <link rel="stylesheet" href="dashboard.css">

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
                <span>Admin Panel</span>
            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php" class="active">
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


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content">

        <!-- HEADER -->

        <header class="top-header">

            <div>

                <h1>Admin Dashboard</h1>

                <p>
                    Manage Nagar Sewa services, applications and citizens.
                </p>

            </div>


            <div class="admin-info">

                <div class="admin-avatar">
                    <?php echo htmlspecialchars($admin_initial); ?>
                </div>

                <div>

                    <strong>
                        <?php echo htmlspecialchars($admin_name); ?>
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <div class="content">


            <!-- ================= WELCOME ================= -->

            <div class="welcome-card">

                <div>

                    <h2>
                        Welcome back, <?php echo htmlspecialchars($admin_name); ?>! 👋
                    </h2>

                    <p>
                        Here's an overview of your Nagar Sewa administration system.
                    </p>

                </div>

                <div class="welcome-icon">
                    🏛️
                </div>

            </div>


            <!-- ================= STATISTICS ================= -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        👥
                    </div>

                    <div class="stat-details">

                        <span>Total Citizens</span>

                        <strong>
                            <?php echo $total_users; ?>
                        </strong>

                        <small>
                            Registered users
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        📋
                    </div>

                    <div class="stat-details">

                        <span>Services</span>

                        <strong>
                            <?php echo $total_services; ?>
                        </strong>

                        <small>
                            Available services
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        📝
                    </div>

                    <div class="stat-details">

                        <span>Applications</span>

                        <strong>
                            <?php echo $total_applications; ?>
                        </strong>

                        <small>
                            Total applications
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        📜
                    </div>

                    <div class="stat-details">

                        <span>Certificates</span>

                        <strong>
                            <?php echo $total_certificates; ?>
                        </strong>

                        <small>
                            Issued certificates
                        </small>

                    </div>

                </div>


            </div>


            <!-- ================= APPLICATION STATUS ================= -->

            <div class="section-title">

                <h2>Application Overview</h2>

                <a href="applications.php">
                    View All Applications →
                </a>

            </div>


            <div class="status-grid">


                <div class="status-card pending">

                    <div class="status-icon">
                        ⏳
                    </div>

                    <div>

                        <span>Pending</span>

                        <strong>
                            <?php echo $pending_applications; ?>
                        </strong>

                    </div>

                </div>


                <div class="status-card review">

                    <div class="status-icon">
                        🔍
                    </div>

                    <div>

                        <span>Under Review</span>

                        <strong>
                            <?php echo $under_review; ?>
                        </strong>

                    </div>

                </div>


                <div class="status-card approved">

                    <div class="status-icon">
                        ✓
                    </div>

                    <div>

                        <span>Approved</span>

                        <strong>
                            <?php echo $approved_applications; ?>
                        </strong>

                    </div>

                </div>


                <div class="status-card rejected">

                    <div class="status-icon">
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


            <!-- ================= RECENT APPLICATIONS ================= -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Recent Applications</h2>

                        <p>
                            Latest citizen applications submitted to Nagar Sewa.
                        </p>

                    </div>

                    <a href="applications.php" class="view-all">
                        View All
                    </a>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Application No.</th>

                                <th>Citizen</th>

                                <th>Service</th>

                                <th>Date</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($recent_applications && $recent_applications->num_rows > 0): ?>

                            <?php while ($application = $recent_applications->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <span class="application-number">
                                            <?php echo htmlspecialchars($application["application_number"]); ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="citizen-cell">

                                            <div class="small-avatar">
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

                                            <span>
                                                <?php echo htmlspecialchars($application["full_name"]); ?>
                                            </span>

                                        </div>

                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($application["service_name"]); ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($application["applied_at"])
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $application["status"])); ?>">

                                            <?php echo htmlspecialchars($application["status"]); ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="5">

                                    <div class="empty-state">

                                        <div>📝</div>

                                        <h3>No applications found</h3>

                                        <p>
                                            No citizen applications have been submitted yet.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- ================= BOTTOM GRID ================= -->

            <div class="bottom-grid">


                <!-- COMPLAINT SUMMARY -->

                <div class="card">

                    <div class="card-header">

                        <div>

                            <h2>Complaints</h2>

                            <p>
                                Recent citizen complaints.
                            </p>

                        </div>

                        <a href="complaints.php" class="view-all">
                            View All
                        </a>

                    </div>


                    <div class="complaint-summary">

                        <div class="complaint-stat">

                            <span>Total Complaints</span>

                            <strong>
                                <?php echo $total_complaints; ?>
                            </strong>

                        </div>


                        <div class="complaint-stat pending-stat">

                            <span>Pending Complaints</span>

                            <strong>
                                <?php echo $pending_complaints; ?>
                            </strong>

                        </div>

                    </div>


                    <div class="complaint-list">

                        <?php if ($recent_complaints && $recent_complaints->num_rows > 0): ?>

                            <?php while ($complaint = $recent_complaints->fetch_assoc()): ?>

                                <div class="complaint-item">

                                    <div class="complaint-icon">
                                        💬
                                    </div>

                                    <div class="complaint-info">

                                        <strong>
                                            <?php echo htmlspecialchars($complaint["subject"]); ?>
                                        </strong>

                                        <span>
                                            <?php echo htmlspecialchars($complaint["full_name"]); ?>
                                        </span>

                                    </div>

                                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $complaint["status"])); ?>">

                                        <?php echo htmlspecialchars($complaint["status"]); ?>

                                    </span>

                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="empty-small">
                                No complaints found.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- QUICK ACTIONS -->

                <div class="card">

                    <div class="card-header">

                        <div>

                            <h2>Quick Actions</h2>

                            <p>
                                Frequently used admin functions.
                            </p>

                        </div>

                    </div>


                    <div class="quick-actions">

                        <a href="applications.php" class="quick-action">

                            <div class="quick-icon blue">
                                📝
                            </div>

                            <div>
                                <strong>Manage Applications</strong>
                                <span>Review citizen applications</span>
                            </div>

                            <b>→</b>

                        </a>


                        <a href="users.php" class="quick-action">

                            <div class="quick-icon green">
                                👥
                            </div>

                            <div>
                                <strong>Manage Users</strong>
                                <span>View registered citizens</span>
                            </div>

                            <b>→</b>

                        </a>


                        <a href="services.php" class="quick-action">

                            <div class="quick-icon orange">
                                📋
                            </div>

                            <div>
                                <strong>Manage Services</strong>
                                <span>Add or update services</span>
                            </div>

                            <b>→</b>

                        </a>


                        <a href="notices.php" class="quick-action">

                            <div class="quick-icon purple">
                                📢
                            </div>

                            <div>
                                <strong>Manage Notices</strong>
                                <span>Publish public notices</span>
                            </div>

                            <b>→</b>

                        </a>


                        <a href="reports.php" class="quick-action">

                            <div class="quick-icon red">
                                📈
                            </div>

                            <div>
                                <strong>View Reports</strong>
                                <span>Analyze system reports</span>
                            </div>

                            <b>→</b>

                        </a>

                    </div>

                </div>


            </div>


        </div>

    </main>

</div>

</body>
</html>