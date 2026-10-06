<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$certificate_id = isset($_GET["certificate_id"])
    ? (int) $_GET["certificate_id"]
    : 0;

if ($certificate_id <= 0) {
    die("Invalid certificate.");
}

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.certificate_number,
        c.issued_at,

        a.id AS application_id,
        a.application_number,
        a.application_data,
        a.status,

        u.full_name,
        u.email,
        u.phone,
        u.address,

        s.service_name

    FROM certificates c

    INNER JOIN applications a
        ON c.application_id = a.id

    INNER JOIN users u
        ON a.user_id = u.id

    INNER JOIN services s
        ON a.service_id = s.id

    WHERE c.id = ?
    AND a.user_id = ?
    AND a.status = 'Approved'

    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $certificate_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$certificate = $result->fetch_assoc();

$stmt->close();

if (!$certificate) {
    die("Certificate not found or you do not have permission to view it.");
}

$applicationData = [];

if (!empty($certificate["application_data"])) {
    $applicationData = json_decode(
        $certificate["application_data"],
        true
    );

    if (!is_array($applicationData)) {
        $applicationData = [];
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

    <title>
        Certificate - Nagar Sewa
    </title>

    <link
        rel="stylesheet"
        href="certificate.css"
    >

</head>

<body>

<div class="page-wrapper">

    <div class="certificate-container">

        <div class="certificate-header">

            <div class="logo">
                NS
            </div>

            <div>

                <h1>
                    NAGAR SEWA
                </h1>

                <p>
                    Digital Government Service Portal
                </p>

            </div>

        </div>


        <div class="certificate-title">

            <h2>
                OFFICIAL CERTIFICATE
            </h2>

            <p>
                Government / Municipal Service Certificate
            </p>

        </div>


        <div class="certificate-body">

            <div class="certificate-row">

                <span>
                    Certificate Number
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $certificate["certificate_number"]
                    );
                    ?>
                </strong>

            </div>


            <div class="certificate-row">

                <span>
                    Application Number
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $certificate["application_number"]
                    );
                    ?>
                </strong>

            </div>


            <div class="certificate-row">

                <span>
                    Service
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $certificate["service_name"]
                    );
                    ?>
                </strong>

            </div>


            <div class="certificate-row">

                <span>
                    Citizen Name
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $certificate["full_name"]
                    );
                    ?>
                </strong>

            </div>


            <div class="certificate-row">

                <span>
                    Address
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $certificate["address"]
                    );
                    ?>
                </strong>

            </div>


            <?php if (!empty($applicationData)): ?>

                <?php foreach ($applicationData as $key => $value): ?>

                    <?php if (!is_array($value)): ?>

                        <div class="certificate-row">

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            "_",
                                            " ",
                                            $key
                                        )
                                    )
                                );
                                ?>
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $value
                                );
                                ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            <?php endif; ?>


            <div class="certificate-row">

                <span>
                    Issue Date
                </span>

                <strong>
                    <?php
                    echo date(
                        "d M Y",
                        strtotime(
                            $certificate["issued_at"]
                        )
                    );
                    ?>
                </strong>

            </div>

        </div>


        <div class="certificate-status">

            <span>
                ✓
            </span>

            <strong>
                APPROVED & VERIFIED
            </strong>

        </div>


        <div class="certificate-footer">

            <div>

                <p>
                    This is a digitally issued certificate
                    generated through Nagar Sewa.
                </p>

                <p>
                    Certificate No:
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $certificate["certificate_number"]
                        );
                        ?>
                    </strong>
                </p>

            </div>

            <div class="signature">

                <div class="signature-line"></div>

                <strong>
                    Authorized Officer
                </strong>

                <small>
                    Nagar Sewa
                </small>

            </div>

        </div>

    </div>


    <div class="certificate-actions">

        <button
            onclick="window.print()"
            class="print-btn"
        >
            🖨 Print / Save as PDF
        </button>

        <a
            href="track-application.php?application_id=<?php echo $certificate["application_id"]; ?>"
            class="back-btn"
        >
            ← Back to Application
        </a>

    </div>

</div>

</body>

</html>