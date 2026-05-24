<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');

$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where   .= ' AND (r.name LIKE ? OR r.city LIKE ? OR r.country LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT * FROM Restaurant r $where ORDER BY r.rating DESC, r.name ASC"
);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — Restaurants</title>
<link rel="stylesheet" href="/tripistry/css/browse.css">
</head>
<body>

<nav class="nav">
  <a href="/tripistry/traveller/dashboard.php" class="nav-brand">Tripistry</a>
  <div class="nav-links">
    <a href="/tripistry/traveller/dashboard.php">Dashboard</a>
    <a href="/tripistry/traveller/browse.php">Packages</a>
    <a href="/tripistry/traveller/group_bookings.php">Group Travel</a>
    <a href="/tripistry/traveller/flights.php">Flights</a>
    <a href="/tripistry/traveller/accommodation.php">Accommodation</a>
    <a href="/tripistry/traveller/attractions.php">Attractions</a>
    <a href="/tripistry/traveller/restaurants.php">Restaurants</a>
    <a href="/tripistry/logout.php">Sign out</a>
  </div>
</nav>

<div class="page-content">
  <h1 class="page-title">Restaurants</h1>
  <p class="page-subtitle">Top restaurants included in travel packages</p>

  <form method="GET" class="search-form">
    <input class="search-input" type="text" name="search"
           placeholder="Search by name, city or country..."
           value="<?= htmlspecialchars($search) ?>">
    <button class="search-btn" type="submit">Search</button>
  </form>

  <div class="results-count"><?= count($restaurants) ?> restaurant<?= count($restaurants) !== 1 ? 's' : '' ?> found</div>

  <?php if (empty($restaurants)): ?>
    <div class="empty-state">No restaurants found</div>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($restaurants as $r): ?>
      <div class="card">
        <div class="card-title"><?= htmlspecialchars($r['name']) ?></div>
        <div class="card-location">📍 <?= htmlspecialchars($r['city'].', '.$r['country']) ?></div>
        <div class="card-divider"></div>
        <div class="card-row">
          <span class="card-label">Rating</span>
          <span class="stars"><?= str_repeat('⭐', (int)$r['rating']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">Address</span>
          <span class="card-value"><?= htmlspecialchars($r['street_number'].' '.$r['street']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">Postal code</span>
          <span class="card-value"><?= htmlspecialchars($r['postal_code']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>