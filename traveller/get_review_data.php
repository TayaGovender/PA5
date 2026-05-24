<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('traveller');

$pdo = get_db();
$traveler_id = $_SESSION['traveler_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$search = $input['search'] ?? '';

$response = ['data' => []];

if ($type === 'getAgency') {
    // Show agencies by agency_name instead of city
    $sql = "SELECT agency_ID, agency_name FROM agency WHERE agency_name LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%"]);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($type === 'getAgencyRating') {
    // Get agency ratings
    $sql = "SELECT a.agency_ID, COALESCE(AVG(rv.rating_score), 0) as rating_score
            FROM agency a
            LEFT JOIN travel_package tp ON tp.agency_ID = a.agency_ID
            LEFT JOIN review rv ON rv.package_ID = tp.package_ID AND rv.traveler_ID = ?
            GROUP BY a.agency_ID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$traveler_id]);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($type === 'getPackages') {
    // Show packages by package_name instead of flight country
    $sql = "SELECT tp.package_ID, tp.package_name
            FROM travel_package tp";
    if (!empty($search)) {
        $sql .= " WHERE tp.package_name LIKE :search";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($type === 'getPackageRating') {
    // Get package ratings by this traveler
    $sql = "SELECT package_ID, rating_score 
            FROM review 
            WHERE traveler_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$traveler_id]);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($type === 'saveReview') {
    $item_type = $input['item_type'] ?? '';
    $item_id = (int)($input['item_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $description = trim($input['description'] ?? '');
    $traveler_id_val = (int)($input['traveler_id'] ?? 0);
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
        exit;
    }
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please write a review']);
        exit;
    }
    if ($item_type === 'package' && $item_id > 0) {
        $check_stmt = $pdo->prepare("SELECT * FROM review WHERE package_ID = ? AND traveler_ID = ?");
        $check_stmt->execute([$item_id, $traveler_id_val]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You have already reviewed this package']);
            exit;
        }
        
        $insert_stmt = $pdo->prepare("INSERT INTO review (package_ID, traveler_ID, rating_score, description, review_date) VALUES (?, ?, ?, ?, CURDATE())");
        if ($insert_stmt->execute([$item_id, $traveler_id_val, $rating, $description])) {
            echo json_encode(['success' => true, 'message' => 'Review saved']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>