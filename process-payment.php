<?php
session_start();
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login to donate']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_donation') {
    $user_id = $_SESSION['user_id'];
    $amount = floatval($_POST['amount']);
    $campaign_id = intval($_POST['campaign_id']);
    $payment_method = $_POST['payment_method'];
    $phone = $_POST['phone'] ?? '';
    
    // Validate amount
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit();
    }
    
    // Generate transaction ID
    $transaction_id = 'TRX' . strtoupper(uniqid());
    
    // For demo, mark all as completed
    $payment_status = 'completed';
    
    try {
        // For general fund donations (campaign_id = 0), set campaign_id to NULL
        // Or find/create a general fund campaign
        if ($campaign_id === 0) {
            // Option 1: Set campaign_id to NULL (if foreign key allows NULL)
            // Option 2: Find a "General Fund" campaign
            
            // Let's find or create a general fund campaign
            $general_campaign_query = "SELECT id FROM donation_campaigns WHERE title = 'General Fund' LIMIT 1";
            $result = $conn->query($general_campaign_query);
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $campaign_id = $row['id'];
            } else {
                // Create a general fund campaign
                $create_query = "INSERT INTO donation_campaigns (title, description, target_amount, current_amount, donors_count, status, created_at) 
                                VALUES ('General Fund', 'General donation fund for various causes', 100000, 0, 0, 'active', NOW())";
                if ($conn->query($create_query)) {
                    $campaign_id = $conn->insert_id;
                } else {
                    // If can't create, set to NULL (need to modify foreign key)
                    $campaign_id = NULL;
                }
            }
        }
        
        // Prepare the insert query based on whether campaign_id is NULL
        if ($campaign_id === NULL) {
            // Insert with NULL campaign_id
            $query = "INSERT INTO donations (user_id, campaign_id, amount, payment_method, phone_number, transaction_id, status, created_at) 
                      VALUES (?, NULL, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("idssss", $user_id, $amount, $payment_method, $phone, $transaction_id, $payment_status);
        } else {
            // Insert with valid campaign_id
            $query = "INSERT INTO donations (user_id, campaign_id, amount, payment_method, phone_number, transaction_id, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iidssss", $user_id, $campaign_id, $amount, $payment_method, $phone, $transaction_id, $payment_status);
        }
        
        if ($stmt->execute()) {
            // Update campaign if applicable (not NULL and not 0)
            if ($campaign_id !== NULL && $campaign_id > 0) {
                $update_query = "UPDATE donation_campaigns 
                                SET current_amount = current_amount + ?, 
                                    donors_count = donors_count + 1 
                                WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("di", $amount, $campaign_id);
                $update_stmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'status' => $payment_status,
                'message' => 'Donation recorded successfully',
                'campaign_id' => $campaign_id
            ]);
        } else {
            throw new Exception('Failed to process donation: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>