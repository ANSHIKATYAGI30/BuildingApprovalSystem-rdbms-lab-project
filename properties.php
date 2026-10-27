<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "db.php";

$message = "";
$messageType = "";

// Display messages after a delete operation
if (isset($_SESSION["delete_message"])) {
    $message = $_SESSION["delete_message"];
    $messageType = $_SESSION["delete_status"] ?? "info";

    unset($_SESSION["delete_message"], $_SESSION["delete_status"]);
}

// Handle adding a new property
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_property"])) {

    $plotNo = trim($_POST["plot_no"] ?? "");
    $landUse = trim($_POST["land_use"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $citizenId = filter_input(INPUT_POST, "c_id", FILTER_VALIDATE_INT);

    if ($plotNo === "" || $landUse === "" || $location === "" ||
        $citizenId === false || $citizenId === null || $citizenId < 1) {

        $message = "Please enter valid details in all fields.";
        $messageType = "error";

    } else {
        try {
            // Verify that the citizen exists
            $check = $pdo->prepare(
                "SELECT C_ID FROM Citizen WHERE C_ID = :id"
            );

            $check->execute([":id" => $citizenId]);

            if (!$check->fetch()) {
                $message = "The selected citizen does not exist.";
                $messageType = "error";

            } else {
                // Generate the next Property ID
                $nextId = $pdo->query(
                    "SELECT COALESCE(MAX(P_ID), 0) + 1 FROM Property"
                )->fetchColumn();

                $sql = "INSERT INTO Property
                        (P_ID, Plot_No, Land_Use, Location, C_ID)
                        VALUES
                        (:id, :plot_no, :land_use, :location, :c_id)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":id" => $nextId,
                    ":plot_no" => $plotNo,
                    ":land_use" => $landUse,
                    ":location" => $location,
                    ":c_id" => $citizenId
                ]);

                $message = "Property added successfully!";
                $messageType = "success";
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());

            $message = "Unable to add property. Check the details and database constraints.";
            $messageType = "error";
        }
    }
}

// Search by Property ID or Property Type (Land_Use)
$search = trim($_GET["search"] ?? "");

try {
    if ($search !== "") {
        $sql = "SELECT
                    p.P_ID,
                    p.Plot_No,
                    p.Land_Use,
                    p.Location,
                    p.C_ID,
                    c.Name AS Citizen_Name
                FROM Property p
                LEFT JOIN Citizen c ON p.C_ID = c.C_ID
                WHERE p.P_ID = :property_id
                   OR p.Land_Use LIKE :land_use
                ORDER BY p.P_ID";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":property_id" => ctype_digit($search) ? (int)$search : -1,
            ":land_use" => "%" . $search . "%"
        ]);

        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $sql = "SELECT
                    p.P_ID,
                    p.Plot_No,
                    p.Land_Use,
                    p.Location,
                    p.C_ID,
                    c.Name AS Citizen_Name
                FROM Property p
                LEFT JOIN Citizen c ON p.C_ID = c.C_ID
                ORDER BY p.P_ID";

        $properties = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    $properties = [];
    $message = "Unable to retrieve property records.";
    $messageType = "error";
}

// Retrieve citizens for the dropdown
try {
    $citizens = $pdo->query(
        "SELECT C_ID, Name FROM Citizen ORDER BY C_ID"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $citizens = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Properties | Building Approval System</title>

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
            max-width: 1150px;
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
    <p>Property Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="citizens.php">Citizens</a>
    <a href="properties.php">Properties</a>
</nav>

<main>

    <section class="panel">
        <h2>Add New Property</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="properties.php">

            <div class="form-grid">

                <div class="field">
                    <label for="plot_no">Plot Number</label>
                    <input
                        type="text"
                        id="plot_no"
                        name="plot_no"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="field">
                    <label for="land_use">Property Type / Land Use</label>
                    <input
                        type="text"
                        id="land_use"
                        name="land_use"
                        maxlength="50"
                        placeholder="Residential, Commercial, etc."
                        required
                    >
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="field">
                    <label for="c_id">Property Owner</label>
                    <select id="c_id" name="c_id" required>
                        <option value="">Select a citizen</option>

                        <?php foreach ($citizens as $citizen): ?>
                            <option value="<?= (int)$citizen["C_ID"] ?>">
                                <?= htmlspecialchars(
                                    $citizen["Name"] .
                                    " (ID: " . $citizen["C_ID"] . ")"
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <button
                type="submit"
                name="add_property"
                value="1"
                style="margin-top: 20px;"
            >
                Add Property
            </button>

        </form>
    </section>

    <section class="panel">

        <h2>Registered Properties</h2>

        <form method="GET" action="properties.php" class="search-form">

            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by Property ID or Property Type..."
                aria-label="Search properties"
            >

            <button type="submit">Search</button>

            <a href="properties.php" class="button clear-button">
                Clear
            </a>

        </form>

        <p class="search-hint">
            Search by exact Property ID or partial Property Type / Land Use.
        </p>

        <p class="count">
            <?= $search !== ""
                ? "Matching properties: "
                : "Total properties: " ?>

            <?= count($properties) ?>
        </p>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>Property ID</th>
                        <th>Plot Number</th>
                        <th>Property Type</th>
                        <th>Location</th>
                        <th>Citizen ID</th>
                        <th>Owner Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($properties) > 0): ?>

                    <?php foreach ($properties as $property): ?>

                        <tr>
                            <td>
                                <?= (int)$property["P_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($property["Plot_No"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($property["Land_Use"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($property["Location"]) ?>
                            </td>

                            <td>
                                <?= (int)$property["C_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($property["Citizen_Name"] ?? "Unknown") ?>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="delete_record.php"
                                    onsubmit="return confirm('Delete this property? Related applications may prevent deletion.');"
                                >
                                    <input
                                        type="hidden"
                                        name="type"
                                        value="property"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$property["P_ID"] ?>"
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
                        <td colspan="7" class="empty-state">
                            No matching property records found.
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