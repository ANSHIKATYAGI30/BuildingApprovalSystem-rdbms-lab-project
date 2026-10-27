<?php
$host = "127.0.0.1";
$port = 3306;
$dbname = "buildingapprovaldb";
$username = "root";
$password = "Kheer@30.mitha"; // Enter your MySQL password if you have one

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "SUCCESS: Connected to MySQL and selected the database.";

} catch (PDOException $e) {
    echo "<h3>Connection failed</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
