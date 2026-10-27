
<?php
require_once "db.php";

// Fetch total records from each table
$citizenCount = $pdo->query(
    "SELECT COUNT(*) FROM Citizen"
)->fetchColumn();

$propertyCount = $pdo->query(
    "SELECT COUNT(*) FROM Property"
)->fetchColumn();

$applicationCount = $pdo->query(
    "SELECT COUNT(*) FROM Building_Application"
)->fetchColumn();

$inspectionCount = $pdo->query(
    "SELECT COUNT(*) FROM Site_Inspection"
)->fetchColumn();

$approvalCount = $pdo->query(
    "SELECT COUNT(*) FROM Building_Approval"
)->fetchColumn();

$engineerCount = $pdo->query(
    "SELECT COUNT(*) FROM Engineer"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Building Approval System</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --navy: #17365d;
            --navy-light: #244b78;
            --blue: #4169a1;
            --background: #f4f7fc;
            --text: #263445;
            --muted: #718096;
            --border: #e4eaf2;
            --white: #ffffff;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
        }

        /* Header */
        header {
            background: linear-gradient(
                120deg,
                var(--navy),
                var(--navy-light)
            );
            color: var(--white);
            padding: 30px 6%;
            position: relative;
            overflow: hidden;
        }

        header::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: 5%;
            top: -110px;
            border: 35px solid #ffffff12;
            border-radius: 50%;
        }

        .header-content {
            max-width: 1200px;
            margin: auto;
            position: relative;
            z-index: 1;
        }

        .brand {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c5d7f1;
            margin-bottom: 12px;
            font-weight: 600;
        }

        header h1 {
            font-size: clamp(24px, 3vw, 34px);
            margin-bottom: 10px;
            line-height: 1.3;
        }

        header p {
            color: #d9e5f5;
            font-size: 15px;
        }

        /* Navigation */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 3px 12px #17365d08;
            padding: 0 6%;
        }

        .nav-inner {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        nav a {
            display: inline-block;
            padding: 18px 13px;
            color: #53657b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: 0.2s ease;
        }

        nav a:hover,
        nav a.active {
            color: var(--navy);
            border-bottom-color: var(--blue);
            background: #f7f9fd;
        }

        /* Main layout */
        main {
            max-width: 1200px;
            margin: 38px auto;
            padding: 0 24px;
        }

        .intro {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 27px;
        }

        .intro h2 {
            font-size: 25px;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .intro p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .overview-label {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 20px;
            background: #e7eef9;
            color: var(--navy);
            font-size: 12px;
            font-weight: 700;
        }

        /* Statistics cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(230px, 1fr)
            );
            gap: 20px;
        }

        .card {
            --accent: #4169a1;

            background: var(--white);
            padding: 25px;
            border: 1px solid var(--border);
            border-top: 4px solid var(--accent);
            border-radius: 14px;
            box-shadow: 0 4px 15px #17365d08;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px #17365d12;
        }

        .card.citizens {
            --accent: #4169a1;
        }

        .card.properties {
            --accent: #278779;
        }

        .card.applications {
            --accent: #b7812d;
        }

        .card.inspections {
            --accent: #8064b3;
        }

        .card.approvals {
            --accent: #32845a;
        }

        .card.engineers {
            --accent: #c16a50;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .card h3 {
            font-size: 14px;
            font-weight: 600;
            color: #637187;
        }

        .card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: color-mix(
                in srgb,
                var(--accent) 12%,
                white
            );
            color: var(--accent);
            font-size: 21px;
            font-weight: 700;
        }

        .number {
            font-size: 36px;
            font-weight: 750;
            color: var(--navy);
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .card-description {
            color: #8793a5;
            font-size: 12px;
            margin-top: 10px;
        }

        /* Quick access */
        .quick-access {
            margin-top: 38px;
        }

        .section-heading {
            margin-bottom: 19px;
        }

        .section-heading h2 {
            font-size: 21px;
            color: var(--navy);
            margin-bottom: 7px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(210px, 1fr)
            );
            gap: 14px;
        }

        .action-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 11px;
            color: var(--navy);
            text-decoration: none;
            font-size: 14px;
            font-weight: 650;
            transition: 0.2s ease;
        }

        .action-link:hover {
            border-color: #9aafcc;
            background: #fafdff;
            transform: translateY(-2px);
        }

        .action-link .arrow {
            color: var(--blue);
            font-size: 20px;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 28px 20px;
            color: #8591a3;
            font-size: 13px;
            border-top: 1px solid var(--border);
            margin-top: 45px;
            background: var(--white);
        }

        @media (max-width: 600px) {
            header {
                padding: 26px 22px;
            }

            nav {
                padding: 0 14px;
            }

            .nav-inner {
                gap: 0;
            }

            nav a {
                padding: 14px 10px;
                font-size: 13px;
            }

            main {
                margin: 27px auto;
                padding: 0 16px;
            }

            .intro h2 {
                font-size: 22px;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .card {
                padding: 21px;
            }

            .actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="header-content">
            <div class="brand">Administrative Portal</div>
            <h1>Building Approval Management System</h1>
            <p>Manage citizens, properties, applications and approvals in one place.</p>
        </div>
    </header>

    <nav>
        <div class="nav-inner">
            <a href="index.php">Home</a>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="citizens.php">Citizens</a>
            <a href="properties.php">Properties</a>
            <a href="applications.php">Applications</a>
            <a href="inspections.php">Inspections</a>
            <a href="approvals.php">Approvals</a>
            <a href="engineers.php">Engineers</a>
        </div>
    </nav>

    <main>

        <section class="intro">
            <div>
                <h2>Dashboard Overview</h2>
                <p>
                    View a summary of the records currently stored
                    in your database.
                </p>
            </div>

            <span class="overview-label">DATABASE SUMMARY</span>
        </section>

        <section class="cards">

            <div class="card citizens">
                <div class="card-top">
                    <h3>Total Citizens</h3>
                    <div class="card-icon" aria-hidden="true">C</div>
                </div>
                <div class="number">
                    <?= htmlspecialchars((string)$citizenCount) ?>
                </div>
                <p class="card-description">Registered citizens</p>
            </div>

            <div class="card properties">
                <div class="card-top">
                    <h3>Total Properties</h3>
                    <div class="card-icon" aria-hidden="true">P</div>
                </div>
                <div class="number">
                    <?= htmlspecialchars((string)$propertyCount) ?>
                </div>
                <p class="card-description">Recorded properties</p>
            </div>

            <div class="card applications">
                <div class="card-top">
                    <h3>Building Applications</h3>
                    <div class="card-icon" aria-hidden="true">A</div>
                </div>
                <div class="number">
                    <?= htmlspecialchars((string)$applicationCount) ?>
                </div>
                <p class="card-description">Submitted applications</p>
            </div>

            <div class="card inspections">
                <div class="card-top">
                    <h3>Site Inspections</h3>
                    <div class="card-icon" aria-hidden="true">I</div>
                </div>
                <div class="number">
                    <?= htmlspecialchars((string)$inspectionCount) ?>
                </div>
                <p class="card-description">Recorded inspections</p>
            </div>

            <div class="card approvals">
                <div class="card-top">
                    <h3>Building Approvals</h3>
                    <div class="card-icon" aria-hidden="true">✓</div>
                </div>
                <div class="number">
                    <?= htmlspecialchars((string)$approvalCount) ?>
                </div>
                <p class="card-description">Approval records</p>
            </div>

            <div class="card engineers">
                <div class="card-top">
                    <h3>Total Engineers</h3>
                    <div class="card-icon" aria-hidden="true">E</div>
                </div>
                <div class="number">
                    <?= htmlspecialchars((string)$engineerCount) ?>
                </div>
                <p class="card-description">Registered engineers</p>
            </div>

        </section>

        <section class="quick-access">
            <div class="section-heading">
                <h2>Quick Access</h2>
                <p>Open a module to add records or view existing information.</p>
            </div>

            <div class="actions">
                <a href="citizens.php" class="action-link">
                    <span>Manage Citizens</span>
                    <span class="arrow" aria-hidden="true">→</span>
                </a>

                <a href="properties.php" class="action-link">
                    <span>Manage Properties</span>
                    <span class="arrow" aria-hidden="true">→</span>
                </a>

                <a href="applications.php" class="action-link">
                    <span>Building Applications</span>
                    <span class="arrow" aria-hidden="true">→</span>
                </a>

                <a href="inspections.php" class="action-link">
                    <span>Site Inspections</span>
                    <span class="arrow" aria-hidden="true">→</span>
                </a>

                <a href="approvals.php" class="action-link">
                    <span>Building Approvals</span>
                    <span class="arrow" aria-hidden="true">→</span>
                </a>

                <a href="engineers.php" class="action-link">
                    <span>Manage Engineers</span>
                    <span class="arrow" aria-hidden="true">→</span>
                </a>
            </div>
        </section>

    </main>

    <footer>
        Building Approval Management System
        <span aria-hidden="true"> | </span>
        RDBMS Project
    </footer>

</body>
</html>
