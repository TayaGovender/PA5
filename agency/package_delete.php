<?php
// agency/package_delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('agency');

$pdo       = get_db();
$agency_id = $_SESSION['agency_id'];
$pkg_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pkg_id) {
    // Ensuring an agency can only delete their own packages
    $stmt = $pdo->prepare('DELETE FROM Travel_package WHERE package_ID = ? AND agency_ID = ?');
    $stmt->execute([$pkg_id, $agency_id]);
}

header('Location: /tripistry/agency/dashboard.php?msg=Package+deleted');
exit;
