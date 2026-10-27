<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once "db.php";

$redirectPages = [
    "citizen" => "citizens.php",
    "property" => "properties.php",
    "application" => "applications.php",
    "inspection" => "inspections.php",
    "approval" => "approvals.php",
    "engineer" => "engineers.php"
];

$recordTables = [
    "citizen" => ["table" => "Citizen", "id" => "C_ID"],
    "property" => ["table" => "Property", "id" => "P_ID"],
    "application" => [
        "table" => "Building_Application",
        "id" => "App_ID"
    ],
    "inspection" => [
        "table" => "Site_Inspection",
        "id" => "Inspection_ID"
    ],
    "approval" => [
        "table" => "Building_Approval",
        "id" => "Approval_ID"
    ],
    "engineer" => ["table" => "Engineer", "id" => "Eng_ID"]
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$type = $_POST["type"] ?? "";
$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);

if (
    !isset($recordTables[$type]) ||
    $id === false ||
    $id === null ||
    $id < 1
) {
    $_SESSION["delete_message"] = "Invalid record type or ID.";
    $_SESSION["delete_status"] = "error";

    header("Location: " . ($redirectPages[$type] ?? "index.php"));
    exit;
}

$table = $recordTables[$type]["table"];
$idColumn = $recordTables[$type]["id"];

try {
    $sql = "DELETE FROM `$table` WHERE `$idColumn` = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION["delete_message"] = "Record deleted successfully.";
        $_SESSION["delete_status"] = "success";
    } else {
        $_SESSION["delete_message"] = "No matching record was found.";
        $_SESSION["delete_status"] = "error";
    }

} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());

    if ($e->getCode() === "23000") {
        $_SESSION["delete_message"] =
            "Cannot delete this record because another record depends on it.";
    } else {
        $_SESSION["delete_message"] =
            "Unable to delete the record. Check the PHP server error log.";
    }

    $_SESSION["delete_status"] = "error";
}

header("Location: " . $redirectPages[$type]);
exit;