<?php
// traveller/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo         = get_db();
$traveler_id = $_SESSION['traveler_id'];

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $delete_stmt = $pdo->prepare("DELETE FROM solo_booking WHERE solo_booking_ID = ? AND traveler_ID = ?");
    if ($delete_stmt->execute([$booking_id, $traveler_id])) {
        $delete_success = "Booking cancelled successfully!";
    } else {
        $delete_error = "Failed to cancel booking.";
    }
}

$stmt = $pdo->prepare(
    'SELECT t.first_name, t.last_name, ua.email
     FROM traveller t
     JOIN user_account ua ON ua.traveler_ID = t.traveler_ID
     WHERE t.traveler_ID = ?'
);
$stmt->execute([$traveler_id]);
$traveller = $stmt->fetch();

// Get bookings for this traveller
$stmt = $pdo->prepare(
    'SELECT sb.*, tp.total_cost_price, tp.duration, f.country as destination, tp.package_name
     FROM solo_booking sb
     JOIN travel_package tp ON tp.package_ID = sb.package_ID
     LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
     WHERE sb.traveler_ID = ?
     ORDER BY sb.solo_booking_ID DESC'
);
$stmt->execute([$traveler_id]);
$bookings = $stmt->fetchAll();

$confirmed = count(array_filter($bookings, fn($b) => $b['status'] === 'Confirmed'));
$pending   = count(array_filter($bookings, fn($b) => $b['status'] === 'Pending'));
$cancelled = count(array_filter($bookings, fn($b) => $b['status'] === 'Cancelled'));
$total     = count($bookings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — My Dashboard</title>
<link rel="stylesheet" href="/tripistry/css/dashboard.css">
<style>
    .delete-btn {
        background: rgba(231,76,60,0.15);
        color: #e74c3c;
        border: 1px solid rgba(231,76,60,0.3);
        padding: 5px 12px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.7rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    .delete-btn:hover {
        background: rgba(231,76,60,0.3);
        border-color: #e74c3c;
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
    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .status-cancelled {
        color: #e74c3c;
    }
</style>
</head>
<body>

<nav class="nav">
  <a href="/tripistry/traveller/dashboard.php" class="nav-brand">Tripistry</a>
  <div class="nav-right">
    <span class="nav-badge">🧳 Traveller</span>
    <span class="nav-user"><?= htmlspecialchars($traveller['first_name'] . ' ' . $traveller['last_name']) ?></span>
    <a href="/tripistry/logout.php" class="nav-signout">Sign out</a>
  </div>
</nav>

<div class="page-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">HELLO, <span><?= htmlspecialchars($traveller['first_name']) ?></span></h1>
      <p class="page-subtitle"><?= htmlspecialchars($traveller['email']) ?></p>
    </div>
  </div>

  <?php if (isset($delete_success)): ?>
    <div class="alert-success">✅ <?= htmlspecialchars($delete_success) ?></div>
  <?php endif; ?>
  
  <?php if (isset($delete_error)): ?>
    <div class="alert-error">❌ <?= htmlspecialchars($delete_error) ?></div>
  <?php endif; ?>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-label">Total bookings</div>
      <div class="stat-value"><?= $total ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Confirmed</div>
      <div class="stat-value gold"><?= $confirmed ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending</div>
      <div class="stat-value"><?= $pending ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Cancelled</div>
      <div class="stat-value"><?= $cancelled ?></div>
    </div>
  </div>

  <div class="quick-nav">
    <a href="/tripistry/traveller/browse.php">🌍 Browse Packages</a>
     <a href="/tripistry/traveller/group_bookings.php">👥 Group Travel</a>
    <a href="/tripistry/traveller/flights.php">✈️ Flights</a>
    <a href="/tripistry/traveller/accommodation.php">🏨 Accommodation</a>
    <a href="/tripistry/traveller/attractions.php">🗺️ Attractions</a>
    <a href="/tripistry/traveller/restaurants.php">🍽️ Restaurants</a>
    <a href="/tripistry/traveller/reviews.php">⭐ My Reviews</a>
  </div>

  <div class="section-title">My Bookings</div>

  <div class="table-wrap">
    <?php if (empty($bookings)): ?>
      <div class="empty-state">No bookings yet. <a href="/tripistry/traveller/browse.php">Browse packages</a></div>
    <?php else: ?>
      <table style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="border-bottom: 1px solid rgba(216,141,20,0.2);">
            <th style="text-align: left; padding: 12px;">Booking #</th>
            <th style="text-align: left; padding: 12px;">Package</th>
            <th style="text-align: left; padding: 12px;">Destination</th>
            <th style="text-align: left; padding: 12px;">Cost (ZAR)</th>
            <th style="text-align: left; padding: 12px;">Duration</th>
            <th style="text-align: left; padding: 12px;">Booking Date</th>
            <th style="text-align: left; padding: 12px;">Status</th>
            <th style="text-align: left; padding: 12px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
            <td style="padding: 12px;">#<?= htmlspecialchars($b['solo_booking_ID']) ?></td>
            <td style="padding: 12px;"><?= htmlspecialchars($b['package_name'] ?? '—') ?></td>
            <td style="padding: 12px;"><?= htmlspecialchars($b['destination'] ?? '—') ?></td>
            <td style="padding: 12px;">R <?= number_format($b['total_cost_price']) ?></td>
            <td style="padding: 12px;"><?= htmlspecialchars($b['duration']) ?> days</td>
            <td style="padding: 12px;"><?= htmlspecialchars($b['booking_date']) ?></td>
            <td style="padding: 12px;">
              <span class="badge badge-<?= strtolower(htmlspecialchars($b['status'])) ?>">
                <?= htmlspecialchars($b['status']) ?>
              </span>
            </td>
            <td style="padding: 12px;">
              <?php if ($b['status'] !== 'Cancelled'): ?>
              <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');" style="display: inline;">
                <input type="hidden" name="booking_id" value="<?= $b['solo_booking_ID'] ?>">
                <button type="submit" name="delete_booking" class="delete-btn">🗑️ Cancel</button>
              </form>
              <?php else: ?>
              <span style="color: rgba(255,255,255,0.3); font-size: 0.7rem;">Already cancelled</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>