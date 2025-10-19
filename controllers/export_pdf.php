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

// Generate PDF content as HTML
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
            font-size: 32px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        .metrics {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .metric-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
            width: 23%;
            min-width: 200px;
        }
        .metric-card.success {
            border-left-color: #10b981;
        }
        .metric-card.info {
            border-left-color: #3b82f6;
        }
        .metric-card.warning {
            border-left-color: #f59e0b;
        }
        .metric-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .metric-subtitle {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section h2 {
            color: #1f2937;
            font-size: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        table tr:nth-child(even) {
            background: #f9fafb;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        .payment-summary {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        .payment-item {
            text-align: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            flex: 1;
            margin: 0 10px;
        }
        .payment-item h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .payment-item p {
            margin: 10px 0 0 0;
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }
        .rank-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
            font-size: 12px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🍽️ Dinedesk</h1>
        <h2 style="margin: 10px 0; color: #1f2937;">Sales Report & Analytics</h2>
        <p>Generated on: <?= date('F d, Y - h:i A') ?></p>
        <p>Report Period: All Time</p>
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
            <p style="color: #999; text-align: center; padding: 20px;">No sales data available</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product Name</th>
                        <th>Quantity Sold</th>
                        <th>Revenue</th>
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
                            <td>₹<?= number_format($data['revenue'], 2) ?></td>
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
                    <h3>Cash Payments</h3>
                    <p>₹<?= number_format($payment_breakdown['Cash'], 2) ?></p>
                    <small style="color: #666;"><?= number_format(($payment_breakdown['Cash'] / $total_revenue) * 100, 1) ?>% of total</small>
                </div>
                <div class="payment-item">
                    <h3>UPI/Online Payments</h3>
                    <p>₹<?= number_format($payment_breakdown['UPI'], 2) ?></p>
                    <small style="color: #666;"><?= number_format(($payment_breakdown['UPI'] / $total_revenue) * 100, 1) ?>% of total</small>
                </div>
            </div>
        <?php else: ?>
            <p style="color: #999; text-align: center; padding: 20px;">No payment data available</p>
        <?php endif; ?>
    </div>

    <!-- Daily Sales Trend -->
    <div class="section">
        <h2>📊 Daily Sales Trend (Last 7 Days)</h2>
        <?php if (empty($recent_days)): ?>
            <p style="color: #999; text-align: center; padding: 20px;">No sales data available</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_days as $date => $data): ?>
                        <tr>
                            <td><strong><?= date('M d, Y', strtotime($date)) ?></strong></td>
                            <td><?= $data['orders'] ?> orders</td>
                            <td>₹<?= number_format($data['revenue'], 2) ?></td>
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
        <p>Generated by: Admin | Date: <?= date('F d, Y') ?></p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Dinedesk_Sales_Report_' . date('Y-m-d') . '.pdf"');

// Use DomPDF library (lightweight, no installation needed)
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configure DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('Dinedesk_Sales_Report_' . date('Y-m-d') . '.pdf', array('Attachment' => 1));
?>
