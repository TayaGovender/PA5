<?php
// agency/package_form.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('agency');

$pdo       = get_db();
$agency_id = $_SESSION['agency_id'];

$pkg_id    = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_edit   = $pkg_id !== null;
$errors    = [];
$pkg       = ['package_name' => '', 'flight_ID' => '', 'accomodation_ID' => '', 'total_cost_price' => '', 'duration' => ''];

if ($is_edit) {
    $stmt = $pdo->prepare('SELECT * FROM travel_package WHERE package_ID = ? AND agency_ID = ?');
    $stmt->execute([$pkg_id, $agency_id]);
    $pkg = $stmt->fetch();
    if (!$pkg) { 
        header('Location: /tripistry/agency/dashboard.php'); 
        exit; 
    }
}

$flights = $pdo->query('SELECT flight_ID, country, flight_duration FROM flight ORDER BY country')->fetchAll();
$accoms  = $pdo->query('SELECT accomodation_ID, city, country, cost_per_night FROM accomodation ORDER BY country')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_name = trim($_POST['package_name'] ?? '');
    $flight_id    = !empty($_POST['flight_ID']) ? (int)$_POST['flight_ID'] : null;
    $accom_id     = !empty($_POST['accomodation_ID']) ? (int)$_POST['accomodation_ID'] : null;
    $total_cost   = !empty($_POST['total_cost_price']) ? (float)$_POST['total_cost_price'] : 0;
    $duration     = !empty($_POST['duration']) ? $_POST['duration'] : '';

    if (empty($package_name)) {
        $errors[] = "Package name is required.";
    }
    if (empty($duration)) {
        $errors[] = "Duration/Start date is required.";
    }

    if (empty($errors)) {
        if ($is_edit) {
            $stmt = $pdo->prepare('UPDATE travel_package SET package_name = ?, flight_ID = ?, accomodation_ID = ?, total_cost_price = ?, duration = ? WHERE package_ID = ? AND agency_ID = ?');
            $stmt->execute([$package_name, $flight_id, $accom_id, $total_cost, $duration, $pkg_id, $agency_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO travel_package (agency_ID, package_name, flight_ID, accomodation_ID, total_cost_price, duration) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$agency_id, $package_name, $flight_id, $accom_id, $total_cost, $duration]);
        }
        header('Location: /tripistry/agency/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tripistry — <?= $is_edit ? 'Edit' : 'Create' ?> Package</title>
    <link rel="stylesheet" href="/tripistry/css/dashboard.css">
</head>
<body>
<nav class="nav">
  <a href="/tripistry/agency/dashboard.php" class="nav-brand">Tripistry Workspace</a>
  <div class="nav-links"><a href="/tripistry/logout.php" class="btn-logout">Logout</a></div>
</nav>

<div class="main-container" style="padding-top: 80px; max-width: 600px; margin: 0 auto;">
  <h2><?= $is_edit ? '✏️ Edit Package #' . htmlspecialchars($pkg_id) : '➕ Create New Travel Package' ?></h2>
  <a href="/tripistry/agency/dashboard.php" style="color:#d88d14; text-decoration:none; font-size:0.9rem;">← Back to Dashboard</a>
  <br><br>

  <?php if (!empty($errors)): ?>
    <div style="background:rgba(231,76,60,0.2); padding:10px; border-radius:5px; margin-bottom:15px; color:#ff6464;">
        <?php foreach($errors as $e): ?> <p style="margin:0;"><?= htmlspecialchars($e) ?></p> <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="form-card" style="background:rgba(255,255,255,0.03); padding:2rem; border-radius:10px; border:1px solid rgba(216,141,20,0.15);">
    
    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Package Name</label>
      <input type="text" name="package_name" value="<?= htmlspecialchars($pkg['package_name']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Flight</label>
      <select name="flight_ID" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;">
        <option value="">— No flight —</option>
        <?php foreach ($flights as $f): ?>
          <option value="<?= $f['flight_ID'] ?>" <?= $pkg['flight_ID'] == $f['flight_ID'] ? 'selected' : '' ?>>
            #<?= $f['flight_ID'] ?> — Destination: <?= htmlspecialchars($f['country']) ?> (<?= $f['flight_duration'] ?>h)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Accommodation</label>
      <select name="accomodation_ID" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;">
        <option value="">— No accommodation —</option>
        <?php foreach ($accoms as $a): ?>
          <option value="<?= $a['accomodation_ID'] ?>" <?= $pkg['accomodation_ID'] == $a['accomodation_ID'] ? 'selected' : '' ?>>
            #<?= $a['accomodation_ID'] ?> — <?= htmlspecialchars($a['city'] . ', ' . $a['country']) ?> (R<?= number_format($a['cost_per_night']) ?>/night)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; margin-bottom:5px;">Total Cost Price (R)</label>
      <input type="number" name="total_cost_price" min="0" value="<?= htmlspecialchars($pkg['total_cost_price']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
    </div>

    <div style="margin-bottom:20px;">
      <label style="display:block; margin-bottom:5px;">Start Date</label>
      <input type="date" name="duration" value="<?= htmlspecialchars($pkg['duration']) ?>" style="width:100%; padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px;" required>
    </div>

    <button type="submit" class="btn-primary" style="cursor:pointer; width:100%; padding:12px; border:none; border-radius:50px; background:linear-gradient(to right, #572901, #d88d14); color:white; font-weight:bold;">
        <?= $is_edit ? 'Save Changes' : 'Create Package' ?>
    </button>
  </form>
</div>
</body>
</html>