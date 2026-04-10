<?php
session_start();
header('Content-Type: application/json');
require_once "../config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$tier_id = isset($data['tier_id']) ? intval($data['tier_id']) : 0;
$user_id = isset($data['user_id']) ? intval($data['user_id']) : $_SESSION['user_id'];

if ($tier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tier selection']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get tier information
    $tier_query = "SELECT * FROM membership_tiers WHERE id = ? AND is_active = 1";
    $stmt = $conn->prepare($tier_query);
    $stmt->bind_param("i", $tier_id);
    $stmt->execute();
    $tier_result = $stmt->get_result();
    
    if ($tier_result->num_rows === 0) {
        throw new Exception('Tier not found or inactive');
    }
    
    $tier = $tier_result->fetch_assoc();
    
    // Deactivate current membership if exists
    $deactivate_query = "UPDATE user_memberships SET status = 'inactive' WHERE user_id = ? AND status = 'active'";
    $stmt = $conn->prepare($deactivate_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Calculate renewal date (30 days from now)
    $renewal_date = date('Y-m-d', strtotime('+30 days'));
    
    // Insert new membership
    $insert_query = "INSERT INTO user_memberships (user_id, tier_id, start_date, renewal_date, status) 
                    VALUES (?, ?, NOW(), ?, 'active')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iis", $user_id, $tier_id, $renewal_date);
    $stmt->execute();
    
    // Update user's role if needed
    if ($tier['name'] === 'Partner' || $tier['name'] === 'Premium') {
        $role_update = "UPDATE users SET user_role = 'partner' WHERE id = ?";
        $stmt = $conn->prepare($role_update);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['user_role'] = 'partner';
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Membership upgraded successfully!',
        'tier_name' => $tier['name']
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>