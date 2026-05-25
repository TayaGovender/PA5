<?php
// traveller/compare.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo = get_db();
$compare_ids = $_GET['ids'] ?? '';
$package_ids = !empty($compare_ids) ? explode(',', $compare_ids) : [];

$packages = [];
if (!empty($package_ids)) {
    $placeholders = str_repeat('?,', count($package_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT tp.package_ID, tp.package_name, tp.total_cost_price, tp.start_date,
               f.country AS flight_country, f.flight_duration,
               ac.city AS accomm_city, ac.cost_per_night, ac.duration AS stay_duration,
               ua.agency_name,
               (SELECT ROUND(AVG(rv.rating_score),1) FROM review rv WHERE rv.package_ID = tp.package_ID) AS avg_review,
               (SELECT COUNT(*) FROM review rv WHERE rv.package_ID = tp.package_ID) AS review_count
        FROM travel_package tp
        LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
        LEFT JOIN accomodation ac ON tp.accomodation_ID = ac.accomodation_ID
        LEFT JOIN user_account ua ON tp.agency_ID = ua.agency_ID AND ua.role = 'agency'
        WHERE tp.package_ID IN ($placeholders)
    ");
    $stmt->execute($package_ids);
    $packages = $stmt->fetchAll();
}

// Remove a package from comparison
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $new_ids = array_diff($package_ids, [$remove_id]);
    $redirect = !empty($new_ids) ? '?ids=' . implode(',', $new_ids) : 'compare.php';
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry — Compare Packages</title>
    <link rel="stylesheet" href="/tripistry/css/browse.css">
    <style>
        .compare-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .selected-packages {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .selected-badge {
            background: rgba(216,141,20,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }
        .remove-compare {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
        }
        .remove-compare:hover {
            color: #ff6464;
        }
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            overflow-x: auto;
            display: block;
        }
        .compare-table th, .compare-table td {
            padding: 15px;
            border: 1px solid rgba(216,141,20,0.15);
            text-align: left;
            vertical-align: top;
        }
        .compare-table th {
            background: rgba(216,141,20,0.1);
            color: #d88d14;
            font-weight: 600;
            width: 140px;
        }
        .package-header {
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .package-name {
            font-size: 1.2rem;
            color: #d88d14;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .agency-name {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
        }
        .price {
            font-size: 1.6rem;
            color: #d88d14;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn-details {
            display: inline-block;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.75rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #d88d14, #fca822);
            color: #1a0a00;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .rating {
            color: #d88d14;
        }
        .best-value {
            color: #2ecc71;
        }
        .standard-value {
            color: #e74c3c;
        }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: rgba(255,255,255,0.4);
        }
        .btn-add-more {
            background: linear-gradient(135deg, #d88d14, #fca822);
            color: #1a0a00;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-clear {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-add-more:hover, .btn-clear:hover {
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            .compare-table {
                font-size: 0.8rem;
            }
            .compare-table th, .compare-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<nav class="nav">
  <a href="/tripistry/traveller/dashboard.php" class="nav-brand">Tripistry</a>
  <div class="nav-links">
    <a href="/tripistry/traveller/dashboard.php">Dashboard</a>
    <a href="/tripistry/traveller/browse.php">Packages</a>
    <a href="/tripistry/traveller/compare.php">Compare</a>
    <a href="/tripistry/traveller/group_bookings.php">Group Travel</a>
    <a href="/tripistry/traveller/flights.php">Flights</a>
    <a href="/tripistry/traveller/accommodation.php">Accommodation</a>
    <a href="/tripistry/traveller/attractions.php">Attractions</a>
    <a href="/tripistry/traveller/restaurants.php">Restaurants</a>
    <a href="/tripistry/traveller/reviews.php">My Reviews</a>
    <a href="/tripistry/logout.php">Sign out</a>
  </div>
</nav>

<div class="page-content">
    <a href="browse.php" class="btn-back">← Back to Browse Packages</a>
    <h1>Compare Travel Packages</h1>
    <p class="page-subtitle">Compare packages side by side to find the best deal</p>

    <div class="compare-header">
        <div>
            <strong>Selected Packages:</strong>
            <div class="selected-packages">
                <?php if (!empty($packages)): ?>
                    <?php foreach ($packages as $pkg): ?>
                        <span class="selected-badge">
                            <?= htmlspecialchars($pkg['package_name']) ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['remove' => $pkg['package_ID']])) ?>" class="remove-compare">✕</a>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span style="color: rgba(255,255,255,0.5);">No packages selected</span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="browse.php" class="btn-add-more">+ Add More Packages</a>
            <a href="javascript:void(0)" onclick="clearCompareAndRedirect()" class="btn-clear">Clear All</a>
        </div>
    </div>

    <?php if (empty($packages)): ?>
        <div class="empty-state">
            No packages selected for comparison.<br><br>
            <a href="browse.php" class="btn-add-more">Browse Packages</a>
        </div>
    <?php elseif (count($packages) == 1): ?>
        <div class="empty-state">
            Add at least one more package to compare.<br><br>
            <a href="browse.php" class="btn-add-more">Add More Packages</a>
        </div>
    <?php else: ?>
        <table class="compare-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <?php foreach ($packages as $pkg): ?>
                        <th>
                            <div class="package-header">
                                <div class="package-name"><?= htmlspecialchars($pkg['package_name']) ?></div>
                                <div class="agency-name">by <?= htmlspecialchars($pkg['agency_name'] ?? 'Agency') ?></div>
                                <div class="price">R <?= number_format($pkg['total_cost_price']) ?></div>
                                <a href="package_view.php?id=<?= $pkg['package_ID'] ?>" class="btn-details">View Details</a>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>📍 Destination</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td><?= htmlspecialchars($pkg['flight_country'] ?? '—') ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>📅 Start Date</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td><?= htmlspecialchars($pkg['start_date']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>✈️ Flight Duration</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td><?= htmlspecialchars($pkg['flight_duration']) ?> hours</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>🏨 Accommodation</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td>
                            <?= htmlspecialchars($pkg['accomm_city'] ?? '—') ?><br>
                            <small>R <?= number_format($pkg['cost_per_night'] ?? 0) ?>/night</small>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>⭐ Rating</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td class="rating">
                            <?php if ($pkg['avg_review']): ?>
                                ★ <?= number_format($pkg['avg_review'], 1) ?> / 5
                                <br><small>(<?= $pkg['review_count'] ?> reviews)</small>
                            <?php else: ?>
                                No reviews yet
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>🏢 Agency</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td><?= htmlspecialchars($pkg['agency_name'] ?? '—') ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>💰 Price per Night</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td>R <?= number_format($pkg['cost_per_night'] ?? 0) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>🎯 Best Value</th>
                    <?php foreach ($packages as $pkg): ?>
                        <td>
                            <?php 
                            $value_score = ($pkg['avg_review'] ?? 0) / max(($pkg['total_cost_price'] / 1000), 0.01);
                            if ($value_score > 0.08): ?>
                                <span class="best-value">✓ Excellent Value</span>
                            <?php elseif ($value_score > 0.04): ?>
                                <span class="best-value">✓ Good Value</span>
                            <?php else: ?>
                                <span class="standard-value">Standard</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>📊 Savings Tip</th>
                    <?php 
                    $cheapest = !empty($packages) ? min(array_column($packages, 'total_cost_price')) : 0;
                    foreach ($packages as $pkg): 
                        $savings = $pkg['total_cost_price'] - $cheapest;
                    ?>
                        <td>
                            <?php if ($savings == 0): ?>
                                <span style="color: #2ecc71;">✓ Best price!</span>
                            <?php else: ?>
                                <span style="color: #f1c40f;">R <?= number_format($savings) ?> more than cheapest</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function clearCompareAndRedirect() {
    localStorage.removeItem('comparePackages');
    window.location.href = 'compare.php';
}
</script>

</body>
</html>