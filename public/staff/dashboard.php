<?php
// Auth check with cache prevention
require_once '../../lib/auth_check.php';

// Check if user is staff or admin
if (!in_array($_SESSION['user']['role'], ['staff', 'admin'])) {
    header("Location: ../products.php");
    exit();
}

require_once '../../models/Order.php';

$orderModel = new Order();

// Get filter and search query
$status_filter = $_GET['status'] ?? null;
$search_query = $_GET['search'] ?? '';
$orders = $orderModel->getAllOrders($status_filter);

// Filter by search query if provided
if (!empty($search_query)) {
    $orders = array_filter($orders, function($order) use ($search_query) {
        $search_lower = strtolower($search_query);
        $name_match = stripos($order['user_name'], $search_query) !== false;
        $email_match = stripos($order['user_email'] ?? '', $search_query) !== false;
        $order_id_match = stripos('#' . $order['id'], $search_query) !== false;
        return $name_match || $email_match || $order_id_match;
    });
}

// Calculate statistics
$all_orders = $orderModel->getAllOrders();
$status_counts = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Preparing' => 0,
    'Ready' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

foreach ($all_orders as $order) {
    $status_counts[$order['status']]++;
}

$total_orders = count($all_orders);
$active_orders = $status_counts['Pending'] + $status_counts['Confirmed'] + $status_counts['Preparing'] + $status_counts['Ready'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard |  Dinedesk</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-card.pending {
            border-left-color: #f59e0b;
        }
        
        .stat-card.active {
            border-left-color: #3b82f6;
        }
        
        .stat-card.completed {
            border-left-color: #10b981;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0.3rem 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.6rem 1.2rem;
            background: #f3f4f6;
            border: 2px solid transparent;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            color: #4b5563;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background: #667eea;
            color: white;
        }
        
        .filter-tab:hover:not(.active) {
            background: #e5e7eb;
        }
        
        /* Grid Layout for Orders */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .staff-order-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 3px solid #667eea;
            transition: box-shadow 0.3s, transform 0.2s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .staff-order-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-id-badge {
            font-size: 1.1rem;
            font-weight: 700;
            color: #667eea;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .order-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }
        
        .order-status-badge {
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-preparing { background: #e0e7ff; color: #4338ca; }
        .status-ready { background: #a7f3d0; color: #065f46; }
        .status-completed { background: #d1fae5; color: #047857; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.3rem 0.7rem;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        
        .customer-info {
            background: #f9fafb;
            padding: 0.85rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        
        .customer-info strong {
            color: #374151;
            font-weight: 600;
        }
        
        .items-compact {
            margin-bottom: 1rem;
            flex-grow: 1;
        }
        
        .items-compact-header {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .item-row-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.9rem;
        }
        
        .item-row-compact:last-child {
            border-bottom: none;
        }
        
        .item-name-compact {
            font-weight: 500;
            color: #1f2937;
        }
        
        .item-qty-badge {
            background: #667eea;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .order-total-compact {
            font-size: 1.3rem;
            font-weight: 700;
            color: #10b981;
            text-align: right;
            padding: 0.75rem 0;
            border-top: 2px solid #e5e7eb;
            margin-bottom: 1rem;
        }
        
        .order-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .order-actions select {
            width: 100%;
            padding: 0.6rem;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .order-actions select:focus {
            border-color: #667eea;
        }
        
        .order-actions button {
            width: 100%;
            padding: 0.65rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .order-actions button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .special-note {
            background: #fef2f2;
            color: #991b1b;
            padding: 0.6rem;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            border-left: 3px solid #ef4444;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="dashboard.php" class="brand">🍽️  Dinedesk - Staff</a>
        <div class="nav-links">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="full-container">
        <h1>📋 Order Management</h1>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-label">⏳ Pending Orders</div>
                <div class="stat-value"><?= $status_counts['Pending'] ?></div>
            </div>
            
            <div class="stat-card active">
                <div class="stat-label">🔄 Active Orders</div>
                <div class="stat-value"><?= $active_orders ?></div>
            </div>
            
            <div class="stat-card completed">
                <div class="stat-label">✅ Completed Today</div>
                <div class="stat-value"><?= $status_counts['Completed'] ?></div>
            </div>
        </div>

        <h2>Orders</h2>
        
        <!-- Search Bar -->
        <div style="margin-bottom: 1.5rem;">
            <form method="GET" action="dashboard.php" style="display: flex; gap: 1rem; align-items: center;">
                <div style="flex: 1; position: relative;">
                    <input type="text" 
                           name="search" 
                           id="searchInput"
                           value="<?= htmlspecialchars($search_query) ?>"
                           placeholder="🔍 Search by customer name, email, or order #..." 
                           style="width: 100%; padding: 0.75rem 3rem 0.75rem 1rem; border: 2px solid #ddd; border-radius: 10px; font-size: 1rem;">
                    <?php if (!empty($search_query)): ?>
                        <a href="dashboard.php<?= $status_filter ? '?status=' . $status_filter : '' ?>" 
                           style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #999; text-decoration: none; font-size: 1.2rem;"
                           title="Clear search">✕</a>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter ?? '') ?>">
                <button type="submit" class="btn btn-primary" style="width: auto; white-space: nowrap;">
                    Search
                </button>
            </form>
            <?php if (!empty($search_query)): ?>
                <p style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">
                    Showing results for: <strong>"<?= htmlspecialchars($search_query) ?>"</strong>
                    (<?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found)
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Status Filter Tabs -->
        <?php 
        $search_param = !empty($search_query) ? '&search=' . urlencode($search_query) : '';
        ?>
        <div class="filter-tabs">
            <a href="dashboard.php?<?= ltrim($search_param, '&') ?>" class="filter-tab <?= !$status_filter ? 'active' : '' ?>">
                All Orders (<?= $total_orders ?>)
            </a>
            <a href="dashboard.php?status=Pending<?= $search_param ?>" class="filter-tab <?= $status_filter === 'Pending' ? 'active' : '' ?>">
                Pending (<?= $status_counts['Pending'] ?>)
            </a>
            <a href="dashboard.php?status=Confirmed<?= $search_param ?>" class="filter-tab <?= $status_filter === 'Confirmed' ? 'active' : '' ?>">
                Confirmed (<?= $status_counts['Confirmed'] ?>)
            </a>
            <a href="dashboard.php?status=Preparing<?= $search_param ?>" class="filter-tab <?= $status_filter === 'Preparing' ? 'active' : '' ?>">
                Preparing (<?= $status_counts['Preparing'] ?>)
            </a>
            <a href="dashboard.php?status=Ready<?= $search_param ?>" class="filter-tab <?= $status_filter === 'Ready' ? 'active' : '' ?>">
                Ready (<?= $status_counts['Ready'] ?>)
            </a>
            <a href="dashboard.php?status=Completed<?= $search_param ?>" class="filter-tab <?= $status_filter === 'Completed' ? 'active' : '' ?>">
                Completed (<?= $status_counts['Completed'] ?>)
            </a>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3>No orders found</h3>
                <?php if (!empty($search_query)): ?>
                    <p>No orders match your search "<?= htmlspecialchars($search_query) ?>"</p>
                    <a href="dashboard.php<?= $status_filter ? '?status=' . $status_filter : '' ?>" class="btn btn-primary" style="margin-top: 1rem; width: auto;">
                        Clear Search
                    </a>
                <?php else: ?>
                    <p>Orders will appear here when customers place them</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): 
                    $order_details = $orderModel->getOrderDetails($order['id']);
                    $order_items = $orderModel->getOrderItems($order['id']);
                    $status_class = 'status-' . strtolower($order['status']);
                    
                    // Calculate order age in minutes
                    $order_time = strtotime($order['created_at']);
                    $current_time = time();
                    $age_minutes = floor(($current_time - $order_time) / 60);
                    $is_urgent = ($age_minutes > 15 && $order['status'] !== 'Completed' && $order['status'] !== 'Cancelled');
                ?>
                    <div class="staff-order-card">
                        <!-- Header -->
                        <div class="order-card-header">
                            <div>
                                <span class="order-id-badge">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                <div class="order-time">
                                    📅 <?= date('M d, h:i A', strtotime($order['created_at'])) ?>
                                </div>
                                <?php if ($is_urgent): ?>
                                    <span class="priority-badge">⚠️ URGENT (<?= $age_minutes ?>m)</span>
                                <?php endif; ?>
                            </div>
                            <span class="order-status-badge <?= $status_class ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                        
                        <!-- Customer Info -->
                        <div class="customer-info">
                            <strong>👤 Customer:</strong> <?= htmlspecialchars($order_details['user_name']) ?><br>
                            <strong>💳 Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?>
                        </div>
                        
                        <!-- Items -->
                        <div class="items-compact">
                            <div class="items-compact-header">Items to Prepare</div>
                            <?php foreach ($order_items as $item): ?>
                                <div class="item-row-compact">
                                    <div>
                                        <span class="item-name-compact"><?= htmlspecialchars($item['product_name']) ?></span>
                                        <span class="item-qty-badge">×<?= $item['quantity'] ?></span>
                                    </div>
                                    <span>₹<?= number_format($item['subtotal'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                            <div class="special-note">
                                <strong>📝 Note:</strong> <?= htmlspecialchars($order['notes']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Total -->
                        <div class="order-total-compact">
                            Total: ₹<?= number_format($order['total_amount'], 2) ?>
                        </div>
                        
                        <!-- Order Actions -->
                        <?php if ($order['status'] !== 'Completed' && $order['status'] !== 'Cancelled'): ?>
                            <div class="order-actions">
                                <select class="status-select" data-order-id="<?= $order['id'] ?>">
                                    <option value="">Update Status...</option>
                                    <option value="Confirmed" <?= $order['status'] === 'Confirmed' ? 'disabled' : '' ?>>✓ Confirm Order</option>
                                    <option value="Preparing" <?= $order['status'] === 'Preparing' ? 'disabled' : '' ?>>👨‍🍳 Start Preparing</option>
                                    <option value="Ready" <?= $order['status'] === 'Ready' ? 'disabled' : '' ?>>✅ Mark as Ready</option>
                                    <option value="Completed" <?= $order['status'] === 'Completed' ? 'disabled' : '' ?>>🎉 Complete Order</option>
                                </select>
                                <button type="button" class="update-status-btn" data-order-id="<?= $order['id'] ?>">
                                    Update Status
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="display: none;"></div>

    <script>
        // Update order status
        document.querySelectorAll('.update-status-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const orderId = this.dataset.orderId;
                const select = document.querySelector(`.status-select[data-order-id="${orderId}"]`);
                const newStatus = select.value;
                
                if (!newStatus) {
                    showToast('Please select a status');
                    return;
                }
                
                if (!confirm(`Update order #${orderId} to ${newStatus}?`)) {
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('status', newStatus);
                    
                    const response = await fetch('../../controllers/update_order_status.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('Order status updated successfully!');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast('Error: ' + data.message);
                    }
                } catch (error) {
                    showToast('Error updating order status');
                    console.error(error);
                }
            });
        });

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        
        // Auto-refresh every 20 seconds for staff
        setInterval(() => {
            location.reload();
        }, 20000);
        
        // Play sound for urgent orders
        <?php if ($status_counts['Pending'] > 0): ?>
            console.log('⚠️ You have <?= $status_counts['Pending'] ?> pending order(s)');
        <?php endif; ?>
    </script>
</body>
</html>
