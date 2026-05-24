<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');

$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where   .= ' AND (a.city LIKE ? OR a.country LIKE ? OR a.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT a.*, at.opening_times, at.closing_times
     FROM Attraction a
     LEFT JOIN Attraction_times at ON at.attraction_ID = a.attraction_ID
     $where
     ORDER BY a.country, a.city"
);
$stmt->execute($params);
$attractions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — Attractions</title>
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
  <h1 class="page-title">Attractions</h1>
  <p class="page-subtitle">Tourist attractions around the world</p>

  <form method="GET" class="search-form">
    <input class="search-input" type="text" name="search"
           placeholder="Search by name, city or country..."
           value="<?= htmlspecialchars($search) ?>">
    <button class="search-btn" type="submit">Search</button>
  </form>

  <div class="results-count"><?= count($attractions) ?> attraction<?= count($attractions) !== 1 ? 's' : '' ?> found</div>

  <?php if (empty($attractions)): ?>
    <div class="empty-state">No attractions found</div>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($attractions as $a): ?>
      <div class="card">
        <div class="card-title"><?= htmlspecialchars($a['description']) ?></div>
        <div class="card-location">📍 <?= htmlspecialchars($a['city'].', '.$a['country']) ?></div>
        <div class="card-divider"></div>
        <div class="card-row">
          <span class="card-label">Address</span>
          <span class="card-value"><?= htmlspecialchars($a['street_number'].' '.$a['street']) ?></span>
        </div>
        <?php if ($a['opening_times']): ?>
        <div class="card-row">
          <span class="card-label">Hours</span>
          <span class="card-value">
            <?= htmlspecialchars(substr($a['opening_times'],0,5)) ?> – <?= htmlspecialchars(substr($a['closing_times'],0,5)) ?>
          </span>
        </div>
        <?php endif; ?>
        <?php if ($a['cost'] == 0): ?>
          <span class="badge-free">Free Entry</span>
        <?php else: ?>
          <div class="card-price">R <?= number_format($a['cost']) ?> <span>entry</span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>