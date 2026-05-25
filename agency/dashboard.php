<?php
// agency/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('agency');

$pdo       = get_db();
$agency_id = $_SESSION['agency_id'];

// Fetch agency profile details
$stmt = $pdo->prepare(
    'SELECT ua.agency_name, ua.email, a.city, a.phone_number
     FROM user_account ua
     JOIN agency a ON a.agency_ID = ua.agency_ID
     WHERE ua.agency_ID = ?'
);
$stmt->execute([$agency_id]);
$agency = $stmt->fetch();

// Calculate total packages this specific agency is offering
$stmt = $pdo->prepare('SELECT COUNT(*) FROM travel_package WHERE agency_ID = ?');
$stmt->execute([$agency_id]);
$total_packages = (int)$stmt->fetchColumn();

// Calculate total group trips
$stmt = $pdo->prepare('SELECT COUNT(*) FROM group_booking WHERE agency_ID = ?');
$stmt->execute([$agency_id]);
$total_groups = (int)$stmt->fetchColumn();

// Calculate total solo traveller bookings made on this agency's packages
$stmt = $pdo->prepare('SELECT COUNT(*) FROM solo_booking WHERE agency_ID = ?');
$stmt->execute([$agency_id]);
$total_bookings = (int)$stmt->fetchColumn();

// Fetch this agency's packages - FIXED: removed tp.duration, added tp.start_date
$pkgs = $pdo->prepare('
    SELECT tp.package_ID, tp.package_name, tp.total_cost_price, tp.start_date,
           f.country AS flight_dest, f.flight_date, f.flight_duration,
           ac.city AS accomm_city, ac.cost_per_night,
           (SELECT ROUND(AVG(rv.rating_score),1) FROM review rv WHERE rv.package_ID = tp.package_ID) AS avg_review
    FROM travel_package tp
    LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
    LEFT JOIN accomodation ac ON tp.accomodation_ID = ac.accomodation_ID
    WHERE tp.agency_ID = ?
    ORDER BY tp.package_ID ASC
');
$pkgs->execute([$agency_id]);
$packages = $pkgs->fetchAll();

// Fetch this agency's group trips
$groups_stmt = $pdo->prepare('
    SELECT gb.*, tp.package_name, tp.total_cost_price as base_price, f.country AS flight_country
    FROM group_booking gb
    LEFT JOIN travel_package tp ON gb.package_ID = tp.package_ID
    LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
    WHERE gb.agency_ID = ?
    ORDER BY gb.group_booking_ID DESC
');
$groups_stmt->execute([$agency_id]);
$group_trips = $groups_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry Workspace — Dashboard</title>
    <link rel="stylesheet" href="/tripistry/css/agency_dashboard.css">
    <style>
        .two-column-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .section-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(216, 141, 20, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            letter-spacing: 2px;
            color: #d88d14;
            margin: 0;
        }
        
        .badge-open { background: rgba(46,204,113,0.15); color: #2ecc71; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; }
        .badge-full { background: rgba(241,196,15,0.15); color: #f1c40f; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; }
        .badge-cancelled { background: rgba(231,76,60,0.15); color: #e74c3c; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; }
        
        .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.7rem;
        }
        
        .group-info {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.5);
            margin-top: 0.3rem;
        }
        
        .capacity-bar {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .capacity-fill {
            height: 100%;
            background: #d88d14;
            border-radius: 2px;
        }
    </style>
</head>
<body>

<nav class="nav">
  <a href="/tripistry/agency/dashboard.php" class="nav-brand">Trip<span>istry</span> Workspace</a>
  <div class="nav-links">
    <a href="/tripistry/logout.php" class="btn-logout">Logout</a>
  </div>
</nav>

<div class="main-container">
  
  <div class="welcome-box">
    <h1>Welcome back, <span><?= htmlspecialchars($agency['agency_name'] ?? 'Agency Partner') ?></span></h1>
    <p>Manage your custom travel itineraries, structural packages, and live team groups from your operational control center.</p>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value"><?= $total_packages ?></div>
      <div class="stat-label">Total Packages Offered</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-value"><?= $total_groups ?></div>
      <div class="stat-label">Active Group Trips</div>
    </div>

    <div class="stat-card">
      <div class="stat-value"><?= $total_bookings ?></div>
      <div class="stat-label">Traveller Bookings Made</div>
    </div>
  </div>

  <!-- TWO COLUMN LAYOUT FOR PACKAGES AND GROUPS -->
  <div class="two-column-layout">
    
    <!-- LEFT COLUMN: SINGLE PACKAGES -->
    <div class="section-card">
      <div class="section-header">
        <div class="section-title">✈️ Single Packages</div>
        <a href="/tripistry/agency/package_form.php" class="btn-create">➕ Create New Package</a>
      </div>
      
      <div class="table-wrap" style="margin-top: 0;">
        <?php if (empty($packages)): ?>
          <div class="empty-state">
            No packages currently being offered. <a href="/tripistry/agency/package_form.php">Create one now</a>.
          </div>
        <?php else: ?>
          <table class="mini-table" style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="border-bottom: 1px solid rgba(216,141,20,0.2);">
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Package Name</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Destination</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Start Date</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Price</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($packages as $p): ?>
              <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 0.7rem 0.5rem;"><?= htmlspecialchars($p['package_name'] ?? 'Package #' . $p['package_ID']) ?></td>
                <td style="padding: 0.7rem 0.5rem;"><?= htmlspecialchars($p['flight_dest'] ?? '—') ?></td>
                <td style="padding: 0.7rem 0.5rem;"><?= htmlspecialchars($p['start_date']) ?></td>
                <td style="padding: 0.7rem 0.5rem;">R <?= number_format($p['total_cost_price']) ?></td>
                <td style="padding: 0.7rem 0.5rem;">
                  <div class="actions" style="display: flex; gap: 0.3rem;">
                    <a class="btn-action btn-sm" href="/tripistry/agency/package_form.php?id=<?= $p['package_ID'] ?>">✏️</a>
                    <a class="btn-action btn-sm" href="/tripistry/agency/package_components.php?id=<?= $p['package_ID'] ?>">🧩</a>
                    <a class="btn-action danger btn-sm" href="/tripistry/agency/package_delete.php?id=<?= $p['package_ID'] ?>" 
                       onclick="return confirm('Delete this package?');">🗑️</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT COLUMN: GROUP PACKAGES -->
    <div class="section-card">
      <div class="section-header">
        <div class="section-title">👥 Group Packages</div>
        <a href="/tripistry/agency/group_form.php" class="btn-create">➕ Create Group Trip</a>
      </div>
      
      <div class="table-wrap" style="margin-top: 0;">
        <?php if (empty($group_trips)): ?>
          <div class="empty-state">
            No group trips created yet. <a href="/tripistry/agency/group_form.php">Create one now</a>.
          </div>
        <?php else: ?>
          <table class="mini-table" style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="border-bottom: 1px solid rgba(216,141,20,0.2);">
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Package Name</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Status</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Capacity</th>
                <th style="text-align: left; padding: 0.5rem; color: #d88d14; font-size: 0.7rem;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($group_trips as $g): ?>
              <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 0.7rem 0.5rem;"><?= htmlspecialchars($g['package_name'] ?? 'Package #' . $g['package_ID']) ?></td>
                <td style="padding: 0.7rem 0.5rem;">
                  <span class="badge-<?= strtolower($g['status']) ?>">
                    <?= htmlspecialchars($g['status']) ?>
                  </span>
                </td>
                <td style="padding: 0.7rem 0.5rem;">
                  <?= $g['current_capacity'] ?>/<?= $g['max_capacity'] ?> booked
                  <div class="capacity-bar">
                    <div class="capacity-fill" style="width: <?= ($g['max_capacity'] > 0) ? (($g['current_capacity'] / $g['max_capacity']) * 100) : 0 ?>%;"></div>
                  </div>
                </td>
                <td style="padding: 0.7rem 0.5rem;">
                  <div class="actions" style="display: flex; gap: 0.3rem;">
                    <a class="btn-action btn-sm" href="/tripistry/agency/group_form.php?id=<?= $g['group_booking_ID'] ?>">✏️</a>
                    <a class="btn-action danger btn-sm" href="/tripistry/agency/group_delete.php?id=<?= $g['group_booking_ID'] ?>&pkg=<?= $g['package_ID'] ?>" 
                       onclick="return confirm('Delete this group trip?');">🗑️</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
    
  </div>
</div>

</body>
</html>