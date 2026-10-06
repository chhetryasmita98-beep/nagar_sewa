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
   DELETE USER
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {

    $user_id = intval($_POST["user_id"]);

    if ($user_id > 0) {

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $success_message = "User deleted successfully.";
        } else {
            $error_message = "Unable to delete user.";
        }

        $stmt->close();
    }
}


/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


/* =========================
   FETCH USERS
========================= */

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            id,
            full_name,
            email,
            phone,
            address,
            created_at
        FROM users
        WHERE
            full_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR address LIKE ?
        ORDER BY created_at DESC
    ");

    $stmt->bind_param(
        "ssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $users = $stmt->get_result();

} else {

    $users = $conn->query("
        SELECT
            id,
            full_name,
            email,
            phone,
            address,
            created_at
        FROM users
        ORDER BY created_at DESC
    ");
}


/* =========================
   STATISTICS
========================= */

// Total users
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");

$total_users = $result->fetch_assoc()["total"];


// Users registered today
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE DATE(created_at) = CURDATE()
");

$today_users = $result->fetch_assoc()["total"];


// Users registered this month
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE
        MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
");

$month_users = $result->fetch_assoc()["total"];


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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users - Nagar Sewa Admin</title>

    <link rel="stylesheet" href="users.css">

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

            <a href="dashboard.php">
                <span>📊</span>
                Dashboard
            </a>

            <a href="users.php" class="active">
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

                <h1>Users</h1>

                <p>
                    Manage registered Nagar Sewa citizens.
                </p>

            </div>


            <div class="admin-info">

                <div class="admin-avatar">

                    <?php
                    echo htmlspecialchars($admin_initial);
                    ?>

                </div>

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars($admin_name);
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


            <!-- ================= ALERTS ================= -->

            <?php if ($success_message): ?>

                <div class="alert success">

                    <span>✓</span>

                    <?php
                    echo htmlspecialchars($success_message);
                    ?>

                </div>

            <?php endif; ?>


            <?php if ($error_message): ?>

                <div class="alert error">

                    <span>!</span>

                    <?php
                    echo htmlspecialchars($error_message);
                    ?>

                </div>

            <?php endif; ?>


            <!-- ================= STATISTICS ================= -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon blue">
                        👥
                    </div>

                    <div class="stat-details">

                        <span>Total Users</span>

                        <strong>
                            <?php echo $total_users; ?>
                        </strong>

                        <small>
                            Registered citizens
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        🆕
                    </div>

                    <div class="stat-details">

                        <span>Today's Users</span>

                        <strong>
                            <?php echo $today_users; ?>
                        </strong>

                        <small>
                            Registered today
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        📅
                    </div>

                    <div class="stat-details">

                        <span>This Month</span>

                        <strong>
                            <?php echo $month_users; ?>
                        </strong>

                        <small>
                            Registered this month
                        </small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon purple">
                        🏛️
                    </div>

                    <div class="stat-details">

                        <span>Citizen Portal</span>

                        <strong>
                            Active
                        </strong>

                        <small>
                            Registration system
                        </small>

                    </div>

                </div>


            </div>


            <!-- ================= USER TABLE CARD ================= -->

            <div class="card">


                <div class="card-header">

                    <div>

                        <h2>Registered Citizens</h2>

                        <p>
                            View and manage citizens registered on Nagar Sewa.
                        </p>

                    </div>


                    <div class="total-label">

                        Total:
                        <strong>
                            <?php echo $total_users; ?>
                        </strong>

                    </div>

                </div>


                <!-- SEARCH -->

                <div class="search-section">

                    <form method="GET" action="users.php" class="search-form">

                        <div class="search-box">

                            <span>🔍</span>

                            <input
                                type="text"
                                name="search"
                                placeholder="Search by name, email, phone or address..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >

                        </div>


                        <button type="submit" class="search-button">
                            Search
                        </button>


                        <?php if ($search !== ""): ?>

                            <a href="users.php" class="clear-button">
                                Clear
                            </a>

                        <?php endif; ?>

                    </form>

                </div>


                <!-- TABLE -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Citizen</th>

                                <th>Email</th>

                                <th>Phone</th>

                                <th>Address</th>

                                <th>Registered</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($users && $users->num_rows > 0): ?>

                            <?php
                            $counter = 1;
                            ?>

                            <?php while ($user = $users->fetch_assoc()): ?>

                                <tr>

                                    <!-- NUMBER -->

                                    <td>
                                        <?php echo $counter++; ?>
                                    </td>


                                    <!-- CITIZEN -->

                                    <td>

                                        <div class="citizen-cell">

                                            <div class="user-avatar-small">

                                                <?php

                                                echo htmlspecialchars(
                                                    strtoupper(
                                                        substr(
                                                            $user["full_name"],
                                                            0,
                                                            1
                                                        )
                                                    )
                                                );

                                                ?>

                                            </div>


                                            <div class="citizen-details">

                                                <strong>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $user["full_name"]
                                                    );
                                                    ?>
                                                </strong>

                                                <span>
                                                    Citizen
                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <span class="email-text">

                                            <?php
                                            echo htmlspecialchars(
                                                $user["email"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $user["phone"]
                                        );
                                        ?>

                                    </td>


                                    <!-- ADDRESS -->

                                    <td>

                                        <span class="address-text">

                                            <?php

                                            echo $user["address"]
                                                ? htmlspecialchars(
                                                    $user["address"]
                                                )
                                                : "Not provided";

                                            ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <span class="date-text">

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $user["created_at"]
                                                )
                                            );

                                            ?>

                                        </span>

                                        <small>

                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $user["created_at"]
                                                )
                                            );

                                            ?>

                                        </small>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <form
                                            method="POST"
                                            action="users.php"
                                            onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?php echo $user["id"]; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_user"
                                                class="delete-button"
                                            >
                                                🗑️ Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7">

                                    <div class="empty-state">

                                        <div class="empty-icon">
                                            👥
                                        </div>

                                        <h3>
                                            No users found
                                        </h3>

                                        <p>

                                            <?php

                                            if ($search !== "") {
                                                echo "No users match your search.";
                                            } else {
                                                echo "No citizens have registered yet.";
                                            }

                                            ?>

                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


            </div>


        </div>

    </main>

</div>

</body>

</html>