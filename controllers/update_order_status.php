<?php
require_once __DIR__ . '/../models/Order.php';
session_start();

// Check if user is admin or staff
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'staff'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$order_id = $_POST['order_id'] ?? null;
$status = $_POST['status'] ?? null;

$valid_statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Completed', 'Cancelled'];

if (!$order_id || !$status || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$orderModel = new Order();

try {
    $result = $orderModel->updateStatus($order_id, $status);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>