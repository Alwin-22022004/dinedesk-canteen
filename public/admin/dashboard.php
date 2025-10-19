<?php
// Auth check with cache prevention
require_once '../../lib/auth_check.php';

// Check if user is admin
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../products.php");
    exit();
}

require_once '../../models/Order.php';
require_once '../../models/Product.php';

$orderModel = new Order();
$productModel = new Product();

// Get filter and search
$status_filter = $_GET['status'] ?? null;
$search_query = $_GET['search'] ?? '';
$orders = $orderModel->getAllOrders($status_filter);

// Filter by search if provided
if (!empty($search_query)) {
    $orders = array_filter($orders, function($order) use ($search_query) {
        $name_match = stripos($order['user_name'], $search_query) !== false;
        $email_match = stripos($order['user_email'] ?? '', $search_query) !== false;
        $order_id_match = stripos('#' . $order['id'], $search_query) !== false;
        return $name_match || $email_match || $order_id_match;
    });
}

// Calculate statistics
$all_orders = $orderModel->getAllOrders();
$total_revenue = 0;
$status_counts = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Preparing' => 0,
    'Ready' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

foreach ($all_orders as $order) {
    if ($order['status'] !== 'Cancelled') {
        $total_revenue += $order['total_amount'];
    }
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
    <title>Admin Dashboard | Dinedesk</title>
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
            padding: 1.8rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            border-left: 4px solid #667eea;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.revenue {
            border-left-color: #10b981;
        }
        
        .stat-card.orders {
            border-left-color: #3b82f6;
        }
        
        .stat-card.active {
            border-left-color: #f59e0b;
        }
        
        .stat-card.completed {
            border-left-color: #8b5cf6;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0.3rem 0;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .search-filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .search-input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
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
        
        .admin-order-card {
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
        
        .admin-order-card:hover {
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
            margin-bottom: 0.5rem;
        }
        
        .payment-method-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .payment-method-badge.cash {
            background: #fef3c7;
            color: #92400e;
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
        <a href="dashboard.php" class="brand">🍽️ Dinedesk - Admin</a>
        <div class="nav-links">
            <span>Welcome, Admin <?= htmlspecialchars($_SESSION['user']['name']) ?>!</span>
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="products.php">🍔 Products</a>
            <a href="reports.php">📈 Reports</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="full-container">
        <h1 style="margin-bottom: 2rem; color: #1f2937;">Admin Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₹<?= number_format($total_revenue, 2) ?></div>
            </div>
            
            <div class="stat-card orders">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $total_orders ?></div>
            </div>
            
            <div class="stat-card active">
                <div class="stat-icon">🔄</div>
                <div class="stat-label">Active Orders</div>
                <div class="stat-value"><?= $active_orders ?></div>
            </div>
            
            <div class="stat-card completed">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= $status_counts['Completed'] ?></div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <form method="GET" action="dashboard.php">
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="🔍 Search by order #, customer name, or email..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter ?? '') ?>">
            </form>
            
            <!-- Status Filter Tabs -->
            <?php $search_param = !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>
            <div class="filter-tabs">
                <a href="dashboard.php?<?= ltrim($search_param, '&') ?>" class="filter-tab <?= !$status_filter ? 'active' : '' ?>">
                    All (<?= $total_orders ?>)
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
        </div>

        <!-- Orders List -->
        <?php if (!empty($search_query)): ?>
            <p style="margin-bottom: 1rem; color: #6b7280;">
                Showing results for "<strong><?= htmlspecialchars($search_query) ?></strong>" 
                (<?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found)
            </p>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No orders found</h3>
                <p><?= !empty($search_query) ? 'No orders match your search' : 'Orders will appear here when customers place them' ?></p>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): 
                    $order_details = $orderModel->getOrderDetails($order['id']);
                    $order_items = $orderModel->getOrderItems($order['id']);
                    $status_class = 'status-' . strtolower($order['status']);
                    $payment_class = strtolower($order['payment_method']) === 'cash' ? 'cash' : 'upi';
                ?>
                    <div class="admin-order-card">
                        <!-- Header -->
                        <div class="order-card-header">
                            <div>
                                <span class="order-id-badge">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                <div class="order-time">
                                    📅 <?= date('M d, h:i A', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            <span class="order-status-badge <?= $status_class ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                        
                        <!-- Customer Info -->
                        <div class="customer-info">
                            <strong>👤 Customer:</strong> <?= htmlspecialchars($order_details['user_name']) ?><br>
                            <strong>📧 Email:</strong> <?= htmlspecialchars($order_details['user_email']) ?><br>
                            <strong>💳 Payment:</strong> 
                            <span class="payment-method-badge <?= $payment_class ?>">
                                <?= htmlspecialchars($order['payment_method']) ?>
                            </span>
                        </div>
                        
                        <!-- Items -->
                        <div class="items-compact">
                            <div class="items-compact-header">Order Items</div>
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 60 seconds to show new orders
        setInterval(() => {
            location.reload();
        }, 60000);
        
        // Search on input change (debounced)
        let searchTimeout;
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }
    </script>
</body>
</html>
