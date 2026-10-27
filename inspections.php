<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "db.php";

$message = "";
$messageType = "";

// Display messages after deletion
if (isset($_SESSION["delete_message"])) {
    $message = $_SESSION["delete_message"];
    $messageType = $_SESSION["delete_status"] ?? "info";

    unset($_SESSION["delete_message"], $_SESSION["delete_status"]);
}

// Handle adding a new inspection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_inspection"])) {

    $inspectionDate = trim($_POST["inspection_date"] ?? "");
    $inspectionResult = trim($_POST["inspection_result"] ?? "");
    $remarks = trim($_POST["remarks"] ?? "");

    $appId = filter_input(INPUT_POST, "app_id", FILTER_VALIDATE_INT);
    $engId = filter_input(INPUT_POST, "eng_id", FILTER_VALIDATE_INT);

    $dateObject = DateTime::createFromFormat("!Y-m-d", $inspectionDate);
    $validDate = $dateObject && $dateObject->format("Y-m-d") === $inspectionDate;

    $allowedResults = [
        "Approved",
        "Rejected",
        "Needs Reinspection"
    ];

    if (
        !$validDate ||
        !in_array($inspectionResult, $allowedResults, true) ||
        $appId === false || $appId === null || $appId < 1 ||
        $engId === false || $engId === null || $engId < 1
    ) {
        $message = "Please enter valid inspection details.";
        $messageType = "error";

    } else {
        try {
            // Generate the next Inspection ID
            $nextId = $pdo->query(
                "SELECT COALESCE(MAX(Inspection_ID), 0) + 1
                 FROM Site_Inspection"
            )->fetchColumn();

            $sql = "INSERT INTO Site_Inspection
                    (
                        Inspection_ID,
                        Inspection_Date,
                        Inspection_Result,
                        Remarks,
                        App_ID,
                        Eng_ID
                    )
                    VALUES
                    (
                        :inspection_id,
                        :inspection_date,
                        :inspection_result,
                        :remarks,
                        :app_id,
                        :eng_id
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":inspection_id" => $nextId,
                ":inspection_date" => $inspectionDate,
                ":inspection_result" => $inspectionResult,
                ":remarks" => $remarks,
                ":app_id" => $appId,
                ":eng_id" => $engId
            ]);

            $message = "Site inspection added successfully!";
            $messageType = "success";

        } catch (PDOException $e) {
            error_log($e->getMessage());

            $message = "Unable to add the inspection. Please verify the application and engineer.";
            $messageType = "error";
        }
    }
}

// Search inspections
$search = trim($_GET["search"] ?? "");

try {
    $baseSql = "SELECT
                    si.Inspection_ID,
                    si.Inspection_Date,
                    si.Inspection_Result,
                    si.Remarks,
                    si.App_ID,
                    si.Eng_ID,
                    e.Name AS Engineer_Name
                FROM Site_Inspection si
                LEFT JOIN Engineer e ON si.Eng_ID = e.Eng_ID";

    if ($search !== "") {
        $sql = $baseSql . "
                WHERE si.Inspection_ID = :inspection_id
                   OR si.App_ID = :app_id
                   OR si.Inspection_Result LIKE :result_search
                   OR e.Name LIKE :engineer_search
                ORDER BY si.Inspection_ID";

        $stmt = $pdo->prepare($sql);

        $numericSearch = ctype_digit($search) ? (int)$search : -1;

        $stmt->execute([
            ":inspection_id" => $numericSearch,
            ":app_id" => $numericSearch,
            ":result_search" => "%" . $search . "%",
            ":engineer_search" => "%" . $search . "%"
        ]);

        $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $sql = $baseSql . " ORDER BY si.Inspection_ID";

        $inspections = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    $inspections = [];
    $message = "Unable to retrieve inspection records.";
    $messageType = "error";
}

// Retrieve applications for the dropdown
try {
    $applications = $pdo->query(
        "SELECT App_ID, Building_Type
         FROM Building_Application
         ORDER BY App_ID"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $applications = [];
}

// Retrieve engineers for the dropdown
try {
    $engineers = $pdo->query(
        "SELECT Eng_ID, Name
         FROM Engineer
         ORDER BY Eng_ID"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $engineers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Site Inspections | Building Approval System</title>

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

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        input:focus, select:focus, textarea:focus {
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
    <p>Site Inspection Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="applications.php">Applications</a>
    <a href="inspections.php">Inspections</a>
    <a href="engineers.php">Engineers</a>
</nav>

<main>

    <section class="panel">
        <h2>Add Site Inspection</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="inspections.php">

            <div class="form-grid">

                <div class="field">
                    <label for="inspection_date">Inspection Date</label>

                    <input
                        type="date"
                        id="inspection_date"
                        name="inspection_date"
                        required
                    >
                </div>

                <div class="field">
                    <label for="inspection_result">Inspection Result</label>

                    <select
                        id="inspection_result"
                        name="inspection_result"
                        required
                    >
                        <option value="">Select result</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Needs Reinspection">
                            Needs Reinspection
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="app_id">Building Application</label>

                    <select id="app_id" name="app_id" required>
                        <option value="">Select application</option>

                        <?php foreach ($applications as $application): ?>
                            <option value="<?= (int)$application["App_ID"] ?>">
                                <?= htmlspecialchars(
                                    "Application " . $application["App_ID"] .
                                    " — " . $application["Building_Type"]
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="field">
                    <label for="eng_id">Inspecting Engineer</label>

                    <select id="eng_id" name="eng_id" required>
                        <option value="">Select engineer</option>

                        <?php foreach ($engineers as $engineer): ?>
                            <option value="<?= (int)$engineer["Eng_ID"] ?>">
                                <?= htmlspecialchars($engineer["Name"]) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="remarks">Remarks</label>

                    <textarea
                        id="remarks"
                        name="remarks"
                        maxlength="200"
                        placeholder="Enter inspection observations"
                    ></textarea>
                </div>

            </div>

            <button
                type="submit"
                name="add_inspection"
                value="1"
                style="margin-top: 20px;"
            >
                Add Inspection
            </button>

        </form>
    </section>

    <section class="panel">

        <h2>Registered Site Inspections</h2>

        <form method="GET" action="inspections.php" class="search-form">

            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by Inspection ID, Application ID, result or engineer..."
                aria-label="Search inspections"
            >

            <button type="submit">Search</button>

            <a href="inspections.php" class="button clear-button">
                Clear
            </a>

        </form>

        <p class="search-hint">
            Search by Inspection ID, Application ID, inspection result, or engineer name.
        </p>

        <p class="count">
            <?= $search !== ""
                ? "Matching inspections: "
                : "Total inspections: " ?>

            <?= count($inspections) ?>
        </p>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>Inspection ID</th>
                        <th>Inspection Date</th>
                        <th>Result</th>
                        <th>Remarks</th>
                        <th>Application ID</th>
                        <th>Engineer ID</th>
                        <th>Engineer Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($inspections) > 0): ?>

                    <?php foreach ($inspections as $inspection): ?>

                        <tr>
                            <td>
                                <?= (int)$inspection["Inspection_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($inspection["Inspection_Date"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($inspection["Inspection_Result"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($inspection["Remarks"] ?? "") ?>
                            </td>

                            <td>
                                <?= (int)$inspection["App_ID"] ?>
                            </td>

                            <td>
                                <?= (int)$inspection["Eng_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($inspection["Engineer_Name"] ?? "Unknown") ?>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="delete_record.php"
                                    onsubmit="return confirm('Are you sure you want to delete this inspection?');"
                                >
                                    <input
                                        type="hidden"
                                        name="type"
                                        value="inspection"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$inspection["Inspection_ID"] ?>"
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
                            No matching inspection records found.
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