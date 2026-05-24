<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

// Get database connection
$pdo = get_db();

$traveler_id = $_SESSION['traveler_id'];

// Get package_id from URL if present
$preselect_package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;
$preselect_package_name = '';

if ($preselect_package_id) {
    // Changed flight_date to flight_rate
    $stmt = $pdo->prepare("SELECT f.country FROM travel_package tp LEFT JOIN flight f ON tp.flight_ID = f.flight_ID WHERE tp.package_ID = ?");
    $stmt->execute([$preselect_package_id]);
    $package = $stmt->fetch();
    if ($package) {
        $preselect_package_name = $package['country'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry — My Reviews</title>
    <link rel="stylesheet" href="/tripistry/css/browse.css">
    <style>
        .reviews-container {
            max-width: 1200px;
            margin: 100px auto 50px auto;
            padding: 0 20px;
        }
        .search-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .search-bar input {
            flex: 1;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(216,141,20,0.3);
            border-radius: 8px;
            color: white;
        }
        .two-columns {
            display: flex;
            gap: 40px;
        }
        .column {
            flex: 1;
        }
        .column h2 {
            font-family: 'Bebas Neue', sans-serif;
            color: #d88d14;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(216,141,20,0.3);
        }
        .agency-item, .package-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(216,141,20,0.15);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .agency-name, .package-name {
            font-weight: 500;
        }
        .stars {
            color: #d88d14;
            margin-left: 10px;
        }
        .btn-rate {
            background: linear-gradient(135deg, #d88d14, #fca822);
            color: #1a0a00;
            padding: 6px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .btn-rate:hover {
            opacity: 0.9;
        }
        modal {
            background: #2e1200;
            border: 1px solid #d88d14;
            border-radius: 12px;
            padding: 20px;
            width: 400px;
        }
        modal::backdrop {
            background: rgba(0,0,0,0.7);
        }
        .rating-input {
            display: flex;
            gap: 10px;
            font-size: 2rem;
            margin: 20px 0;
        }
        .rating-input span {
            cursor: pointer;
            color: #555;
        }
        .rating-input span.active {
            color: #d88d14;
        }
        .back-link {
            display: inline-block;
            color: #d88d14;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-link:hover {
            color: #fca822;
        }
    </style>
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
    <a href="/tripistry/traveller/reviews.php">My Reviews</a>
    <a href="/tripistry/logout.php">Sign out</a>
  </div>
</nav>

<div class="reviews-container">
    <?php if ($preselect_package_id): ?>
        <a href="/tripistry/traveller/package_view.php?id=<?= $preselect_package_id ?>" class="back-link">← Back to Package</a>
    <?php endif; ?>
    
    <h1>My Reviews</h1>
    <p class="page-subtitle">Rate agencies and packages you've booked</p>

    <div class="search-bar">
        <input type="text" id="search_agency" placeholder="Search agencies...">
        <input type="text" id="search_package" placeholder="Search packages...">
    </div>

    <div class="two-columns">
        <div class="column">
            <h2>Agencies</h2>
            <div id="agencies"></div>
        </div>
        <div class="column">
            <h2>Packages</h2>
            <div id="packages"></div>
        </div>
    </div>
</div>

<dialog id="rateModal">
    <h3>Rate Item</h3>
    <div id="modalContent"></div>
    <div class="rating-input" id="modalStars">
        <span data-value="1">★</span>
        <span data-value="2">★</span>
        <span data-value="3">★</span>
        <span data-value="4">★</span>
        <span data-value="5">★</span>
    </div>
    <textarea id="modalReview" placeholder="Write your review..." rows="3" style="width:100%; margin:10px 0; padding:8px;"></textarea>
    <button id="submitReview" style="background:#d88d14; color:#1a0a00; padding:10px; border:none; border-radius:5px; cursor:pointer;">Submit Review</button>
    <button id="closeModal" style="margin-left:10px; padding:10px; cursor:pointer;">Cancel</button>
</dialog>

<script>
// Pass PHP variable to JavaScript
var TRAVELER_ID = <?= json_encode($traveler_id) ?>;
var PRESELECT_PACKAGE = '<?= htmlspecialchars($preselect_package_name) ?>';
</script>
<script src="/tripistry/js/reviews.js"></script>

<?php if ($preselect_package_name): ?>
<script>
    // Auto-fill the package search when page loads
    document.addEventListener('DOMContentLoaded', function() {
        var packageSearch = document.getElementById('search_package');
        if (packageSearch && PRESELECT_PACKAGE) {
            packageSearch.value = PRESELECT_PACKAGE;
            // Call getPackages function from reviews.js
            if (typeof getPackages === 'function') {
                getPackages();
            }
        }
    });
</script>
<?php endif; ?>

</body>
</html>