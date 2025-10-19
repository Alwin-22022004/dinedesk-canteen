<?php
// Auth check
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

require_once '../models/Order.php';
require_once '../models/Product.php';

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
$top_products = array_slice($product_sales, 0, 10, true);

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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dinedesk Sales Report - <?= date('Y-m-d') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: white;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 4px solid #667eea;
            padding-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            font-size: 42px;
            margin-bottom: 10px;
        }
        .header h2 {
            color: #1f2937;
            font-size: 28px;
            margin: 15px 0;
        }
        .header p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .metric-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .metric-card.success {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }
        .metric-card.info {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        }
        .metric-card.warning {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        .metric-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin: 10px 0;
        }
        .metric-subtitle {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
        }
        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        .section h2 {
            color: #1f2937;
            font-size: 24px;
            border-bottom: 3px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        table tr:nth-child(even) {
            background: #f9fafb;
        }
        table tr:hover {
            background: #f3f4f6;
        }
        .payment-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        .payment-item {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        .payment-item h3 {
            margin: 0;
            font-size: 16px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .payment-item p {
            margin: 15px 0 10px 0;
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
        }
        .payment-item small {
            color: #999;
            font-size: 13px;
        }
        .rank-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: bold;
            font-size: 14px;
            margin-right: 10px;
        }
        .footer {
            margin-top: 60px;
            padding-top: 30px;
            border-top: 3px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .footer strong {
            color: #1f2937;
            font-size: 14px;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
            z-index: 1000;
        }
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 14px;
        }
        @media print {
            .print-button {
                display: none !important;
            }
            .container {
                padding: 20px;
            }
            .section {
                page-break-inside: avoid;
            }
        }
        @page {
            size: A4;
            margin: 20mm;
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Save as PDF</button>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🍽️ Dinedesk</h1>
            <h2>Sales Report & Analytics</h2>
            <p><strong>Generated on:</strong> <?= date('F d, Y - h:i A') ?></p>
            <p><strong>Report Period:</strong> All Time</p>
        </div>

        <!-- Key Metrics -->
        <div class="metrics">
            <div class="metric-card success">
                <div class="metric-label">💰 Total Revenue</div>
                <div class="metric-value">₹<?= number_format($total_revenue, 2) ?></div>
                <div class="metric-subtitle">From completed orders</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-label">📦 Total Orders</div>
                <div class="metric-value"><?= $total_orders ?></div>
                <div class="metric-subtitle"><?= $completed_orders ?> completed, <?= $cancelled_orders ?> cancelled</div>
            </div>
            
            <div class="metric-card info">
                <div class="metric-label">📊 Avg Order Value</div>
                <div class="metric-value">₹<?= number_format($avg_order_value, 2) ?></div>
                <div class="metric-subtitle">Per completed order</div>
            </div>
            
            <div class="metric-card warning">
                <div class="metric-label">🛍️ Items Sold</div>
                <div class="metric-value"><?= $total_items_sold ?></div>
                <div class="metric-subtitle">Total items sold</div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="section">
            <h2>🏆 Top 10 Selling Products</h2>
            <?php if (empty($top_products)): ?>
                <div class="no-data">No sales data available</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product Name</th>
                            <th>Quantity Sold</th>
                            <th>Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($top_products as $product_name => $data): 
                        ?>
                            <tr>
                                <td><span class="rank-badge"><?= $rank++ ?></span></td>
                                <td><strong><?= htmlspecialchars($product_name) ?></strong></td>
                                <td><?= $data['quantity'] ?> items</td>
                                <td><strong>₹<?= number_format($data['revenue'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="section">
            <h2>💳 Payment Method Breakdown</h2>
            <?php if ($total_revenue > 0): ?>
                <div class="payment-summary">
                    <div class="payment-item">
                        <h3>💵 Cash Payments</h3>
                        <p>₹<?= number_format($payment_breakdown['Cash'], 2) ?></p>
                        <small><?= number_format(($payment_breakdown['Cash'] / $total_revenue) * 100, 1) ?>% of total revenue</small>
                    </div>
                    <div class="payment-item">
                        <h3>💳 UPI/Online Payments</h3>
                        <p>₹<?= number_format($payment_breakdown['UPI'], 2) ?></p>
                        <small><?= number_format(($payment_breakdown['UPI'] / $total_revenue) * 100, 1) ?>% of total revenue</small>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">No payment data available</div>
            <?php endif; ?>
        </div>

        <!-- Daily Sales Trend -->
        <div class="section">
            <h2>📊 Daily Sales Trend (Last 7 Days)</h2>
            <?php if (empty($recent_days)): ?>
                <div class="no-data">No sales data available</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number of Orders</th>
                            <th>Revenue</th>
                            <th>Avg Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_days as $date => $data): ?>
                            <tr>
                                <td><strong><?= date('l, F d, Y', strtotime($date)) ?></strong></td>
                                <td><?= $data['orders'] ?> orders</td>
                                <td><strong>₹<?= number_format($data['revenue'], 2) ?></strong></td>
                                <td>₹<?= number_format($data['revenue'] / $data['orders'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Dinedesk - Canteen Management System</strong></p>
            <p>This report is confidential and intended for internal use only.</p>
            <p>Generated by: Admin (<?= htmlspecialchars($_SESSION['user']['name']) ?>) | Date: <?= date('F d, Y h:i A') ?></p>
        </div>
    </div>

    <script>
        // Auto-print dialog hint
        console.log('💡 Tip: Click the "Save as PDF" button to download this report as PDF');
    </script>
</body>
</html>
