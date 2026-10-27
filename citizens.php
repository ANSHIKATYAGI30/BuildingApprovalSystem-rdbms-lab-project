<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "db.php";

$message = "";
$messageType = "";

if (isset($_SESSION["delete_message"])) {
    $message = $_SESSION["delete_message"];
    $messageType = $_SESSION["delete_status"] ?? "info";

    unset($_SESSION["delete_message"], $_SESSION["delete_status"]);
}

// Handle adding a new citizen
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aadhaar = trim($_POST["aadhaar"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $address = trim($_POST["address"] ?? "");

    if (!preg_match('/^[0-9]{12}$/', $aadhaar)) {
        $message = "Aadhaar number must contain exactly 12 digits.";
        $messageType = "error";

    } elseif ($name === "" || $contact === "" || $address === "") {
        $message = "Please fill in all the fields.";
        $messageType = "error";

    } else {
        try {
            $nextId = $pdo->query(
                "SELECT COALESCE(MAX(C_ID), 0) + 1 FROM Citizen"
            )->fetchColumn();

            $sql = "INSERT INTO Citizen
                    (C_ID, Aadhaar_No, Name, Contact, Address)
                    VALUES (:id, :aadhaar, :name, :contact, :address)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":id" => $nextId,
                ":aadhaar" => $aadhaar,
                ":name" => $name,
                ":contact" => $contact,
                ":address" => $address
            ]);

            $message = "Citizen added successfully!";
            $messageType = "success";

        } catch (PDOException $e) {
            error_log($e->getMessage());

            if ($e->getCode() === "23000") {
                $message = "This Aadhaar number may already exist, or a database constraint was violated.";
            } else {
                $message = "Unable to add citizen. Please check the server error log.";
            }

            $messageType = "error";
        }
    }
}

// Search functionality
$search = trim($_GET["search"] ?? "");

try {
    if ($search !== "") {

        if (ctype_digit($search)) {
            // For numeric input, prioritize an exact Citizen ID match.
            $sql = "SELECT C_ID, Aadhaar_No, Name, Contact, Address
                    FROM Citizen
                    WHERE C_ID = :citizen_id
                    ORDER BY C_ID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":citizen_id" => (int)$search
            ]);

            $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no Citizen ID matches, search other fields.
            if (empty($citizens)) {
                $sql = "SELECT C_ID, Aadhaar_No, Name, Contact, Address
                        FROM Citizen
                        WHERE Name LIKE :name_search
                           OR Contact LIKE :contact_search
                           OR Aadhaar_No LIKE :aadhaar_search
                        ORDER BY C_ID";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ":name_search" => "%" . $search . "%",
                    ":contact_search" => "%" . $search . "%",
                    ":aadhaar_search" => "%" . $search . "%"
                ]);

                $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

        } else {
            // Text input searches by name, contact, or Aadhaar.
            $sql = "SELECT C_ID, Aadhaar_No, Name, Contact, Address
                    FROM Citizen
                    WHERE Name LIKE :name_search
                       OR Contact LIKE :contact_search
                       OR Aadhaar_No LIKE :aadhaar_search
                    ORDER BY C_ID";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name_search" => "%" . $search . "%",
                ":contact_search" => "%" . $search . "%",
                ":aadhaar_search" => "%" . $search . "%"
            ]);

            $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } else {
        // No search term: display all citizens.
        $stmt = $pdo->query(
            "SELECT C_ID, Aadhaar_No, Name, Contact, Address
             FROM Citizen
             ORDER BY C_ID"
        );

        $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    $citizens = [];
    $message = "Unable to retrieve citizen records.";
    $messageType = "error";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Citizens | Building Approval System</title>

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

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
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

        .clear-button:hover {
            background: #d9e2ef;
        }

        .search-hint {
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

        .count {
            color: #667085;
            margin-bottom: 18px;
        }

        .empty-state {
            text-align: center;
            padding: 25px;
            color: #667085;
        }

        .delete-button {
            background: #b42318;
            color: white;
            padding: 8px 12px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #912018;
        }

        .actions-cell form {
            margin: 0;
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

            .search-form button,
            .search-form .button {
                flex: 1;
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
    <p>Citizens Management</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="citizens.php">Citizens</a>
</nav>

<main>

    <section class="panel">
        <h2>Add New Citizen</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="citizens.php">

            <div class="form-grid">

                <div class="field">
                    <label for="aadhaar">Aadhaar Number</label>

                    <input
                        type="password"
                        id="aadhaar"
                        name="aadhaar"
                        inputmode="numeric"
                        pattern="[0-9]{12}"
                        minlength="12"
                        maxlength="12"
                        autocomplete="off"
                        placeholder="Enter 12 digits"
                        required
                    >
                </div>

                <div class="field">
                    <label for="name">Full Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="field">
                    <label for="contact">Contact Number</label>

                    <input
                        type="tel"
                        id="contact"
                        name="contact"
                        maxlength="15"
                        required
                    >
                </div>

                <div class="field">
                    <label for="address">Address</label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        maxlength="150"
                        required
                    >
                </div>

            </div>

            <button type="submit" style="margin-top: 20px;">
                Add Citizen
            </button>

        </form>
    </section>

    <section class="panel">

        <h2>Registered Citizens</h2>

        <form method="GET" action="citizens.php" class="search-form">

            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Search by ID, name, contact or Aadhaar..."
                aria-label="Search citizens"
            >

            <button type="submit">Search</button>

            <a href="citizens.php" class="button clear-button">
                Clear
            </a>

        </form>

        <p class="search-hint">
            Search by ID, name, contact number, or Aadhaar number.
            Aadhaar numbers remain masked in the results.
        </p>

        <p class="count">
            <?php if ($search !== ""): ?>
                Results for
                "<strong><?= htmlspecialchars($search) ?></strong>":
            <?php else: ?>
                Total records:
            <?php endif; ?>

            <?= count($citizens) ?>
        </p>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Citizen ID</th>
                        <th>Name</th>
                        <th>Aadhaar Number</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($citizens) > 0): ?>

                    <?php foreach ($citizens as $citizen): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars((string)$citizen["C_ID"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($citizen["Name"]) ?>
                            </td>

                            <td>
                                ••••••••<?= htmlspecialchars(substr($citizen["Aadhaar_No"], -4)) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($citizen["Contact"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($citizen["Address"]) ?>
                            </td>

                            <td class="actions-cell">

                                <form
                                    method="POST"
                                    action="delete_record.php"
                                    onsubmit="return confirm('Delete this citizen record? This action cannot be undone.');"
                                >

                                    <input
                                        type="hidden"
                                        name="type"
                                        value="citizen"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$citizen["C_ID"] ?>"
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
                            No matching citizen records found.
                            Try another search term.
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