<?php
// agency/group_delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('agency');

$pdo       = get_db();
$agency_id = $_SESSION['agency_id'];
$group_id  = isset($_GET['id'])  ? (int)$_GET['id']  : 0;
$pkg_id    = isset($_GET['pkg']) ? (int)$_GET['pkg'] : 0;

if ($group_id && $pkg_id) {
    $stmt = $pdo->prepare('DELETE FROM Group_Booking WHERE group_booking_ID = ? AND package_ID = ? AND agency_ID = ?');
    $stmt->execute([$group_id, $pkg_id, $agency_id]);
}

header('Location: /tripistry/agency/dashboard.php?msg=Group+trip+deleted');
exit;