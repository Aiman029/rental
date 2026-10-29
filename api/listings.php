<?php
// API: Listings Search, Filter & Fetch

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$isVerified = isVerifiedStudent();
$category = $_GET['category'] ?? 'rental';
$location = $_GET['location'] ?? 'all';
$availability = $_GET['availability'] ?? 'all';
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
$search = trim($_GET['search'] ?? '');
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    $pdo = getDbConnection();

    // If specific ID requested
    if ($propertyId !== null) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.name AS landlord_account_name, u.phone AS landlord_account_phone 
            FROM properties p
            JOIN users u ON p.landlord_id = u.user_id
            WHERE p.property_id = :id AND p.status = 'Approved'
            LIMIT 1
        ");
        $stmt->execute(['id' => $propertyId]);
        $prop = $stmt->fetch();

        if (!$prop) {
            json_response(['success' => false, 'message' => 'Kediaman tidak ditemui.'], 404);
        }

        // Fetch reviews
        $rStmt = $pdo->prepare("SELECT * FROM property_reviews WHERE property_id = :id ORDER BY created_at DESC");
        $rStmt->execute(['id' => $propertyId]);
        $reviews = $rStmt->fetchAll();

        // Decode JSON fields
        $prop['badges'] = json_decode($prop['badges'] ?? '[]', true);
        $prop['nearby_spots'] = json_decode($prop['nearby_spots'] ?? '[]', true);
        $prop['gallery'] = json_decode($prop['gallery'] ?? '[]', true);
        $prop['sections'] = json_decode($prop['sections'] ?? '[]', true);
        $prop['contract_info'] = json_decode($prop['contract_info'] ?? '{}', true);
        $prop['pricing'] = calculate_price($prop['base_price'], $isVerified);
        $prop['reviews'] = $reviews;

        json_response(['success' => true, 'property' => $prop]);
    }

    // List query with filters
    $sql = "SELECT * FROM properties WHERE status = 'Approved'";
    $params = [];

    if ($category !== 'all') {
        $sql .= " AND category = :category";
        $params['category'] = $category;
    }

    if ($location !== 'all' && !empty($location)) {
        $sql .= " AND location = :location";
        $params['location'] = $location;
    }

    if ($availability !== 'all' && !empty($availability)) {
        $sql .= " AND availability = :availability";
        $params['availability'] = $availability;
    }

    if ($maxPrice > 0 && $maxPrice < 99999) {
        $sql .= " AND base_price <= :max_price";
        $params['max_price'] = $maxPrice;
    }

    if (!empty($search)) {
        $sql .= " AND (title LIKE :s1 OR location LIKE :s2 OR distance LIKE :s3 OR badges LIKE :s4)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
        $params['s3'] = "%$search%";
        $params['s4'] = "%$search%";
    }

    $sql .= " ORDER BY property_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $results = [];
    foreach ($rows as $row) {
        $row['badges'] = json_decode($row['badges'] ?? '[]', true);
        $row['nearby_spots'] = json_decode($row['nearby_spots'] ?? '[]', true);
        $row['gallery'] = json_decode($row['gallery'] ?? '[]', true);
        $row['sections'] = json_decode($row['sections'] ?? '[]', true);
        $row['contract_info'] = json_decode($row['contract_info'] ?? '{}', true);
        $row['pricing'] = calculate_price($row['base_price'], $isVerified);
        $results[] = $row;
    }

    json_response([
        'success' => true,
        'count' => count($results),
        'is_student_verified' => $isVerified,
        'properties' => $results
    ]);

} catch (Exception $e) {
    error_log('API Listings error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Ralat pangkalan data semasa memuatkan senarai kediaman.'], 500);
}
