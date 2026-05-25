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
    $sql = "SELECT agency_ID, agency_name FROM agency WHERE agency_name LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%"]);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($type === 'getAgencyRating') {
    // Check if agency_review table exists, if not, return empty
    try {
        $sql = "SELECT agency_ID, COALESCE(AVG(rating_score), 0) as rating_score
                FROM agency_review 
                WHERE traveler_ID = ?
                GROUP BY agency_ID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$traveler_id]);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $response['data'] = [];
    }
    
} elseif ($type === 'getPackages') {
    $sql = "SELECT tp.package_ID, tp.package_name
            FROM travel_package tp
            ORDER BY tp.package_ID";
    if (!empty($search)) {
        $sql = "SELECT tp.package_ID, tp.package_name
                FROM travel_package tp
                WHERE tp.package_name LIKE :search
                ORDER BY tp.package_ID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($type === 'getPackageRating') {
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
    } elseif ($item_type === 'agency' && $item_id > 0) {
        // Create agency_review table if it doesn't exist
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS agency_review (
                    review_ID INT AUTO_INCREMENT PRIMARY KEY,
                    agency_ID INT NOT NULL,
                    traveler_ID INT NOT NULL,
                    rating_score INT NOT NULL,
                    description TEXT NOT NULL,
                    review_date DATE NOT NULL,
                    FOREIGN KEY (agency_ID) REFERENCES agency(agency_ID) ON DELETE CASCADE,
                    FOREIGN KEY (traveler_ID) REFERENCES traveller(traveler_ID) ON DELETE CASCADE,
                    UNIQUE KEY unique_agency_review (agency_ID, traveler_ID)
                )
            ");
        } catch (PDOException $e) {
            // Table might already exist
        }
        
        // Check if already reviewed this agency
        $check_stmt = $pdo->prepare("SELECT * FROM agency_review WHERE agency_ID = ? AND traveler_ID = ?");
        $check_stmt->execute([$item_id, $traveler_id_val]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You have already reviewed this agency']);
            exit;
        }
        
        $insert_stmt = $pdo->prepare("INSERT INTO agency_review (agency_ID, traveler_ID, rating_score, description, review_date) VALUES (?, ?, ?, ?, CURDATE())");
        if ($insert_stmt->execute([$item_id, $traveler_id_val, $rating, $description])) {
            echo json_encode(['success' => true, 'message' => 'Agency review saved']);
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