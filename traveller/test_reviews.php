<?php
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();

echo "<h1>Database Test for Reviews</h1>";

// Test 1: Check agencies table
echo "<h2>1. Agencies Table</h2>";
$stmt = $pdo->query("SELECT * FROM agency LIMIT 5");
$agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Number of agencies: " . count($agencies) . "<br>";
echo "<pre>";
print_r($agencies);
echo "</pre>";

// Test 2: Check travel_package table
echo "<h2>2. Travel Packages Table</h2>";
$stmt = $pdo->query("SELECT * FROM travel_package LIMIT 5");
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Number of packages: " . count($packages) . "<br>";
echo "<pre>";
print_r($packages);
echo "</pre>";

// Test 3: Check packages with flight info
echo "<h2>3. Packages with Flight Info</h2>";
$stmt = $pdo->query("
    SELECT tp.package_ID, tp.total_cost_price, f.country, f.flight_duration 
    FROM travel_package tp 
    LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
    LIMIT 5
");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($results);
echo "</pre>";

// Test 4: Direct API call simulation
echo "<h2>4. API Response for getAgency</h2>";
$stmt = $pdo->query("SELECT agency_ID, city as agency_name FROM agency");
$agencyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Agency data to be sent to JS:<br>";
echo "<pre>";
print_r($agencyData);
echo "</pre>";

echo "<h2>5. API Response for getPackages</h2>";
$stmt = $pdo->query("
    SELECT tp.package_ID, COALESCE(f.country, CONCAT('Package ', tp.package_ID)) as package_name
    FROM travel_package tp
    LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
");
$packageData = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Package data to be sent to JS:<br>";
echo "<pre>";
print_r($packageData);
echo "</pre>";
?>