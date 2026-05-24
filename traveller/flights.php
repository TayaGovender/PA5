<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');

$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where   .= ' AND f.country LIKE ?';
    $params[] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT * FROM Flight f $where ORDER BY f.flight_date ASC"
);
$stmt->execute($params);
$flights = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — Flights</title>
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
  <h1 class="page-title">Flights</h1>
  <p class="page-subtitle">Browse available flights</p>

  <form method="GET" class="search-form">
    <input class="search-input" type="text" name="search"
           placeholder="Search by destination country..."
           value="<?= htmlspecialchars($search) ?>">
    <button class="search-btn" type="submit">Search</button>
  </form>

  <div class="results-count"><?= count($flights) ?> flight<?= count($flights) !== 1 ? 's' : '' ?> found</div>

  <?php if (empty($flights)): ?>
    <div class="empty-state">No flights found</div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Flight ID</th>
          <th>Destination</th>
          <th>Date</th>
          <th>Duration</th>
          <th>Type</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($flights as $f): ?>
        <tr>
          <td>#<?= htmlspecialchars($f['flight_ID']) ?></td>
          <td><?= htmlspecialchars($f['country']) ?></td>
          <td><?= htmlspecialchars($f['flight_date']) ?></td>
          <td><?= htmlspecialchars($f['flight_duration']) ?>h</td>
          <td>
            <?php if ($f['is_domestic']): ?>
              <span class="badge-domestic">Domestic</span>
            <?php else: ?>
              <span class="badge-international">International</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>