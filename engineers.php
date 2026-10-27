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

// Handle adding an engineer
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_engineer"])) {

    $name = trim($_POST["name"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $phoneNumber = trim($_POST["phone_number"] ?? "");
    $qualifications = trim($_POST["qualifications"] ?? "");

    if (
        $name === "" ||
        $department === "" ||
        $phoneNumber === "" ||
        $qualifications === ""
    ) {
        $message = "Please fill in all engineer details.";
        $messageType = "error";

    } elseif (!preg_match('/^[0-9]{10}$/', $phoneNumber)) {
        $message = "Please enter a valid 10-digit phone number.";
        $messageType = "error";

    } else {
        try {
            // Generate the next Engineer ID
            $nextId = $pdo->query(
                "SELECT COALESCE(MAX(Eng_ID), 0) + 1 FROM Engineer"
            )->fetchColumn();

            $sql = "INSERT INTO Engineer
                    (
                        Eng_ID,
                        Name,
                        Department,
                        Phone_Number,
                        Qualifications
                    )
                    VALUES
                    (
                        :eng_id,
                        :name,
                        :department,
                        :phone_number,
                        :qualifications
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":eng_id" => $nextId,
                ":name" => $name,
                ":department" => $department,
                ":phone_number" => $phoneNumber,
                ":qualifications" => $qualifications
            ]);

            $message = "Engineer added successfully!";
            $messageType = "success";

        } catch (PDOException $e) {
            error_log($e->getMessage());

            $message = "Unable to add the engineer. Please check the entered details.";
            $messageType = "error";
        }
    }
}

// Search engineers
$search = trim($_GET["search"] ?? "");

try {
    if ($search !== "") {

        $numericSearch = ctype_digit($search) ? (int)$search : -1;

        $sql = "SELECT
                    Eng_ID,
                    Name,
                    Department,
                    Phone_Number,
                    Qualifications
                FROM Engineer
                WHERE Eng_ID = :eng_id
                   OR Name LIKE :name_search
                   OR Department LIKE :department_search
                ORDER BY Eng_ID";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":eng_id" => $numericSearch,
            ":name_search" => "%" . $search . "%",
            ":department_search" => "%" . $search . "%"
        ]);

        $engineers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        $sql = "SELECT
                    Eng_ID,
                    Name,
                    Department,
                    Phone_Number,
                    Qualifications
                FROM Engineer
                ORDER BY Eng_ID";

        $engineers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    $engineers = [];
    $message = "Unable to retrieve engineer records.";
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Engineer Management | Building Approval System</title>

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

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            background: white;
        }

        input:focus {
            outline: 2px solid #9db8dc;
            border-color: #315b91;
        }

        button,
        .button {
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

        button:hover,
        .button:hover {
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

        .search-hint,
        .count {
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

        th,
        td {
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

            header,
            nav {
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
    <p>Engineer Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="citizens.php">Citizens</a>
    <a href="properties.php">Properties</a>
    <a href="applications.php">Applications</a>
    <a href="inspections.php">Inspections</a>
    <a href="approvals.php">Approvals</a>
    <a href="engineers.php">Engineers</a>
</nav>

<main>

    <section class="panel">

        <h2>Add Engineer</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="engineers.php">

            <div class="form-grid">

                <div class="field">
                    <label for="name">Engineer Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        required
                        placeholder="Enter engineer name"
                    >
                </div>

                <div class="field">
                    <label for="department">Department</label>

                    <input
                        type="text"
                        id="department"
                        name="department"
                        maxlength="100"
                        required
                        placeholder="Enter department"
                    >
                </div>

                <div class="field">
                    <label for="phone_number">Phone Number</label>

                    <input
                        type="tel"
                        id="phone_number"
                        name="phone_number"
                        pattern="[0-9]{10}"
                        maxlength="10"
                        minlength="10"
                        inputmode="numeric"
                        required
                        placeholder="10-digit phone number"
                    >
                </div>

                <div class="field">
                    <label for="qualifications">Qualifications</label>

                    <input
                        type="text"
                        id="qualifications"
                        name="qualifications"
                        maxlength="150"
                        required
                        placeholder="Enter qualifications"
                    >
                </div>

            </div>

            <button
                type="submit"
                name="add_engineer"
                value="1"
                style="margin-top: 20px;"
            >
                Add Engineer
            </button>

        </form>

    </section>

    <section class="panel">

        <h2>Registered Engineers</h2>

        <form method="GET" action="engineers.php" class="search-form">

            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by Engineer ID, name or department..."
                aria-label="Search engineers"
            >

            <button type="submit">Search</button>

            <a href="engineers.php" class="button clear-button">
                Clear
            </a>

        </form>

        <p class="search-hint">
            Search using Engineer ID, engineer name, or department.
        </p>

        <p class="count">
            <?= $search !== ""
                ? "Matching engineers: "
                : "Total engineers: " ?>

            <?= count($engineers) ?>
        </p>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Engineer ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Phone Number</th>
                        <th>Qualifications</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($engineers) > 0): ?>

                    <?php foreach ($engineers as $engineer): ?>

                        <tr>
                            <td>
                                <?= (int)$engineer["Eng_ID"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($engineer["Name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($engineer["Department"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($engineer["Phone_Number"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($engineer["Qualifications"]) ?>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="delete_record.php"
                                    onsubmit="return confirm('Are you sure you want to delete this engineer?');"
                                >
                                    <input
                                        type="hidden"
                                        name="type"
                                        value="engineer"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$engineer["Eng_ID"] ?>"
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
                        <td colspan="6" class="empty-state">
                            No matching engineer records found.
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
