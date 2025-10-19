<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

header('Content-Type: application/json');

$cart = new Cart();
$productModel = new Product();

$id = $_POST['product_id'] ?? $_POST['id'] ?? null;
$quantity = intval($_POST['quantity'] ?? 1);

if ($id) {
    $product = $productModel->find($id);
    if ($product) {
        $cart->add($id, $quantity);
        $cart_items = $cart->items();
        $cart_count = array_sum($cart_items);
        
        echo json_encode([
            "success" => true,
            "message" => "Added to cart successfully!",
            "cart_count" => $cart_count
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Product not found"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid product ID"
    ]);
}
?>
