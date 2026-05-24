<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo    = get_db();
$search = trim($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry — Browse Packages</title>
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
    <a href="/tripistry/traveller/reviews.php">My Reviews</a>
    <a href="/tripistry/logout.php">Sign out</a>
  </div>
</nav>

<div class="page-content">
  <h1 class="page-title">Travel Packages</h1>
  <p class="page-subtitle">Browse and compare packages from our agencies</p>

  <div class="filters-console" style="display: flex; gap: 15px; margin-bottom: 2rem; align-items: center; flex-wrap: wrap;">
    <input type="text" id="search" placeholder="Search by package name..." class="search-input" style="flex: 1; min-width: 200px;">
    <input type="number" id="maxPrice" placeholder="Max Price (ZAR)" class="search-input" style="min-width: 150px;">
    <select id="sort" class="search-input" style="min-width: 150px;">
        <option value="">Sort By</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="rating_desc">Highest Rated</option>
        <option value="date_asc">Earliest Flight</option>
    </select>
    <button type="button" onclick="loadPackages()" class="search-btn">Apply Filters</button>
    <button type="button" onclick="resetFilters()" class="search-btn" style="background-color: #6c757d;">Reset</button>
  </div>

  <div id="packagesContainer" class="cards-grid">
    <div class="loading" style="text-align: center; padding: 40px;">Loading packages...</div>
  </div>
</div>

<script src="/tripistry/js/packages.js"></script>

</body>
</html>