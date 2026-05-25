<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

if (!isset($_GET['id'])) {
    die("Package ID missing");
}

$id = (int)$_GET['id'];
$pdo = get_db();
$traveler_id = $_SESSION['traveler_id'];

$sql = "
SELECT
    tp.package_ID,
    tp.package_name,
    tp.agency_ID,
    tp.total_cost_price,
    tp.start_date,
    f.country AS flight_country,
    f.flight_duration,
    ag.agency_name,
    ag.city AS agency_city,
    ag.phone_number,
    ac.country AS hotel_country,
    ac.city AS hotel_city,
    ac.cost_per_night,
    ac.duration AS stay_duration,
    ac.name AS hotel_name
FROM travel_package tp
LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
LEFT JOIN agency ag ON tp.agency_ID = ag.agency_ID
LEFT JOIN accomodation ac ON tp.accomodation_ID = ac.accomodation_ID
WHERE tp.package_ID = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$package = $stmt->fetch();

if (!$package) {
    die("Package not found");
}

// Handle booking submission
$booking_error = '';
$booking_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_package'])) {
    $package_id = (int)$_POST['package_id'];
    $agency_id = $package['agency_ID'];
    $destination = $package['package_name'] ?? $package['flight_country'] ?? 'Package';
    
    // Check if already booked
    $check_stmt = $pdo->prepare("SELECT * FROM solo_booking WHERE package_ID = ? AND traveler_ID = ?");
    $check_stmt->execute([$package_id, $traveler_id]);
    
    if ($check_stmt->fetch()) {
        $booking_error = "You have already booked this package!";
    } else {
        // Insert booking
        $insert_stmt = $pdo->prepare("
            INSERT INTO solo_booking (package_ID, agency_ID, traveler_ID, destination, status) 
            VALUES (?, ?, ?, ?, 'Pending')
        ");
        if ($insert_stmt->execute([$package_id, $agency_id, $traveler_id, $destination])) {
            $booking_success = "Package booked successfully! Status: Pending confirmation.";
        } else {
            $booking_error = "Booking failed. Please try again.";
        }
    }
}

// Handle quick review submission
$review_error = '';
$review_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $package_id = (int)$_POST['package_id'];
    $rating = (int)$_POST['rating'];
    $description = trim($_POST['description']);
    
    if ($rating < 1 || $rating > 5) {
        $review_error = "Rating must be between 1 and 5 stars.";
    } elseif (empty($description)) {
        $review_error = "Please write a review.";
    } else {
        $check_stmt = $pdo->prepare("SELECT * FROM review WHERE package_ID = ? AND traveler_ID = ?");
        $check_stmt->execute([$package_id, $traveler_id]);
        
        if ($check_stmt->fetch()) {
            $review_error = "You have already reviewed this package!";
        } else {
            $insert_stmt = $pdo->prepare("INSERT INTO review (package_ID, traveler_ID, rating_score, description, review_date) VALUES (?, ?, ?, ?, CURDATE())");
            if ($insert_stmt->execute([$package_id, $traveler_id, $rating, $description])) {
                $review_success = "Thank you for your review!";
                // Refresh to show the new review
                header("Location: package_view.php?id=" . $package_id);
                exit;
            } else {
                $review_error = "Failed to save review.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Tripistry — View Package Details</title>
    <link rel="stylesheet" href="/tripistry/css/browse.css">
    <link rel="stylesheet" href="/tripistry/css/package_view.css">
    <style>
        .attractions-grid, .restaurants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        
        .attraction-card, .restaurant-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(216, 141, 20, 0.15);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
        }
        
        .attraction-card:hover, .restaurant-card:hover {
            transform: translateY(-4px);
            border-color: #d88d14;
            box-shadow: 0 8px 25px rgba(216, 141, 20, 0.15);
        }
        
        .attraction-card img, .restaurant-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .attraction-card h3, .restaurant-card h3 {
            margin: 0 0 8px 0;
            color: #d88d14;
            font-size: 1rem;
        }
        
        .attraction-card .attraction-cost {
            margin-top: 10px;
            font-weight: bold;
        }
        
        .free-entry {
            color: #2ecc71;
        }
        
        .paid-entry {
            color: #d88d14;
        }
        
        .stars {
            color: #d88d14;
            letter-spacing: 2px;
        }
        
        .quick-review-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(216,141,20,0.2);
        }
        
        .review-stars {
            display: flex;
            gap: 10px;
            font-size: 2rem;
            cursor: pointer;
        }
        
        .review-stars span {
            color: #555;
            transition: color 0.2s;
        }
        
        .review-stars span.active, .review-stars span:hover {
            color: #d88d14;
        }
        
        .alert-success {
            background: rgba(46,204,113,0.2);
            color: #2ecc71;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 3px solid #2ecc71;
        }
        
        .alert-error {
            background: rgba(231,76,60,0.2);
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 3px solid #e74c3c;
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

<div class="details-container">
    <a href="browse.php" class="back-btn">← Back to Packages Gallery</a>
    
    <?php if ($booking_success): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($booking_success) ?></div>
    <?php endif; ?>
    
    <?php if ($booking_error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($booking_error) ?></div>
    <?php endif; ?>
    
    <?php if ($review_success): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($review_success) ?></div>
    <?php endif; ?>
    
    <?php if ($review_error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($review_error) ?></div>
    <?php endif; ?>
    
    <h1><?= htmlspecialchars($package['package_name'] ?? $package['flight_country']) ?> Tour Itinerary</h1>
    
    <div class="main-grid">
        <div class="info-box">
            <h2>Package Overview</h2>
            <p><strong>Package Name:</strong> <?= htmlspecialchars($package['package_name'] ?? '—') ?></p>
            <p><strong>Start Date:</strong> <?= htmlspecialchars($package['start_date']) ?></p>
            <p><strong>Flight Duration:</strong> <?= htmlspecialchars($package['flight_duration']) ?> Hours</p>
            <p><strong>Flight Destination:</strong> <?= htmlspecialchars($package['flight_country'] ?? '—') ?></p>
            <p><strong>Accommodation:</strong> <?= htmlspecialchars($package['hotel_name'] ?? 'Hotel') ?> in <?= htmlspecialchars($package['hotel_city']) ?>, <?= htmlspecialchars($package['hotel_country']) ?> (<?= htmlspecialchars($package['stay_duration']) ?> Nights)</p>
            <p><strong>Arranged By Agency:</strong> <?= htmlspecialchars($package['agency_name'] ?? $package['agency_city']) ?> (Contact: <?= htmlspecialchars($package['phone_number']) ?>)</p>
        </div>
        
        <div class="price-card">
            <span>Total Standard Cost</span>
            <div class="price-val">R <?= number_format($package['total_cost_price']) ?></div>
            <span>Includes flights, standard lodging, curated excursions & dining entries</span>
        </div>
    </div>

    <div class="sub-section-title">Included Attractions</div>
    <div class="attractions-grid">
        <?php
        $attractionSQL = "
            SELECT a.* FROM package_attraction pa 
            INNER JOIN attraction a ON pa.attraction_ID = a.attraction_ID 
            WHERE pa.package_ID = ?
        ";
        $attractionStmt = $pdo->prepare($attractionSQL);
        $attractionStmt->execute([$id]);
        $attractions = $attractionStmt->fetchAll();

        if (empty($attractions)) {
            echo "<p style='color: rgba(255,255,255,0.5);'>No attractions mapped to this package itinerary yet.</p>";
        } else {
            foreach ($attractions as $attraction) {
                $image_url = !empty($attraction['image_url']) ? $attraction['image_url'] : 'https://placehold.co/400x300?text=' . urlencode($attraction['description']);
                ?>
                <div class="attraction-card">
                    <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($attraction['description']) ?>">
                    <div style="padding: 15px;">
                        <h3><?= htmlspecialchars($attraction['description']) ?></h3>
                        <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.7);">📍 <?= htmlspecialchars($attraction['city']) ?>, <?= htmlspecialchars($attraction['country']) ?></p>
                        <?php if ($attraction['cost'] > 0): ?>
                            <p class="attraction-cost paid-entry">Entry: R <?= number_format($attraction['cost']) ?></p>
                        <?php else: ?>
                            <p class="attraction-cost free-entry">✓ Free Entry</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <div class="sub-section-title">Curated Dining Spots</div>
    <div class="restaurants-grid">
        <?php
        $restaurantSQL = "
            SELECT r.* FROM package_restaurant pr 
            INNER JOIN restaurant r ON pr.restaurant_ID = r.restaurant_ID 
            WHERE pr.package_ID = ?
        ";
        $restaurantStmt = $pdo->prepare($restaurantSQL);
        $restaurantStmt->execute([$id]);
        $restaurants = $restaurantStmt->fetchAll();

        if (empty($restaurants)) {
            echo "<p style='color: rgba(255,255,255,0.5);'>No dining plans mapped to this package itinerary yet.</p>";
        } else {
            foreach ($restaurants as $restaurant) {
                $image_url = !empty($restaurant['image_url']) ? $restaurant['image_url'] : 'https://placehold.co/400x300?text=' . urlencode($restaurant['name']);
                ?>
                <div class="restaurant-card">
                    <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                    <div style="padding: 15px;">
                        <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                        <p class="stars">⭐ Rating: <?= str_repeat('★', (int)$restaurant['rating']) ?></p>
                        <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.7);">📍 <?= htmlspecialchars($restaurant['city']) ?>, <?= htmlspecialchars($restaurant['country']) ?></p>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <div class="sub-section-title">Traveller Reviews</div>
    <div class="components-flex-grid">
        <?php
        $reviewSQL = "SELECT r.*, t.first_name, t.last_name 
                      FROM review r 
                      LEFT JOIN traveller t ON r.traveler_ID = t.traveler_ID
                      WHERE r.package_ID = ? 
                      ORDER BY r.review_date DESC";
        $reviewStmt = $pdo->prepare($reviewSQL);
        $reviewStmt->execute([$id]);
        $reviews = $reviewStmt->fetchAll();

        if (empty($reviews)) {
            echo "<p>No reviews written for this package yet. Be the first to write a review!</p>";
        } else {
            foreach ($reviews as $review) {
                $rating = (int)$review['rating_score'];
                $stars = '';
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        $stars .= '★';
                    } else {
                        $stars .= '☆';
                    }
                }
                
                $reviewer_name = isset($review['first_name']) ? htmlspecialchars($review['first_name']) : 'Anonymous';
                if (isset($review['last_name'])) {
                    $reviewer_name .= ' ' . htmlspecialchars($review['last_name']);
                }
                
                echo "
                <div class='small-card' style='width: 100%;'>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;'>
                        <div>
                            <strong style='color: #d88d14;'>" . $reviewer_name . "</strong>
                            <span style='color: #d88d14; margin-left: 10px;'>" . $stars . "</span>
                        </div>
                        <span style='color: rgba(255,255,255,0.4); font-size: 0.8rem;'>" . htmlspecialchars($review['review_date']) . "</span>
                    </div>
                    <p style='margin: 0; color: rgba(255,255,255,0.8); line-height: 1.5;'>\"" . htmlspecialchars($review['description']) . "\"</p>
                </div>";
            }
        }
        ?>
    </div>

    <!-- BOOKING SECTION -->
    <div class="booking-section">
        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to book this package?');">
            <input type="hidden" name="package_id" value="<?= $id ?>">
            <button type="submit" name="book_package" class="btn-book">
                📖 Book This Package — R <?= number_format($package['total_cost_price']) ?>
            </button>
        </form>
        <div class="booking-info">
            <p>Free cancellation up to 7 days before departure</p>
            <p>Secure booking with instant confirmation</p>
            <p>24/7 customer support included</p>
        </div>
    </div>

    <!-- QUICK REVIEW SECTION -->
    <div class="quick-review-section">
        <h3 style="color: #d88d14; margin-bottom: 20px;">✍️ Write a Review</h3>
        
        <?php
        // Check if user has already reviewed this package
        $check_review_sql = "SELECT * FROM review WHERE package_ID = ? AND traveler_ID = ?";
        $check_stmt = $pdo->prepare($check_review_sql);
        $check_stmt->execute([$id, $traveler_id]);
        $has_reviewed = $check_stmt->fetch();
        
        if ($has_reviewed): ?>
            <p style="color: #2ecc71;">✅ You have already reviewed this package. Thank you for your feedback!</p>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="package_id" value="<?= $id ?>">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; color: rgba(255,255,255,0.7);">Your Rating:</label>
                    <div class="review-stars" id="reviewStars">
                        <span data-value="1">☆</span>
                        <span data-value="2">☆</span>
                        <span data-value="3">☆</span>
                        <span data-value="4">☆</span>
                        <span data-value="5">☆</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; color: rgba(255,255,255,0.7);">Your Review:</label>
                    <textarea name="description" rows="4" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(216,141,20,0.3); border-radius: 8px; color: white;" placeholder="Share your experience with this package..."></textarea>
                </div>
                <button type="submit" name="submit_review" style="background: linear-gradient(135deg, #d88d14, #fca822); color: #1a0a00; border: none; padding: 10px 25px; border-radius: 50px; cursor: pointer; font-weight: bold;">Submit Review</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Star rating for quick review
var stars = document.querySelectorAll('#reviewStars span');
var ratingInput = document.getElementById('ratingValue');

stars.forEach(function(star, index) {
    star.addEventListener('click', function() {
        var value = parseInt(this.getAttribute('data-value'));
        ratingInput.value = value;
        stars.forEach(function(s, i) {
            if (i < value) {
                s.innerHTML = '★';
                s.style.color = '#d88d14';
            } else {
                s.innerHTML = '☆';
                s.style.color = '#555';
            }
        });
    });
});

// Set initial stars to 5
var initialRating = parseInt(ratingInput.value);
stars.forEach(function(s, i) {
    if (i < initialRating) {
        s.innerHTML = '★';
        s.style.color = '#d88d14';
    }
});
</script>

</body>
</html>