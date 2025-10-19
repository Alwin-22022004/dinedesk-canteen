<?php
// Auth check with cache prevention
require_once '../lib/auth_check.php';

$order_id = $_SESSION['last_order_id'] ?? null;
unset($_SESSION['last_order_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success |  Dinedesk</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="products.php" class="brand">🍽️  Dinedesk</a>
        <div class="nav-links">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</span>
            <a href="products.php">🏠 Menu</a>
            <a href="orders.php">📦 My Orders</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container success-container">
        <div class="success-icon">🎉</div>
        <h1>Order Placed Successfully!</h1>
        
        <?php if ($order_id): ?>
            <div class="order-id">
                Order ID: #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?>
            </div>
        <?php endif; ?>
        
        <p style="font-size: 1.1rem; color: #6c757d; margin: 2rem 0;">
            Your order has been received and is being prepared. 
            <br>Please collect your items from the canteen counter.
        </p>
        
        <div style="background: #fff3cd; padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border-left: 4px solid #ffc107;">
            <strong>Important:</strong> Please show this order ID at the counter for pickup.
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="orders.php" class="btn btn-primary" style="width: auto;">
                📦 View My Orders
            </a>
            <a href="products.php" class="btn btn-secondary" style="width: auto;">
                🏠 Back to Menu
            </a>
        </div>
    </div>
    
    <script>
        // Optional: Auto-redirect after 10 seconds
        // setTimeout(() => {
        //     window.location.href = 'orders.php';
        // }, 10000);
    </script>
</body>
</html>
