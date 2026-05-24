<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo = get_db();
$search = $_GET['search'] ?? '';
$maxPrice = $_GET['maxPrice'] ?? '';
$sort = $_GET['sort'] ?? '';

// Base SQL query with package_name
$sql = "SELECT tp.package_ID, tp.package_name, tp.total_cost_price, tp.duration,
        f.country AS flight_country, f.flight_duration,
        ac.city AS accomm_city, ac.cost_per_night,
        (SELECT ROUND(AVG(rv.rating_score),1) 
         FROM review rv WHERE rv.package_ID = tp.package_ID) AS avg_review
        FROM travel_package tp
        LEFT JOIN flight f ON f.flight_ID = tp.flight_ID
        LEFT JOIN accomodation ac ON ac.accomodation_ID = tp.accomodation_ID
        WHERE 1=1";

$params = [];

// Add search filter - search by package name only
if ($search !== '') {
    $sql .= " AND tp.package_name LIKE ?";
    $params[] = "%$search%";
}

// Add max price filter
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $sql .= " AND tp.total_cost_price <= ?";
    $params[] = $maxPrice;
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY tp.total_cost_price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY tp.total_cost_price DESC";
        break;
    case 'rating_desc':
        $sql .= " ORDER BY avg_review DESC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY tp.duration ASC";
        break;
    default:
        $sql .= " ORDER BY tp.total_cost_price ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (empty($results)) {
    echo '<div class="empty-state">No packages found</div>';
} else {
    foreach ($results as $row) {
        $rating = $row['avg_review'] ? '⭐ ' . $row['avg_review'] . ' / 5' : 'No reviews';
        $destination = $row['package_name'] ?? $row['flight_country'] ?? 'Unknown';
        ?>
        <div class="card">
            <div class="card-tag">Package #<?= htmlspecialchars($row['package_ID']) ?></div>
            <div class="card-title"><?= htmlspecialchars($destination) ?></div>
            <div class="card-location"><?= htmlspecialchars($row['accomm_city'] ?? '—') ?></div>
            <div class="card-divider"></div>
            <div class="card-row">
                <span class="card-label">Flight duration</span>
                <span class="card-value"><?= ($row['flight_duration'] ? $row['flight_duration'] . 'h' : '—') ?></span>
            </div>
            <div class="card-row">
                <span class="card-label">Per night</span>
                <span class="card-value"><?= ($row['cost_per_night'] ? 'R ' . number_format($row['cost_per_night']) : '—') ?></span>
            </div>
            <div class="card-row">
                <span class="card-label">Rating</span>
                <span class="card-value"><?= $rating ?></span>
            </div>
            <div class="card-price">R <?= number_format($row['total_cost_price']) ?> <span>total</span></div>
            <a href="package_view.php?id=<?= (int)$row['package_ID'] ?>" class="btn-view">View Package →</a>
        </div>
        <?php
    }
}
?>