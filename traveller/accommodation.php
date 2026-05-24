<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');

$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where   .= ' AND (a.city LIKE ? OR a.country LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT a.*,
            CASE WHEN h.accomodation_ID  IS NOT NULL THEN 'Hotel'
                 WHEN ab.accomodation_ID IS NOT NULL THEN 'Airbnb'
                 ELSE 'Other' END AS accomm_type,
            h.catering_type,
            ab.property_type
     FROM Accomodation a
     LEFT JOIN Hotel  h  ON h.accomodation_ID  = a.accomodation_ID
     LEFT JOIN Airbnb ab ON ab.accomodation_ID = a.accomodation_ID
     $where
     ORDER BY a.cost_per_night ASC"
);
$stmt->execute($params);
$accommodations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — Accommodation</title>
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
  <h1 class="page-title">Accommodation</h1>
  <p class="page-subtitle">Hotels and Airbnbs included in packages</p>

  <form method="GET" class="search-form">
    <input class="search-input" type="text" name="search"
           placeholder="Search by city or country..."
           value="<?= htmlspecialchars($search) ?>">
    <button class="search-btn" type="submit">Search</button>
  </form>

  <div class="results-count"><?= count($accommodations) ?> result<?= count($accommodations) !== 1 ? 's' : '' ?> found</div>

  <?php if (empty($accommodations)): ?>
    <div class="empty-state">No accommodation found</div>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($accommodations as $a): ?>
      <div class="card">
        <span class="badge-type"><?= htmlspecialchars($a['accomm_type']) ?></span>
        <div class="card-title"><?= htmlspecialchars($a['city']) ?></div>
        <div class="card-location"><?= htmlspecialchars($a['country']) ?></div>
        <div class="card-divider"></div>
        <div class="card-row">
          <span class="card-label">Address</span>
          <span class="card-value"><?= htmlspecialchars($a['street_number'].' '.$a['street']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">Duration</span>
          <span class="card-value"><?= htmlspecialchars($a['duration']) ?> nights</span>
        </div>
        <?php if ($a['catering_type']): ?>
        <div class="card-row">
          <span class="card-label">Catering</span>
          <span class="card-value"><?= htmlspecialchars($a['catering_type']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($a['property_type']): ?>
        <div class="card-row">
          <span class="card-label">Property</span>
          <span class="card-value"><?= htmlspecialchars($a['property_type']) ?></span>
        </div>
        <?php endif; ?>
        <div class="card-price">R <?= number_format($a['cost_per_night']) ?> <span>/ night</span></div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>