<?php
// API: Student Wishlist Toggle & Management

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$user = currentUser();
if (!$user) {
    json_response(['success' => false, 'login_required' => true, 'message' => 'Sila log masuk sebagai pelajar untuk menyimpan kediaman ke Wishlist.'], 401);
}

if ($user['role'] !== 'student') {
    json_response(['success' => false, 'message' => 'Ciri Wishlist ini khusus untuk akaun pelajar sahaja.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Kaedah permintaan tidak sah.'], 405);
}

validate_csrf_or_die();

$propertyId = (int)($_POST['property_id'] ?? 0);
if ($propertyId <= 0) {
    json_response(['success' => false, 'message' => 'ID kediaman tidak sah.'], 400);
}

try {
    $pdo = getDbConnection();

    // Verify property exists
    $checkProp = $pdo->prepare("SELECT property_id, title FROM properties WHERE property_id = ?");
    $checkProp->execute([$propertyId]);
    $prop = $checkProp->fetch();
    if (!$prop) {
        json_response(['success' => false, 'message' => 'Kediaman tidak ditemui.'], 404);
    }

    // Check if already in wishlist
    $wStmt = $pdo->prepare("SELECT wishlist_id FROM wishlists WHERE student_id = ? AND property_id = ?");
    $wStmt->execute([$user['user_id'], $propertyId]);
    $existing = $wStmt->fetch();

    $action = '';
    if ($existing) {
        // Remove from wishlist
        $delStmt = $pdo->prepare("DELETE FROM wishlists WHERE wishlist_id = ?");
        $delStmt->execute([$existing['wishlist_id']]);
        $action = 'removed';
        $msg = 'Kediaman telah dikeluarkan daripada senarai simpanan anda.';
    } else {
        // Add to wishlist
        $insStmt = $pdo->prepare("INSERT INTO wishlists (student_id, property_id) VALUES (?, ?)");
        $insStmt->execute([$user['user_id'], $propertyId]);
        $action = 'added';
        $msg = 'Kediaman berjaya disimpan ke senarai Wishlist anda!';
    }

    // Count total wishlists
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE student_id = ?");
    $cStmt->execute([$user['user_id']]);
    $total = (int)$cStmt->fetchColumn();

    json_response([
        'success' => true,
        'action' => $action,
        'message' => $msg,
        'total_wishlists' => $total,
        'property_id' => $propertyId
    ]);

} catch (Exception $e) {
    error_log('Wishlist API error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Ralat pangkalan data semasa mengemaskini Wishlist.'], 500);
}
