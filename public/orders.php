<?php
// Auth check with cache prevention
require_once '../lib/auth_check.php';

require_once '../models/Order.php';

$orderModel = new Order();
$orders = $orderModel->getUserOrders($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders |  Dinedesk</title>
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
            <a href="cart.php">🛒 Cart</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="full-container">
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>No orders yet</h3>
                <p>Start ordering delicious food from our menu!</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 1rem; width: auto;">
                    Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): 
                    $order_items = $orderModel->getOrderItems($order['id']);
                    $status_class = 'status-' . strtolower($order['status']);
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id-badge">
                                    Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                                </span>
                                <p style="color: #6c757d; margin-top: 0.5rem; font-size: 0.9rem;">
                                    <?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                            
                            <span class="order-status <?= $status_class ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                        
                        <div class="order-items-list">
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <span>
                                        <?= htmlspecialchars($item['product_name']) ?> 
                                        <small style="color: #6c757d;">× <?= $item['quantity'] ?></small>
                                    </span>
                                    <span>₹<?= number_format($item['subtotal'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Payment Method:</span>
                                <strong><?= htmlspecialchars($order['payment_method']) ?></strong>
                            </div>
                            
                            <?php if ($order['notes']): ?>
                                <div style="margin: 0.5rem 0; padding: 0.75rem; background: #f8f9fa; border-radius: 8px;">
                                    <small style="color: #6c757d;">
                                        <strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-total">
                            Total: ₹<?= number_format($order['total_amount'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
