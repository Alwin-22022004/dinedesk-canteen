<?php
/**
 * Razorpay Order Creation
 * Creates a Razorpay order before payment
 */

require_once __DIR__ . '/../config/razorpay_config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$amount = intval($input['amount'] ?? 0); // Amount in paise
$notes = $input['notes'] ?? '';

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit();
}

try {
    // Prepare order data
    $orderData = [
        'amount' => $amount, // Amount in paise
        'currency' => RAZORPAY_CURRENCY,
        'receipt' => 'order_' . time() . '_' . $_SESSION['user']['id'],
        'notes' => [
            'user_id' => $_SESSION['user']['id'],
            'user_name' => $_SESSION['user']['name'],
            'special_instructions' => $notes
        ]
    ];
    
    // Create Razorpay order using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $order = json_decode($response, true);
        
        echo json_encode([
            'success' => true,
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency']
        ]);
    } else {
        $error = json_decode($response, true);
        echo json_encode([
            'success' => false,
            'message' => $error['error']['description'] ?? 'Failed to create order'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
