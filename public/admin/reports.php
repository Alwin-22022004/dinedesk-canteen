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

// Get all orders
$all_orders = $orderModel->getAllOrders();

// Calculate statistics
$total_revenue = 0;
$total_orders = count($all_orders);
$completed_orders = 0;
$cancelled_orders = 0;
$total_items_sold = 0;

// Category wise sales
$category_sales = [];
$product_sales = [];
$daily_sales = [];

foreach ($all_orders as $order) {
    if ($order['status'] === 'Completed') {
        $completed_orders++;
        $total_revenue += $order['total_amount'];
        
        // Get order items
        $items = $orderModel->getOrderItems($order['id']);
        foreach ($items as $item) {
            $total_items_sold += $item['quantity'];
            
            // Product sales
            $product_name = $item['product_name'];
            if (!isset($product_sales[$product_name])) {
                $product_sales[$product_name] = ['quantity' => 0, 'revenue' => 0];
            }
            $product_sales[$product_name]['quantity'] += $item['quantity'];
            $product_sales[$product_name]['revenue'] += $item['subtotal'];
        }
        
        // Daily sales
        $date = date('Y-m-d', strtotime($order['created_at']));
        if (!isset($daily_sales[$date])) {
            $daily_sales[$date] = ['orders' => 0, 'revenue' => 0];
        }
        $daily_sales[$date]['orders']++;
        $daily_sales[$date]['revenue'] += $order['total_amount'];
    } elseif ($order['status'] === 'Cancelled') {
        $cancelled_orders++;
    }
}

// Sort product sales by quantity
arsort($product_sales);
$top_products = array_slice($product_sales, 0, 5, true);

// Sort daily sales by date
krsort($daily_sales);
$recent_days = array_slice($daily_sales, 0, 7, true);

// Calculate average order value
$avg_order_value = $completed_orders > 0 ? $total_revenue / $completed_orders : 0;

// Calculate payment method breakdown
$payment_breakdown = ['Cash' => 0, 'UPI' => 0];
foreach ($all_orders as $order) {
    if ($order['status'] === 'Completed') {
        $method = $order['payment_method'];
        if (stripos($method, 'cash') !== false) {
            $payment_breakdown['Cash'] += $order['total_amount'];
        } else {
            $payment_breakdown['UPI'] += $order['total_amount'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .report-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary);
        }
        .report-card.success {
            border-left-color: var(--success);
        }
        .report-card.warning {
            border-left-color: var(--warning);
        }
        .report-card.info {
            border-left-color: var(--secondary);
        }
        .report-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 1rem 0;
        }
        .report-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .product-list {
            list-style: none;
            padding: 0;
        }
        .product-list li {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--light);
            align-items: center;
        }
        .product-list li:last-child {
            border-bottom: none;
        }
        .rank-badge {
            background: var(--primary);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="dashboard.php" class="brand">🍽️  Dinedesk - Admin</a>
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="products.php">🍔 Products</a>
            <a href="reports.php">📈 Reports</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="full-container">
        <h1>Sales Reports & Analytics</h1>
        
        <!-- Key Metrics -->
        <div class="report-grid">
            <div class="report-card success">
                <div class="report-label">💰 Total Revenue</div>
                <div class="report-value">₹<?= number_format($total_revenue, 2) ?></div>
                <small>From completed orders</small>
            </div>
            
            <div class="report-card">
                <div class="report-label">📦 Total Orders</div>
                <div class="report-value"><?= $total_orders ?></div>
                <small><?= $completed_orders ?> completed, <?= $cancelled_orders ?> cancelled</small>
            </div>
            
            <div class="report-card info">
                <div class="report-label">📊 Avg Order Value</div>
                <div class="report-value">₹<?= number_format($avg_order_value, 2) ?></div>
                <small>Per completed order</small>
            </div>
            
            <div class="report-card warning">
                <div class="report-label">🛍️ Items Sold</div>
                <div class="report-value"><?= $total_items_sold ?></div>
                <small>Total items in completed orders</small>
            </div>
        </div>

        <!-- Charts Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <!-- Top Selling Products Chart -->
            <div class="chart-container">
                <h2>🏆 Top 5 Selling Products</h2>
                <?php if (empty($top_products)): ?>
                    <p style="color: #6c757d; text-align: center; padding: 2rem;">No sales data available yet</p>
                <?php else: ?>
                    <canvas id="topProductsChart" style="max-height: 300px;"></canvas>
                <?php endif; ?>
            </div>

            <!-- Payment Method Breakdown Chart -->
            <div class="chart-container">
                <h2>💳 Payment Method Breakdown</h2>
                <?php if ($total_revenue > 0): ?>
                    <canvas id="paymentMethodChart" style="max-height: 300px;"></canvas>
                <?php else: ?>
                    <p style="color: #6c757d; text-align: center; padding: 2rem;">No payment data available yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Sales Trend Chart -->
        <div class="chart-container">
            <h2>📊 Daily Sales Trend (Last 7 Days)</h2>
            <?php if (empty($recent_days)): ?>
                <p style="color: #6c757d; text-align: center; padding: 2rem;">No sales data available yet</p>
            <?php else: ?>
                <canvas id="dailySalesChart" style="max-height: 350px;"></canvas>
            <?php endif; ?>
        </div>

        <!-- Export Options -->
        <div class="chart-container">
            <h2>📥 Export Reports</h2>
            <p style="color: #6c757d; margin-bottom: 1.5rem;">Download reports for further analysis</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn-success" onclick="window.print()">
                    🖨️ Print Report
                </button>
                <button class="btn-secondary" onclick="alert('CSV export feature coming soon!')">
                    📄 Export to CSV
                </button>
                <a href="../../controllers/export_pdf_simple.php" class="btn btn-primary" style="background: #e74c3c; color: white; text-decoration: none;" target="_blank">
                    📑 Export to PDF
                </a>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .navbar, button {
                display: none !important;
            }
            .full-container {
                max-width: 100%;
            }
        }
    </style>

    <script>
        // Top Products Bar Chart
        <?php if (!empty($top_products)): ?>
        const topProductsCtx = document.getElementById('topProductsChart');
        new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($top_products)) ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?= json_encode(array_column($top_products, 'revenue')) ?>,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(139, 92, 246, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₹' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Payment Method Pie Chart
        <?php if ($total_revenue > 0): ?>
        const paymentMethodCtx = document.getElementById('paymentMethodChart');
        new Chart(paymentMethodCtx, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'UPI'],
                datasets: [{
                    data: [<?= $payment_breakdown['Cash'] ?>, <?= $payment_breakdown['UPI'] ?>],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(59, 130, 246, 0.8)'
                    ],
                    borderColor: [
                        'rgba(245, 158, 11, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ₹' + context.parsed.toFixed(2) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Daily Sales Line Chart
        <?php if (!empty($recent_days)): ?>
        const dailySalesCtx = document.getElementById('dailySalesChart');
        const dailyDates = <?= json_encode(array_reverse(array_keys($recent_days))) ?>;
        const dailyRevenue = <?= json_encode(array_reverse(array_column($recent_days, 'revenue'))) ?>;
        const dailyOrders = <?= json_encode(array_reverse(array_column($recent_days, 'orders'))) ?>;

        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: dailyDates.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: dailyRevenue,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: dailyOrders,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.yAxisID === 'y') {
                                    label += '₹' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y + ' order(s)';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        },
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Orders'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
