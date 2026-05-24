<?php
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();

echo "<h1>Database Debug Info</h1>";

// Check agency table
echo "<h2>Agency Table</h2>";
$stmt = $pdo->query("SELECT * FROM agency LIMIT 5");
$agencies = $stmt->fetchAll();
echo "Number of agencies: " . count($agencies) . "<br>";
foreach ($agencies as $a) {
    print_r($a);
    echo "<br>";
}

// Check user_account table
echo "<h2>User Account Table (agency role)</h2>";
$stmt = $pdo->query("SELECT * FROM user_account WHERE role = 'agency' LIMIT 5");
$users = $stmt->fetchAll();
echo "Number of agency users: " . count($users) . "<br>";
foreach ($users as $u) {
    print_r($u);
    echo "<br>";
}

// Check travel_package table
echo "<h2>Travel Package Table</h2>";
$stmt = $pdo->query("SELECT * FROM travel_package LIMIT 5");
$packages = $stmt->fetchAll();
echo "Number of packages: " . count($packages) . "<br>";
foreach ($packages as $p) {
    print_r($p);
    echo "<br>";
}

// Check flight table
echo "<h2>Flight Table</h2>";
$stmt = $pdo->query("SELECT * FROM flight LIMIT 5");
$flights = $stmt->fetchAll();
echo "Number of flights: " . count($flights) . "<br>";
foreach ($flights as $f) {
    print_r($f);
    echo "<br>";
}
?>