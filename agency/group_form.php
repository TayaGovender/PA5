<?php
// agency/group_form.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('agency');

$pdo       = get_db();
$agency_id = $_SESSION['agency_id'];

$group_id       = isset($_GET['id'])  ? (int)$_GET['id']  : null;
$preselect_pkg  = isset($_GET['pkg']) ? (int)$_GET['pkg'] : null;
$is_edit        = $group_id !== null;
$errors         = [];

$grp = [
    'package_ID'       => $preselect_pkg ?? '',
    'traveler_ID'      => null, 
    'destination'      => '',
    'status'           => 'Open',
    'price'            => '',
    'max_capacity'     => '',
    'current_capacity' => 0,
];

if ($is_edit) {
    $stmt = $pdo->prepare('SELECT * FROM Group_Booking WHERE group_booking_ID = ? AND agency_ID = ?');
    $stmt->execute([$group_id, $agency_id]);
    $grp = $stmt->fetch();
    if (!$grp) { 
        header('Location: /tripistry/agency/dashboard.php'); 
        exit; 
    }
}

$packages = $pdo->prepare('SELECT tp.package_ID, f.country AS dest FROM Travel_package tp LEFT JOIN Flight f ON tp.flight_ID = f.flight_ID WHERE tp.agency_ID = ?');
$packages->execute([$agency_id]);
$package_list = $packages->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id       = (int)$_POST['package_ID'];
    $destination      = trim($_POST['destination']);
    $status           = $_POST['status'];
    $price            = (float)$_POST['price'];
    $max_capacity     = (int)$_POST['max_capacity'];
    $current_capacity = (int)$_POST['current_capacity'];

    if (empty($destination)) $errors[] = "Destination name cannot be blank.";
    if ($max_capacity <= 0) $errors[] = "Max capacity must be greater than 0.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";

    if (empty($errors)) {
        if ($is_edit) {
            $stmt = $pdo->prepare('UPDATE Group_Booking SET package_ID = ?, destination = ?, status = ?, price = ?, max_capacity = ?, current_capacity = ? WHERE group_booking_ID = ? AND agency_ID = ?');
            $stmt->execute([$package_id, $destination, $status, $price, $max_capacity, $current_capacity, $group_id, $agency_id]);
        } else {
            // Explicitly set traveler_ID to NULL
            $stmt = $pdo->prepare('INSERT INTO Group_Booking (package_ID, agency_ID, traveler_ID, destination, status, price, max_capacity, current_capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$package_id, $agency_id, null, $destination, $status, $price, $max_capacity, $current_capacity]);
        }
        header('Location: /tripistry/agency/dashboard.php?msg=Group+trip+saved');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tripistry — Manage Group Trip</title>
    <link rel="stylesheet" href="/tripistry/css/dashboard.css">
</head>
<body>
<nav class="nav">
  <a href="/tripistry/agency/dashboard.php" class="nav-brand">Tripistry Workspace</a>
  <div class="nav-links"><a href="/tripistry/logout.php" class="btn-logout">Logout</a></div>
</nav>

<div class="main-container" style="padding-top: 80px; max-width: 600px; margin: 0 auto;">
  <h2><?= $is_edit ? '👥 Edit Group Trip #' . htmlspecialchars($group_id) : '👥 Setup New Group Trip' ?></h2>
  <a href="/tripistry/agency/dashboard.php" style="color:#d88d14; text-decoration:none;">← Back to Dashboard</a>
  <br><br>

  <?php if (!empty($errors)): ?>
    <div style="background:rgba(231,76,60,0.2); padding:10px; border-radius:5px; margin-bottom:15px; color:#ff6464;">
        <?php foreach($errors as $e): ?> <p style="margin:0;"><?= htmlspecialchars($e) ?></p> <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="form-card" style="background:rgba(255,255,255,0.03); padding:2rem; border-radius:10px; border:1px solid rgba(216,141,20,0.15);">
    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Select Linked Base Package</label>
      <select name="package_ID" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
        <option value="">— Select a Package —</option>
        <?php foreach ($package_list as $p): ?>
          <option value="<?= $p['package_ID'] ?>" <?= $grp['package_ID'] == $p['package_ID'] ? 'selected' : '' ?>>
             Package #<?= $p['package_ID'] ?> (To: <?= htmlspecialchars($p['dest'] ?? 'Unspecified') ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Group Marketing Title / Destination</label>
      <input type="text" name="destination" value="<?= htmlspecialchars($grp['destination']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Group Status</label>
      <select name="status" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;">
         <option value="Open" <?= ($grp['status'] ?? '') === 'Open' ? 'selected' : '' ?>>Open</option>
         <option value="Full" <?= ($grp['status'] ?? '') === 'Full' ? 'selected' : '' ?>>Full</option>
         <option value="Cancelled" <?= ($grp['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Price per Person (R)</label>
      <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($grp['price']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Max Capacity Limits</label>
      <input type="number" name="max_capacity" value="<?= htmlspecialchars($grp['max_capacity']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
    </div>

    <div style="margin-bottom:20px;">
      <label style="display:block; margin-bottom:5px;">Current Bookings Count</label>
      <input type="number" name="current_capacity" value="<?= htmlspecialchars($grp['current_capacity']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;">
    </div>

    <button type="submit" style="cursor:pointer; width:100%; padding:12px; border:none; border-radius:50px; background:linear-gradient(to right, #572901, #d88d14); color:white; font-weight:bold;">
        <?= $is_edit ? 'Save Changes' : 'Create Group Trip' ?>
    </button>
  </form>
</div>
</body>
</html>