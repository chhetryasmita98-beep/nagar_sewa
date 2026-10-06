<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$notices = [];

/* Fetch latest notices */
$sql = "
    SELECT
        id,
        title,
        description,
        notice_date,
        created_at
    FROM notices
    ORDER BY notice_date DESC, created_at DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notices - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="notices.css"
    >

</head>

<body>

<div class="page-wrapper">

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

            <a
                href="notices.php"
                class="active"
            >
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


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">

        <!-- Header -->

        <header class="top-header">

            <div>

                <h1>
                    Notices & Announcements
                </h1>

                <p>
                    Stay updated with the latest Nagar Sewa announcements.
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


        <!-- =========================
             CONTENT
        ========================== -->

        <section class="content-area">


            <!-- Notice Banner -->

            <div class="notice-banner">

                <div class="banner-icon">
                    📢
                </div>

                <div>

                    <h2>
                        Important Notices
                    </h2>

                    <p>
                        Check this page regularly for important
                        government service announcements and updates.
                    </p>

                </div>

            </div>


            <!-- Notice List -->

            <div class="notices-container">

                <div class="section-header">

                    <div>

                        <h2>
                            Latest Notices
                        </h2>

                        <p>
                            Official announcements from Nagar Sewa.
                        </p>

                    </div>


                    <div class="notice-count">

                        <?php echo count($notices); ?>

                        Notice(s)

                    </div>

                </div>


                <?php if (count($notices) > 0): ?>

                    <div class="notice-list">

                        <?php foreach ($notices as $notice): ?>

                            <article class="notice-card">

                                <div class="notice-date">

                                    <span class="date-day">

                                        <?php

                                        echo date(
                                            "d",
                                            strtotime(
                                                $notice["notice_date"]
                                            )
                                        );

                                        ?>

                                    </span>

                                    <span class="date-month">

                                        <?php

                                        echo date(
                                            "M",
                                            strtotime(
                                                $notice["notice_date"]
                                            )
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="notice-content">

                                    <div class="notice-top">

                                        <h3>

                                            <?php

                                            echo htmlspecialchars(
                                                $notice["title"]
                                            );

                                            ?>

                                        </h3>

                                        <span class="official-label">
                                            Official Notice
                                        </span>

                                    </div>


                                    <p>

                                        <?php

                                        echo nl2br(
                                            htmlspecialchars(
                                                $notice["description"]
                                            )
                                        );

                                        ?>

                                    </p>


                                    <div class="notice-footer">

                                        <span>

                                            📅 Published:

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $notice["notice_date"]
                                                )
                                            );

                                            ?>

                                        </span>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <!-- Empty Notice -->

                    <div class="empty-state">

                        <div class="empty-icon">
                            📭
                        </div>

                        <h3>
                            No Notices Available
                        </h3>

                        <p>
                            There are currently no government notices.
                            Please check again later.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- Helpful Information -->

            <div class="information-box">

                <div class="information-icon">
                    ℹ️
                </div>

                <div>

                    <h3>
                        Stay Updated
                    </h3>

                    <p>
                        Important announcements regarding government
                        services, application procedures, deadlines,
                        and certificate distribution will be published
                        on this page.
                    </p>

                </div>

            </div>


        </section>

    </main>

</div>

</body>

</html>