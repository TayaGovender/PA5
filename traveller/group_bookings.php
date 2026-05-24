<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo = get_db();
$traveler_id = $_SESSION['traveler_id'];

// Handle joining a group
$join_success = '';
$join_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_group'])) {
    $group_id = (int)$_POST['group_id'];
    
    // Check if already joined this group
    $check_stmt = $pdo->prepare("SELECT * FROM group_booking WHERE group_booking_ID = ? AND traveler_ID = ?");
    $check_stmt->execute([$group_id, $traveler_id]);
    
    if ($check_stmt->fetch()) {
        $join_error = "You have already joined this group!";
    } else {
        // Check if group has capacity
        $group_stmt = $pdo->prepare("SELECT current_capacity, max_capacity, status, package_ID, agency_ID, destination, price_per_person, departure_date, return_date FROM group_booking WHERE group_booking_ID = ? AND traveler_ID IS NULL");
        $group_stmt->execute([$group_id]);
        $group = $group_stmt->fetch();
        
        if (!$group) {
            $join_error = "Group not found!";
        } elseif ($group['status'] === 'Full') {
            $join_error = "This group is already full!";
        } elseif ($group['current_capacity'] >= $group['max_capacity']) {
            $join_error = "This group has reached its maximum capacity!";
        } else {
            // Insert new row for this traveler joining the group
            $insert_stmt = $pdo->prepare("
                INSERT INTO group_booking (package_ID, agency_ID, traveler_ID, destination, status, price_per_person, max_capacity, current_capacity, departure_date, return_date, booking_date, payment_status) 
                VALUES (?, ?, ?, ?, 'Open', ?, ?, 1, ?, ?, CURDATE(), 'Pending')
            ");
            $insert_stmt->execute([
                $group['package_ID'], 
                $group['agency_ID'], 
                $traveler_id, 
                $group['destination'],
                $group['price_per_person'],
                $group['max_capacity'],
                $group['departure_date'],
                $group['return_date']
            ]);
            
            // Update the current capacity of the master group
            $update_stmt = $pdo->prepare("
                UPDATE group_booking SET current_capacity = current_capacity + 1 
                WHERE group_booking_ID = ?
            ");
            $update_stmt->execute([$group_id]);
            
            $join_success = "You have successfully joined the group!";
        }
    }
}

// Handle leaving a group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_group'])) {
    $group_id = (int)$_POST['group_id'];
    
    // Get the master group ID
    $master_stmt = $pdo->prepare("SELECT group_booking_ID FROM group_booking WHERE package_ID = (SELECT package_ID FROM group_booking WHERE group_booking_ID = ?) AND traveler_ID IS NULL");
    $master_stmt->execute([$group_id]);
    $master = $master_stmt->fetch();
    
    // Delete the traveler's booking
    $delete_stmt = $pdo->prepare("DELETE FROM group_booking WHERE group_booking_ID = ? AND traveler_ID = ? AND payment_status != 'Paid'");
    if ($delete_stmt->execute([$group_id, $traveler_id])) {
        // Update master group capacity
        if ($master) {
            $update_stmt = $pdo->prepare("UPDATE group_booking SET current_capacity = current_capacity - 1 WHERE group_booking_ID = ?");
            $update_stmt->execute([$master['group_booking_ID']]);
        }
        $join_success = "You have left the group.";
    } else {
        $join_error = "Failed to leave group. You may have already paid.";
    }
}

// Get available group trips (only master groups where traveler_ID is NULL)
$available_groups = $pdo->prepare("
    SELECT g.*, tp.package_name, f.country, tp.duration as package_duration
    FROM group_booking g
    JOIN travel_package tp ON g.package_ID = tp.package_ID
    LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
    WHERE g.status IN ('Open')
    AND g.departure_date >= CURDATE()
    AND g.current_capacity < g.max_capacity
    AND g.traveler_ID IS NULL
    ORDER BY g.departure_date ASC
");
$available_groups->execute();
$groups = $available_groups->fetchAll();

// Get traveller's joined groups
$my_groups = $pdo->prepare("
    SELECT g.*, tp.package_name, f.country, tp.duration as package_duration
    FROM group_booking g
    JOIN travel_package tp ON g.package_ID = tp.package_ID
    LEFT JOIN flight f ON tp.flight_ID = f.flight_ID
    WHERE g.traveler_ID = ?
    AND g.departure_date >= CURDATE()
    ORDER BY g.departure_date ASC
");
$my_groups->execute([$traveler_id]);
$my_group_trips = $my_groups->fetchAll();

// Get group count for stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM group_booking WHERE traveler_ID = ? AND departure_date >= CURDATE()");
$stmt->execute([$traveler_id]);
$total_joined = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry — Group Travel</title>
    <link rel="stylesheet" href="/tripistry/css/browse.css">
    <style>
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(216,141,20,0.2);
            border-radius: 12px;
            padding: 15px 25px;
            text-align: center;
            flex: 1;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #d88d14;
        }
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
        }
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            color: #d88d14;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(216,141,20,0.3);
        }
        .group-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(216,141,20,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, border-color 0.2s;
        }
        .group-card:hover {
            border-color: #d88d14;
            transform: translateY(-2px);
        }
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .group-header h3 {
            color: #d88d14;
            margin: 0;
            font-size: 1.3rem;
        }
        .badge-open {
            background: rgba(46,204,113,0.15);
            color: #2ecc71;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .badge-pending {
            background: rgba(241,196,15,0.15);
            color: #f1c40f;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .group-details {
            margin: 15px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.9rem;
        }
        .detail-label {
            color: rgba(255,255,255,0.5);
        }
        .detail-value {
            color: white;
        }
        .price {
            font-size: 1.5rem;
            color: #d88d14;
            font-weight: bold;
            margin: 10px 0;
        }
        .spots-info {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.5);
            margin-bottom: 15px;
        }
        .capacity-bar {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            margin: 10px 0;
            overflow: hidden;
        }
        .capacity-fill {
            height: 100%;
            background: #d88d14;
            border-radius: 3px;
        }
        .btn-join, .btn-leave {
            padding: 10px 20px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            transition: opacity 0.2s;
        }
        .btn-join {
            background: linear-gradient(135deg, #d88d14, #fca822);
            color: #1a0a00;
        }
        .btn-leave {
            background: rgba(231,76,60,0.15);
            color: #e74c3c;
            border: 1px solid rgba(231,76,60,0.3);
        }
        .btn-join:hover, .btn-leave:hover {
            opacity: 0.85;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
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
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
            .stats-bar {
                flex-direction: column;
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
    <h1>👥 Group Travel</h1>
    <p class="page-subtitle">Join group trips, save money, and meet fellow travellers</p>

    <?php if ($join_success): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($join_success) ?></div>
    <?php endif; ?>

    <?php if ($join_error): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($join_error) ?></div>
    <?php endif; ?>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-number"><?= count($groups) ?></div>
            <div class="stat-label">Available Groups</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= $total_joined ?></div>
            <div class="stat-label">Groups You've Joined</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= count($my_group_trips) ?></div>
            <div class="stat-label">Upcoming Trips</div>
        </div>
    </div>

    <div class="two-columns">
        <!-- Available Group Trips -->
        <div>
            <div class="section-title">✈️ Available Group Trips</div>
            <?php if (empty($groups)): ?>
                <div class="empty-state">
                    No group trips available at the moment.<br>
                    Check back later for new group departures!
                </div>
            <?php else: ?>
                <?php foreach ($groups as $group): 
                    $spots_taken = $group['current_capacity'];
                    $spots_left = $group['max_capacity'] - $spots_taken;
                    $percentage = ($group['max_capacity'] > 0) ? ($spots_taken / $group['max_capacity']) * 100 : 0;
                ?>
                    <div class="group-card">
                        <div class="group-header">
                            <h3><?= htmlspecialchars($group['package_name']) ?></h3>
                            <span class="badge-open">Open</span>
                        </div>
                        <div class="group-details">
                            <div class="detail-row">
                                <span class="detail-label">📍 Destination</span>
                                <span class="detail-value"><?= htmlspecialchars($group['country'] ?? $group['destination']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📅 Departure Date</span>
                                <span class="detail-value"><?= htmlspecialchars($group['departure_date']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">⏱️ Duration</span>
                                <span class="detail-value"><?= htmlspecialchars($group['package_duration']) ?> days</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">👥 Group Size</span>
                                <span class="detail-value"><?= $spots_taken ?> / <?= $group['max_capacity'] ?> travellers</span>
                            </div>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-fill" style="width: <?= $percentage ?>%;"></div>
                        </div>
                        <div class="spots-info"><?= $spots_left ?> spots remaining</div>
                        <div class="price">R <?= number_format($group['price_per_person']) ?> <span style="font-size: 0.8rem;">per person</span></div>
                        
                        <?php if ($spots_left > 0): ?>
                            <form method="POST" onsubmit="return confirm('Join this group trip for R <?= number_format($group['price_per_person']) ?>?');">
                                <input type="hidden" name="group_id" value="<?= $group['group_booking_ID'] ?>">
                                <button type="submit" name="join_group" class="btn-join">✈️ Join This Group</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- My Joined Groups -->
        <div>
            <div class="section-title">🎒 My Group Trips</div>
            <?php if (empty($my_group_trips)): ?>
                <div class="empty-state">
                    You haven't joined any group trips yet.<br>
                    Browse available groups above to start your adventure!
                </div>
            <?php else: ?>
                <?php foreach ($my_group_trips as $group): ?>
                    <div class="group-card">
                        <div class="group-header">
                            <h3><?= htmlspecialchars($group['package_name']) ?></h3>
                            <span class="badge-pending"><?= $group['payment_status'] ?? 'Pending' ?></span>
                        </div>
                        <div class="group-details">
                            <div class="detail-row">
                                <span class="detail-label">📍 Destination</span>
                                <span class="detail-value"><?= htmlspecialchars($group['country'] ?? $group['destination']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📅 Departure Date</span>
                                <span class="detail-value"><?= htmlspecialchars($group['departure_date']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📅 Booked On</span>
                                <span class="detail-value"><?= htmlspecialchars($group['booking_date']) ?></span>
                            </div>
                        </div>
                        <div class="price">R <?= number_format($group['price_per_person']) ?> <span style="font-size: 0.8rem;">total</span></div>
                        
                        <?php if ($group['departure_date'] > date('Y-m-d') && ($group['payment_status'] ?? 'Pending') !== 'Paid'): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to leave this group?');">
                                <input type="hidden" name="group_id" value="<?= $group['group_booking_ID'] ?>">
                                <button type="submit" name="leave_group" class="btn-leave">🚪 Leave Group</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>