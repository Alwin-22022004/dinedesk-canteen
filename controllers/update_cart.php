<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

header('Content-Type: application/json');

$cart = new Cart();
$productModel = new Product();

$product_id = $_POST['product_id'] ?? null;
$action = $_POST['action'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

try {
    if ($action === 'remove') {
        $cart->remove($product_id);
        $message = 'Item removed from cart';
    } elseif ($action === 'update') {
        $cart->update($product_id, $quantity);
        $message = 'Cart updated';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
    
    // Calculate totals
    $cart_items = $cart->items();
    $cart_count = array_sum($cart_items);
    $subtotal = 0;
    $line_total = 0;
    
    foreach ($cart_items as $id => $qty) {
        $product = $productModel->find($id);
        if ($product) {
            $item_total = $product['price'] * $qty;
            $subtotal += $item_total;
            
            if ($id == $product_id) {
                $line_total = $item_total;
            }
        }
    }
    
    $tax = $subtotal * 0.05;
    $total = $subtotal + $tax;
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total,
        'product_id' => $product_id,
        'line_total' => $line_total
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating cart']);
}
?>
