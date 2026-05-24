<?php
// agency/package_components.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('agency');

$pdo       = get_db();
$agency_id = $_SESSION['agency_id'];
$pkg_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify package ownership
$stmt = $pdo->prepare('SELECT tp.*, f.country AS flight_dest FROM travel_package tp LEFT JOIN flight f ON tp.flight_ID = f.flight_ID WHERE tp.package_ID = ? AND tp.agency_ID = ?');
$stmt->execute([$pkg_id, $agency_id]);
$pkg = $stmt->fetch();
if (!$pkg) { 
    header('Location: /tripistry/agency/dashboard.php'); 
    exit; 
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle Flight changes
    if ($action === 'update_flight') {
        $flight_id = !empty($_POST['flight_ID']) ? (int)$_POST['flight_ID'] : null;
        $stmt = $pdo->prepare('UPDATE travel_package SET flight_ID = ? WHERE package_ID = ? AND agency_ID = ?');
        $stmt->execute([$flight_id, $pkg_id, $agency_id]);
        $msg = 'Flight updated successfully.';
    }
    
    // Handle Accommodation changes
    if ($action === 'update_accommodation') {
        $accom_id = !empty($_POST['accommodation_ID']) ? (int)$_POST['accommodation_ID'] : null;
        $stmt = $pdo->prepare('UPDATE travel_package SET accommodation_ID = ? WHERE package_ID = ? AND agency_ID = ?');
        $stmt->execute([$accom_id, $pkg_id, $agency_id]);
        $msg = 'Accommodation updated successfully.';
    }
    
    // Handle Attractions
    if ($action === 'add_attraction') {
        $aid = (int)$_POST['attraction_ID'];
        $pdo->prepare('INSERT IGNORE INTO package_attraction (package_ID, attraction_ID) VALUES (?, ?)')->execute([$pkg_id, $aid]);
        $msg = 'Attraction added successfully.';
    }
    if ($action === 'remove_attraction') {
        $aid = (int)$_POST['attraction_ID'];
        $pdo->prepare('DELETE FROM package_attraction WHERE package_ID = ? AND attraction_ID = ?')->execute([$pkg_id, $aid]);
        $msg = 'Attraction removed.';
    }
    
    // Handle Restaurants
    if ($action === 'add_restaurant') {
        $rid = (int)$_POST['restaurant_ID'];
        $pdo->prepare('INSERT IGNORE INTO package_restaurant (package_ID, restaurant_ID) VALUES (?, ?)')->execute([$pkg_id, $rid]);
        $msg = 'Restaurant added successfully.';
    }
    if ($action === 'remove_restaurant') {
        $rid = (int)$_POST['restaurant_ID'];
        $pdo->prepare('DELETE FROM package_restaurant WHERE package_ID = ? AND restaurant_ID = ?')->execute([$pkg_id, $rid]);
        $msg = 'Restaurant removed.';
    }
}

// Fetch current package details
$current_flight_id = $pkg['flight_ID'];
$current_accom_id = $pkg['accommodation_ID'] ?? $pkg['accomodation_ID'] ?? null;

// Fetch current linked attractions
$stmt = $pdo->prepare('SELECT a.* FROM package_attraction pa JOIN attraction a ON pa.attraction_ID = a.attraction_ID WHERE pa.package_ID = ?');
$stmt->execute([$pkg_id]);
$cur_attractions = $stmt->fetchAll();
$cur_attr_ids = array_column($cur_attractions, 'attraction_ID');

// Fetch current linked restaurants
$stmt = $pdo->prepare('SELECT r.* FROM package_restaurant pr JOIN restaurant r ON pr.restaurant_ID = r.restaurant_ID WHERE pr.package_ID = ?');
$stmt->execute([$pkg_id]);
$cur_restaurants = $stmt->fetchAll();
$cur_rest_ids = array_column($cur_restaurants, 'restaurant_ID');

// Fetch all available items for dropdowns
$avail_flights = $pdo->query('SELECT flight_ID, country, flight_duration FROM flight ORDER BY country')->fetchAll();
$avail_accommodations = $pdo->query('SELECT accomodation_ID, city, country, cost_per_night FROM accomodation ORDER BY country, city')->fetchAll();
$avail_attractions = $pdo->query('SELECT * FROM attraction ORDER BY country, city')->fetchAll();
$avail_restaurants = $pdo->query('SELECT * FROM restaurant ORDER BY country, city')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Components — Package #<?= $pkg_id ?></title>
    <link rel="stylesheet" href="/tripistry/css/dashboard.css">
    <style>
        .component-box { background: rgba(255,255,255,0.03); border:1px solid rgba(216,141,20,0.2); border-radius:8px; padding:20px; margin-bottom:25px; }
        .tag { display: inline-flex; align-items: center; background:#572901; padding:5px 12px; border-radius:20px; font-size:0.85rem; margin:5px; border:1px solid #d88d14; }
        .tag button { background:none; border:none; color:#ff6464; margin-left:8px; cursor:pointer; font-weight:bold; }
        select, button.btn-add { padding:10px; background:#2e1200; color:white; border:1px solid #d88d14; border-radius:5px; cursor:pointer; }
        select:hover, button.btn-add:hover { background:#3d1a00; }
        .current-item { background: rgba(216,141,20,0.15); padding:8px 12px; border-radius:5px; margin-top:10px; font-size:0.9rem; }
        .current-item strong { color: #d88d14; }
        hr { border-color: rgba(216,141,20,0.2); margin: 20px 0; }
        h3 { color: #d88d14; margin-bottom: 15px; }
    </style>
</head>
<body>
<nav class="nav">
  <a href="/tripistry/agency/dashboard.php" class="nav-brand">Tripistry Workspace</a>
  <div class="nav-links"><a href="/tripistry/logout.php" class="btn-logout">Logout</a></div>
</nav>

<div class="main-container" style="padding-top: 80px; max-width: 900px; margin: 0 auto;">
  <h2>Manage Components for Package #<?= $pkg_id ?></h2>
  <a href="/tripistry/agency/dashboard.php" style="color:#d88d14; text-decoration:none;">← Back to Dashboard</a>
  <br><br>

  <?php if($msg): ?>
    <div style="background:rgba(46,204,113,0.2); color:#2ecc71; padding:10px; border-radius:5px; margin-bottom:15px;">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- FLIGHT SECTION -->
  <div class="component-box">
     <h3>✈️ Package Flight</h3>
     <div class="current-item">
         <strong>Current Flight:</strong> 
         <?php 
         $current_flight = null;
         foreach ($avail_flights as $f) {
             if ($f['flight_ID'] == $current_flight_id) {
                 $current_flight = $f;
                 break;
             }
         }
         if ($current_flight): ?>
             <?= htmlspecialchars($current_flight['country']) ?> (<?= $current_flight['flight_duration'] ?> hours)
         <?php else: ?>
             No flight assigned
         <?php endif; ?>
     </div>
     <form method="POST" style="margin-top:15px;">
        <input type="hidden" name="action" value="update_flight">
        <select name="flight_ID" style="width:70%;">
           <option value="">— Select a Flight —</option>
           <?php foreach($avail_flights as $f): ?>
             <option value="<?= $f['flight_ID'] ?>" <?= $current_flight_id == $f['flight_ID'] ? 'selected' : '' ?>>
                 <?= htmlspecialchars($f['country']) ?> (<?= $f['flight_duration'] ?> hours)
             </option>
           <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-add">✈️ Update Flight</button>
     </form>
  </div>

  <!-- ACCOMMODATION SECTION -->
  <div class="component-box">
     <h3>🏨 Package Accommodation</h3>
     <div class="current-item">
         <strong>Current Accommodation:</strong> 
         <?php 
         $current_accom = null;
         foreach ($avail_accommodations as $a) {
             if ($a['accomodation_ID'] == $current_accom_id) {
                 $current_accom = $a;
                 break;
             }
         }
         if ($current_accom): ?>
             <?= htmlspecialchars($current_accom['city']) ?>, <?= htmlspecialchars($current_accom['country']) ?> (R<?= number_format($current_accom['cost_per_night']) ?>/night)
         <?php else: ?>
             No accommodation assigned
         <?php endif; ?>
     </div>
     <form method="POST" style="margin-top:15px;">
        <input type="hidden" name="action" value="update_accommodation">
        <select name="accommodation_ID" style="width:70%;">
           <option value="">— Select Accommodation —</option>
           <?php foreach($avail_accommodations as $a): ?>
             <option value="<?= $a['accomodation_ID'] ?>" <?= $current_accom_id == $a['accomodation_ID'] ? 'selected' : '' ?>>
                 <?= htmlspecialchars($a['city']) ?>, <?= htmlspecialchars($a['country']) ?> (R<?= number_format($a['cost_per_night']) ?>/night)
             </option>
           <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-add">🏨 Update Accommodation</button>
     </form>
  </div>

  <hr>

  <!-- ATTRACTIONS SECTION -->
  <div class="component-box">
     <h3>🗺️ Package Attractions</h3>
     <div style="margin-bottom:15px;">
       <?php if(empty($cur_attractions)): ?>
         <p style="color:gray;">No attractions linked yet.</p>
       <?php else: ?>
         <?php foreach ($cur_attractions as $a): ?>
           <span class="tag">
             <?= htmlspecialchars($a['description']) ?> (<?= htmlspecialchars($a['city']) ?>)
             <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="remove_attraction">
                <input type="hidden" name="attraction_ID" value="<?= $a['attraction_ID'] ?>">
                <button type="submit">✕</button>
             </form>
           </span>
         <?php endforeach; ?>
       <?php endif; ?>
     </div>
     <form method="POST">
        <input type="hidden" name="action" value="add_attraction">
        <select name="attraction_ID" required style="width:70%;">
           <option value="">— Select an Attraction to link —</option>
           <?php foreach($avail_attractions as $a): if(!in_array($a['attraction_ID'], $cur_attr_ids)): ?>
             <option value="<?= $a['attraction_ID'] ?>">
                 <?= htmlspecialchars($a['description']) ?> (<?= htmlspecialchars($a['city']) ?>)
             </option>
           <?php endif; endforeach; ?>
        </select>
        <button type="submit" class="btn-add">➕ Add Attraction</button>
     </form>
  </div>

  <!-- RESTAURANTS SECTION -->
  <div class="component-box">
     <h3>🍽️ Package Restaurants</h3>
     <div style="margin-bottom:15px;">
       <?php if(empty($cur_restaurants)): ?>
         <p style="color:gray;">No restaurants linked yet.</p>
       <?php else: ?>
         <?php foreach ($cur_restaurants as $r): ?>
           <span class="tag">
             <?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['city']) ?>)
             <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="remove_restaurant">
                <input type="hidden" name="restaurant_ID" value="<?= $r['restaurant_ID'] ?>">
                <button type="submit">✕</button>
             </form>
           </span>
         <?php endforeach; ?>
       <?php endif; ?>
     </div>
     <form method="POST">
        <input type="hidden" name="action" value="add_restaurant">
        <select name="restaurant_ID" required style="width:70%;">
           <option value="">— Select a Restaurant to link —</option>
           <?php foreach($avail_restaurants as $r): if(!in_array($r['restaurant_ID'], $cur_rest_ids)): ?>
             <option value="<?= $r['restaurant_ID'] ?>">
                 <?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['city']) ?>)
             </option>
           <?php endif; endforeach; ?>
        </select>
        <button type="submit" class="btn-add">➕ Add Restaurant</button>
     </form>
  </div>
</div>
</body>
</html>