<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: ../public/login.php");
    exit();
}

$cart = new Cart();
$productModel = new Product();
$orderModel = new Order();

$user_id = $_SESSION['user']['id'];
$cart_items_session = $cart->items();

// Check if cart is empty
if (empty($cart_items_session)) {
    $_SESSION['error'] = 'Your cart is empty';
    header("Location: ../public/cart.php");
    exit();
}

// Get payment method and notes
$payment_method = $_POST['payment_method'] ?? 'Cash';
$notes = $_POST['notes'] ?? '';

// Verify Razorpay payment if applicable
if ($payment_method === 'Razorpay') {
    require_once __DIR__ . '/../config/razorpay_config.php';
    
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    
    if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
        $_SESSION['error'] = 'Invalid payment details';
        header("Location: ../public/cart.php");
        exit();
    }
    
    // Verify signature
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);
    
    if ($generated_signature !== $razorpay_signature) {
        $_SESSION['error'] = 'Payment verification failed. Please try again.';
        header("Location: ../public/cart.php");
        exit();
    }
    
    // Payment verified successfully - Store as UPI
    $payment_method = 'UPI';
}

$cart_items = [];
$subtotal = 0;
$stock_errors = [];

// Prepare cart items with product details and validate stock
foreach ($cart_items_session as $pid => $qty) {
    $p = $productModel->find($pid);
    if ($p) {
        // Check if product is active
        if ($p['is_active'] == 0) {
            $stock_errors[] = $p['name'] . ' is currently unavailable';
            continue;
        }
        
        // Check stock availability
        if ($p['stock_quantity'] < $qty) {
            if ($p['stock_quantity'] == 0) {
                $stock_errors[] = $p['name'] . ' is out of stock';
            } else {
                $stock_errors[] = $p['name'] . ' - only ' . $p['stock_quantity'] . ' available (you ordered ' . $qty . ')';
            }
            continue;
        }
        
        $subtotal += $p['price'] * $qty;
        $cart_items[$pid] = [
            'qty' => $qty, 
            'price' => $p['price'],
            'name' => $p['name']
        ];
    }
}

// If there are stock errors, redirect back to cart
if (!empty($stock_errors)) {
    $_SESSION['error'] = 'Cannot place order: ' . implode(', ', $stock_errors);
    header("Location: ../public/cart.php");
    exit();
}

// Calculate total with tax
$tax = $subtotal * 0.05;
$total = $subtotal + $tax;

try {
    // Create order
    $order_id = $orderModel->create($user_id, $cart_items, $total, $payment_method, $notes);
    
    // Clear cart
    $cart->clear();
    
    // Store order ID in session for success page
    $_SESSION['last_order_id'] = $order_id;
    
    header("Location: ../public/order_success.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to place order. Please try again.';
    header("Location: ../public/cart.php");
    exit();
}
?>
