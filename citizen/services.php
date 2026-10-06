<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];


/* =========================================================
   FETCH USER
========================================================= */

$stmt = $conn->prepare(
    "SELECT full_name
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   FETCH ACTIVE SERVICES
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        id,
        service_name,
        description,
        required_documents
     FROM services
     WHERE status = 'Active'
     ORDER BY id ASC"
);

$stmt->execute();

$services_result = $stmt->get_result();

$stmt->close();


/* =========================================================
   USER INITIAL
========================================================= */

$first_letter = strtoupper(
    substr($user["full_name"], 0, 1)
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

    <title>Government Services - Nagar Sewa</title>

    <link
        rel="stylesheet"
        href="services.css"
    >

</head>

<body>


<div class="page-wrapper">


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
                    Citizen Portal
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">


            <a href="dashboard.php">

                <span>🏠</span>

                Dashboard

            </a>


            <a
                href="services.php"
                class="active"
            >

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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             HEADER
        ================================================== -->

        <header class="top-header">


            <div>

                <h1>
                    Government Services
                </h1>

                <p>
                    Apply for municipal and government services online.
                </p>

            </div>


            <div class="user-info">


                <div class="user-avatar">

                    <?php
                    echo htmlspecialchars(
                        $first_letter
                    );
                    ?>

                </div>


                <div>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $user["full_name"]
                        );
                        ?>

                    </strong>

                    <small>
                        Citizen
                    </small>

                </div>


            </div>


        </header>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="content">


            <!-- =================================================
                 PAGE INTRODUCTION
            ================================================== -->

            <div class="page-intro">

                <div>

                    <h2>
                        Available Services
                    </h2>

                    <p>
                        Select a service below to view details
                        and submit your application.
                    </p>

                </div>

                <div class="service-count">

                    <strong>
                        <?php
                        echo $services_result->num_rows;
                        ?>
                    </strong>

                    <span>
                        Services Available
                    </span>

                </div>

            </div>


            <!-- =================================================
                 SERVICE GRID
            ================================================== -->

            <div class="services-grid">


                <?php if ($services_result->num_rows > 0): ?>


                    <?php

                    while (
                        $service =
                        $services_result->fetch_assoc()
                    ):

                        $service_name =
                            strtolower(
                                $service["service_name"]
                            );

                        $icon = "📄";

                        if (
                            strpos(
                                $service_name,
                                "birth"
                            ) !== false
                        ) {
                            $icon = "👶";
                        } elseif (
                            strpos(
                                $service_name,
                                "death"
                            ) !== false
                        ) {
                            $icon = "🕊️";
                        } elseif (
                            strpos(
                                $service_name,
                                "marriage"
                            ) !== false
                        ) {
                            $icon = "💍";
                        } elseif (
                            strpos(
                                $service_name,
                                "migration"
                            ) !== false
                            ||
                            strpos(
                                $service_name,
                                "residence"
                            ) !== false
                        ) {
                            $icon = "🏠";
                        }

                    ?>


                        <div class="service-card">


                            <div class="service-card-top">


                                <div class="service-icon">

                                    <?php
                                    echo $icon;
                                    ?>

                                </div>


                                <span class="active-badge">
                                    Available
                                </span>


                            </div>


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $service["service_name"]
                                );
                                ?>

                            </h3>


                            <p class="service-description">

                                <?php
                                echo htmlspecialchars(
                                    $service["description"]
                                );
                                ?>

                            </p>


                            <div class="documents-section">


                                <strong>
                                    Required Documents
                                </strong>


                                <?php

                                $documents =
                                    explode(
                                        ",",
                                        $service[
                                            "required_documents"
                                        ]
                                    );

                                ?>


                                <ul>

                                    <?php foreach (
                                        $documents
                                        as $document
                                    ): ?>

                                        <?php
                                        $document =
                                            trim($document);

                                        if (
                                            $document === ""
                                        ) {
                                            continue;
                                        }
                                        ?>

                                        <li>

                                            <span>
                                                ✓
                                            </span>

                                            <?php
                                            echo htmlspecialchars(
                                                $document
                                            );
                                            ?>

                                        </li>

                                    <?php endforeach; ?>

                                </ul>


                            </div>


                            <a
                                href="apply-service.php?service_id=<?php echo (int) $service["id"]; ?>"
                                class="apply-button"
                            >

                                Apply Now

                                <span>
                                    →
                                </span>

                            </a>


                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            📋
                        </div>

                        <h3>
                            No Services Available
                        </h3>

                        <p>
                            There are currently no active
                            government services.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 HELP SECTION
            ================================================== -->

            <div class="help-card">


                <div class="help-icon">
                    💡
                </div>


                <div class="help-content">

                    <h3>
                        Need Help?
                    </h3>

                    <p>
                        Make sure you have all the required
                        documents before starting your application.
                        You can track your application status from
                        the Track Application section.
                    </p>

                </div>


                <a
                    href="complaints.php"
                    class="help-button"
                >
                    Contact Support
                </a>


            </div>


        </div>

    </main>

</div>


</body>

</html>