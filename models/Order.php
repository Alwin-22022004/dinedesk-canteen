<?php
require_once __DIR__ . '/../lib/Database.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->pdo();
    }

    public function create($user_id, $cart_items, $total, $payment_method = 'Cash', $notes = '') {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, notes) 
                                        VALUES (?, ?, 'Pending', ?, ?)");
            $stmt->execute([$user_id, $total, $payment_method, $notes]);
            $order_id = $this->db->lastInsertId();

            $itemStmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) 
                                            VALUES (?, ?, ?, ?, ?, ?)");
            
            $updateStockStmt = $this->db->prepare("UPDATE products 
                                                    SET stock_quantity = stock_quantity - ? 
                                                    WHERE id = ?");
            
            $checkStockStmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            
            $disableProductStmt = $this->db->prepare("UPDATE products 
                                                       SET is_active = 0 
                                                       WHERE id = ? AND stock_quantity <= 0");
            
            foreach ($cart_items as $pid => $data) {
                $subtotal = $data['qty'] * $data['price'];
                
                // Insert order item
                $itemStmt->execute([
                    $order_id, 
                    $pid, 
                    $data['name'], 
                    $data['qty'], 
                    $data['price'], 
                    $subtotal
                ]);
                
                // Reduce stock quantity
                $updateStockStmt->execute([$data['qty'], $pid]);
                
                // Check if stock is now zero or negative
                $checkStockStmt->execute([$pid]);
                $product = $checkStockStmt->fetch();
                
                // Auto-disable product if stock is 0 or less
                if ($product && $product['stock_quantity'] <= 0) {
                    $disableProductStmt->execute([$pid]);
                }
            }
            
            $this->db->commit();
            return $order_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getUserOrders($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    public function getOrderDetails($order_id) {
        $stmt = $this->db->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                     FROM orders o 
                                     JOIN users u ON o.user_id = u.id 
                                     WHERE o.id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetch();
    }
    
    public function getOrderItems($order_id) {
        $stmt = $this->db->prepare("SELECT oi.*, p.image 
                                     FROM order_items oi 
                                     LEFT JOIN products p ON oi.product_id = p.id 
                                     WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus($order_id, $status) {
        try {
            $this->db->beginTransaction();
            
            // Get current order status
            $currentStmt = $this->db->prepare("SELECT status FROM orders WHERE id = ?");
            $currentStmt->execute([$order_id]);
            $currentOrder = $currentStmt->fetch();
            $oldStatus = $currentOrder['status'] ?? null;
            
            // Update order status
            $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            
            // If order is being cancelled, restore stock
            if ($status === 'Cancelled' && $oldStatus !== 'Cancelled') {
                $this->restoreStockForOrder($order_id);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function restoreStockForOrder($order_id) {
        // Get all items in the order
        $itemsStmt = $this->db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$order_id]);
        $items = $itemsStmt->fetchAll();
        
        // Restore stock for each item
        $restoreStmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $reactivateStmt = $this->db->prepare("UPDATE products SET is_active = 1 WHERE id = ? AND stock_quantity > 0 AND is_active = 0");
        
        foreach ($items as $item) {
            // Restore stock
            $restoreStmt->execute([$item['quantity'], $item['product_id']]);
            
            // Reactivate product if it was disabled due to stock
            $reactivateStmt->execute([$item['product_id']]);
        }
    }
    
    public function getAllOrders($status = null) {
        if ($status) {
            $stmt = $this->db->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.status = ? 
                                        ORDER BY o.created_at DESC");
            $stmt->execute([$status]);
        } else {
            $stmt = $this->db->query("SELECT o.*, u.name as user_name, u.email as user_email 
                                      FROM orders o 
                                      JOIN users u ON o.user_id = u.id 
                                      ORDER BY o.created_at DESC");
        }
        return $stmt->fetchAll();
    }
}
?>
