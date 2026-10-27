<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "db.php";

$message = "";
$messageType = "";

// Displays messages after deletion
if (isset($_SESSION["delete_message"])) {
    $message = $_SESSION["delete_message"];
    $messageType = $_SESSION["delete_status"] ?? "info";

    unset($_SESSION["delete_message"], $_SESSION["delete_status"]);
}

// Handle adding a new approval
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_approval"])) {

    $approvalDate = trim($_POST["approval_date"] ?? "");
    $expiryDate = trim($_POST["expiry_date"] ?? "");
    $approvalStatus = trim($_POST["approval_status"] ?? "");

    $appId = filter_input(INPUT_POST, "app_id", FILTER_VALIDATE_INT);

    $validStatuses = ["Approved", "Rejected"];

    $approvalDateObject = DateTime::createFromFormat("!Y-m-d", $approvalDate);
    $expiryDateObject = DateTime::createFromFormat("!Y-m-d", $expiryDate);

    $validApprovalDate =
        $approvalDateObject &&
        $approvalDateObject->format("Y-m-d") === $approvalDate;

    $validExpiryDate =
        $expiryDateObject &&
        $expiryDateObject->format("Y-m-d") === $expiryDate;

    if (
        !$validApprovalDate ||
        !$validExpiryDate ||
        $expiryDate < $approvalDate ||
        !in_array($approvalStatus, $validStatuses, true) ||
        $appId === false ||
        $appId === null ||
        $appId < 1
    ) {
        $message = "Please enter valid approval details. The expiry date cannot be earlier than the approval date.";
        $messageType = "error";

    } else {
        try {
            // Check whether the application exists
            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM Building_Application WHERE App_ID = :app_id"
            );

            $check->execute([":app_id" => $appId]);

            if ((int)$check->fetchColumn() === 0) {
                $message = "The selected building application does not exist.";
                $messageType = "error";

            } else {
                // Generate the next Approval ID
                $nextId = $pdo->query(
                    "SELECT COALESCE(MAX(Approval_ID), 0) + 1
                     FROM Building_Approval"
                )->fetchColumn();

                $sql = "INSERT INTO Building_Approval
                        (
                            Approval_ID,
                            Approval_Date,
                            Expiry_Date,
                            Approval_Status,
                            App_ID
                        )
                        VALUES
                        (
                            :approval_id,
                            :approval_date,
                            :expiry_date,
                            :approval_status,
                            :app_id
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":approval_id" => $nextId,
                    ":approval_date" => $approvalDate,
                    ":expiry_date" => $expiryDate,
                    ":approval_status" => $approvalStatus,
                    ":app_id" => $appId
                ]);

                $message = "Building approval record added successfully!";
                $messageType = "success";
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());

            $message = "Unable to add the approval. Please verify the application and database fields.";
            $messageType = "error";
        }
    }
}

// Search approval records
$search = trim($_GET["search"] ?? "");

try {
    $baseSql = "SELECT
                    ba.Approval_ID,
                    ba.Approval_Date,
                    ba.Expiry_Date,
                    ba.Approval_Status,
                    ba.App_ID,
                    b.Building_Type,
                    b.P_ID
                FROM Building_Approval ba
                LEFT JOIN Building_Application b
                    ON ba.App_ID = b.App_ID";

    if ($search !== "") {
        $sql = $baseSql . "
                WHERE ba.Approval_ID = :approval_id
                   OR ba.App_ID = :app_id
                   OR ba.Approval_Status LIKE :status_search
                   OR b.Building_Type LIKE :type_search
                ORDER BY ba.Approval_ID";

        $stmt = $pdo->prepare($sql);

        $numericSearch = ctype_digit($search) ? (int)$search : -1;

        $stmt->execute([
            ":approval_id" => $numericSearch,
            ":app_id" => $numericSearch,
            ":status_search" => "%" . $search . "%",
            ":type_search" => "%" . $search . "%"
        ]);

        $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $sql = $baseSql . " ORDER BY ba.Approval_ID";

        $approvals = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    $approvals = [];
    $message = "Unable to retrieve approval records.";
    $messageType = "error";
}

// Retrieve applications for the dropdown
try {
    $applications = $pdo->query(
        "SELECT App_ID, Building_Type, P_ID
         FROM Building_Application
         ORDER BY App_ID"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $applications = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Building Approvals | Building Approval System</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            color: #263445;
        }

        header {
            background: #17365d;
            color: white;
            padding: 25px 35px;
        }

        header h1 {
            margin-bottom: 8px;
        }

        nav {
            background: white;
            padding: 16px 35px;
            border-bottom: 1px solid #e0e5ed;
        }

        nav a {
            color: #17365d;
            text-decoration: none;
            margin-right: 22px;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #0000000d;
        }

        h2 {
            color: #17365d;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field label {
            font-weight: bold;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            background: white;
        }

        input:focus, select:focus {
            outline: 2px solid #9db8dc;
            border-color: #315b91;
        }

        button, .button {
            display: inline-block;
            padding: 12px 22px;
            background: #17365d;
            color: white;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
        }

        button:hover, .button:hover {
            background: #254d7e;
        }

        .message {
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .success {
            background: #e7f7ed;
            color: #17643a;
        }

        .error {
            background: #fdecec;
            color: #a12626;
        }

        .info {
            background: #eef4ff;
            color: #17365d;
        }

        .search-form {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 18px;
        }

        .search-form input {
            flex: 1;
            min-width: 0;
        }

        .clear-button {
            background: #e8edf5;
            color: #17365d;
        }

        .search-hint, .count {
            color: #667085;
            font-size: 13px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #e5eaf0;
            text-align: left;
        }

        th {
            background: #edf2f8;
            color: #17365d;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .delete-button {
            background: #b42318;
            padding: 8px 12px;
        }

        .delete-button:hover {
            background: #912018;
        }

        .empty-state {
            text-align: center;
            padding: 25px;
            color: #667085;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-approved {
            background: #e7f7ed;
            color: #17643a;
        }

        .status-rejected {
            background: #fdecec;
            color: #a12626;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #667085;
        }

        @media (max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            header, nav {
                padding: 20px;
            }

            .search-form {
                flex-wrap: wrap;
            }

            .search-form input {
                flex-basis: 100%;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Building Approval Management System</h1>
    <p>Building Approval Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="applications.php">Applications</a>
    <a href="inspections.php">Inspections</a>
    <a href="approvals.php">Approvals</a>
</nav>

<main>

    <section class="panel">

        <h2>Add Building Approval</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="approvals.php">

            <div class="form-grid">

                <div class="field">
                    <label for="approval_date">Approval Date</label>

                    <input
                        type="date"
                        id="approval_date"
                        name="approval_date"
                        required
                    >
                </div>

                <div class="field">
                    <label for="expiry_date">Expiry Date</label>

                    <input
                        type="date"
                        id="expiry_date"
                        name="expiry_date"
                        required
                    >
                </div>

                <div class="field">
                    <label for="app_id">Building Application</label>

                    <select id="app_id" name="app_id" required>
                        <option value="">Select application</option>

                        <?php foreach ($applications as $application): ?>
                            <option value="<?= (int)$application["App_ID"] ?>">
                                <?= htmlspecialchars(
                                    "Application " . $application["App_ID"] .
                                    " — " . $application["Building_Type"] .
                                    " (Property " . $application["P_ID"] . ")"
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="field">
                    <label for="approval_status">Approval Status</label>

                    <select
                        id="approval_status"
                        name="approval_status"
                        required
                    >
                        <option value="">Select status</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

            </div>

            <button
                type="submit"
                name="add_approval"
                value="1"
                style="margin-top: 20px;"
            >
                Add Approval
            </button>

        </form>

    </section>

    <section class="panel">

        <h2>Building Approval Records</h2>

        <form method="GET" action="approvals.php" class="search-form">

            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by Approval ID, Application ID, status or building type..."
                aria-label="Search approvals"
            >

            <button type="submit">Search</button>

            <a href="approvals.php" class="button clear-button">
                Clear
            </a>

        </form>

        <p class="search-hint">
            Search by Approval ID, Application ID, approval status, or building type.
        </p>

        <p class="count">
            <?= $search !== ""
                ? "Matching records: "
                : "Total approval records: " ?>

            <?= count($approvals) ?>
        </p>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Approval ID</th>
                        <th>Approval Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Application ID</th>
                        <th>Building Type</th>
                        <th>Property ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($approvals) > 0): ?>

                    <?php foreach ($approvals as $approval): ?>

                        <tr>
                            <td>
                                <?= (int)$approval["Approval_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($approval["Approval_Date"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($approval["Expiry_Date"]) ?>
                            </td>

                            <td>
                                <?php
                                $statusClass =
                                    $approval["Approval_Status"] === "Approved"
                                    ? "status-approved"
                                    : "status-rejected";
                                ?>

                                <span class="status <?= $statusClass ?>">
                                    <?= htmlspecialchars($approval["Approval_Status"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= (int)$approval["App_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($approval["Building_Type"] ?? "Unknown") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars((string)($approval["P_ID"] ?? "Unknown")) ?>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="delete_record.php"
                                    onsubmit="return confirm('Are you sure you want to delete this approval record?');"
                                >
                                    <input
                                        type="hidden"
                                        name="type"
                                        value="approval"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$approval["Approval_ID"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="delete-button"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty-state">
                            No matching approval records found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<footer>
    Building Approval Management System | RDBMS Project
</footer>

</body>
</html>
