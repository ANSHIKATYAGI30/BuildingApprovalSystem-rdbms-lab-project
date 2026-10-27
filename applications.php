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

// Handle adding a new building application
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_application"])) {

    $applicationDate = trim($_POST["application_date"] ?? "");
    $estimatedCost = trim($_POST["estimated_cost"] ?? "");
    $buildingType = trim($_POST["building_type"] ?? "");
    $floors = filter_input(INPUT_POST, "floors_proposed", FILTER_VALIDATE_INT);
    $propertyId = filter_input(INPUT_POST, "p_id", FILTER_VALIDATE_INT);

    $dateObject = DateTime::createFromFormat("!Y-m-d", $applicationDate);
    $validDate = $dateObject && $dateObject->format("Y-m-d") === $applicationDate;

    if (
        !$validDate ||
        $buildingType === "" ||
        !is_numeric($estimatedCost) ||
        !is_finite((float)$estimatedCost) ||
        (float)$estimatedCost <= 0 ||
        $floors === false || $floors === null || $floors < 1 ||
        $propertyId === false || $propertyId === null || $propertyId < 1
    ) {
        $message = "Please enter valid application details.";
        $messageType = "error";

    } else {
        try {
            // Verify that the selected property exists
            $check = $pdo->prepare(
                "SELECT P_ID FROM Property WHERE P_ID = :p_id"
            );

            $check->execute([":p_id" => $propertyId]);

            if (!$check->fetch()) {
                $message = "The selected property does not exist.";
                $messageType = "error";

            } else {
                // Generate the next application ID
                $nextId = $pdo->query(
                    "SELECT COALESCE(MAX(App_ID), 0) + 1
                     FROM Building_Application"
                )->fetchColumn();

                $sql = "INSERT INTO Building_Application
                        (
                            App_ID,
                            Application_Date,
                            Estimated_Cost,
                            Building_Type,
                            Floors_Proposed,
                            P_ID
                        )
                        VALUES
                        (
                            :app_id,
                            :application_date,
                            :estimated_cost,
                            :building_type,
                            :floors,
                            :p_id
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":app_id" => $nextId,
                    ":application_date" => $applicationDate,
                    ":estimated_cost" => $estimatedCost,
                    ":building_type" => $buildingType,
                    ":floors" => $floors,
                    ":p_id" => $propertyId
                ]);

                $message = "Building application added successfully!";
                $messageType = "success";
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());

            $message = "Unable to add the application. Please check the details and database constraints.";
            $messageType = "error";
        }
    }
}

// Search applications by ID or building type
$search = trim($_GET["search"] ?? "");

try {
    $baseSql = "SELECT
                    ba.App_ID,
                    ba.Application_Date,
                    ba.Estimated_Cost,
                    ba.Building_Type,
                    ba.Floors_Proposed,
                    ba.P_ID,
                    p.Plot_No,
                    p.Location
                FROM Building_Application ba
                LEFT JOIN Property p ON ba.P_ID = p.P_ID";

    if ($search !== "") {
        $sql = $baseSql . "
                WHERE ba.App_ID = :app_id
                   OR ba.Building_Type LIKE :building_type
                ORDER BY ba.App_ID";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":app_id" => ctype_digit($search) ? (int)$search : -1,
            ":building_type" => "%" . $search . "%"
        ]);

        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $sql = $baseSql . " ORDER BY ba.App_ID";

        $applications = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    $applications = [];
    $message = "Unable to retrieve building applications.";
    $messageType = "error";
}

// Retrieve properties for the dropdown
try {
    $properties = $pdo->query(
        "SELECT P_ID, Plot_No, Location
         FROM Property
         ORDER BY P_ID"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $properties = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Building Applications | Building Approval System</title>

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
    <p>Building Applications Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="citizens.php">Citizens</a>
    <a href="properties.php">Properties</a>
    <a href="applications.php">Applications</a>
</nav>

<main>

    <section class="panel">
        <h2>Add Building Application</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="applications.php">

            <div class="form-grid">

                <div class="field">
                    <label for="application_date">Application Date</label>

                    <input
                        type="date"
                        id="application_date"
                        name="application_date"
                        required
                    >
                </div>

                <div class="field">
                    <label for="estimated_cost">Estimated Cost</label>

                    <input
                        type="number"
                        id="estimated_cost"
                        name="estimated_cost"
                        min="0.01"
                        step="0.01"
                        placeholder="Enter estimated cost"
                        required
                    >
                </div>

                <div class="field">
                    <label for="building_type">Building Type</label>

                    <input
                        type="text"
                        id="building_type"
                        name="building_type"
                        maxlength="50"
                        placeholder="Residential, Commercial, etc."
                        required
                    >
                </div>

                <div class="field">
                    <label for="floors_proposed">Proposed Floors</label>

                    <input
                        type="number"
                        id="floors_proposed"
                        name="floors_proposed"
                        min="1"
                        step="1"
                        required
                    >
                </div>

                <div class="field">
                    <label for="p_id">Property</label>

                    <select id="p_id" name="p_id" required>
                        <option value="">Select a property</option>

                        <?php foreach ($properties as $property): ?>
                            <option value="<?= (int)$property["P_ID"] ?>">
                                <?= htmlspecialchars(
                                    "Property " . $property["P_ID"] .
                                    " — Plot " . $property["Plot_No"] .
                                    " — " . $property["Location"]
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <button
                type="submit"
                name="add_application"
                value="1"
                style="margin-top: 20px;"
            >
                Add Application
            </button>

        </form>
    </section>

    <section class="panel">

        <h2>Registered Building Applications</h2>

        <form method="GET" action="applications.php" class="search-form">

            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by Application ID or Building Type..."
                aria-label="Search applications"
            >

            <button type="submit">Search</button>

            <a href="applications.php" class="button clear-button">
                Clear
            </a>

        </form>

        <p class="search-hint">
            Search using an exact Application ID or part of the Building Type.
        </p>

        <p class="count">
            <?= $search !== ""
                ? "Matching applications: "
                : "Total applications: " ?>

            <?= count($applications) ?>
        </p>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Application ID</th>
                        <th>Application Date</th>
                        <th>Estimated Cost</th>
                        <th>Building Type</th>
                        <th>Proposed Floors</th>
                        <th>Property ID</th>
                        <th>Plot Number</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($applications) > 0): ?>

                    <?php foreach ($applications as $application): ?>

                        <tr>
                            <td>
                                <?= (int)$application["App_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($application["Application_Date"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    number_format(
                                        (float)$application["Estimated_Cost"],
                                        2
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($application["Building_Type"]) ?>
                            </td>

                            <td>
                                <?= (int)$application["Floors_Proposed"] ?>
                            </td>

                            <td>
                                <?= (int)$application["P_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($application["Plot_No"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($application["Location"] ?? "") ?>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="delete_record.php"
                                    onsubmit="return confirm('Delete this building application? Related inspections or approvals may prevent deletion.');"
                                >
                                    <input
                                        type="hidden"
                                        name="type"
                                        value="application"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$application["App_ID"] ?>"
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
                        <td colspan="9" class="empty-state">
                            No matching building applications found.
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