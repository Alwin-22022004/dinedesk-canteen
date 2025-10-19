<?php
require_once __DIR__ . '/../lib/Database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->pdo();
    }

    public function all($category_id = null, $search = null) {
        $query = "SELECT p.*, c.name as category_name, c.icon as category_icon 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.is_active = 1";
        
        $params = [];
        
        if ($category_id) {
            $query .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        if ($search) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $query .= " ORDER BY p.category_id, p.name";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function allForAdmin() {
        // Get all products including inactive ones for admin
        $query = "SELECT p.*, c.name as category_name, c.icon as category_icon 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.is_active DESC, p.category_id, p.name";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name 
                                     FROM products p 
                                     LEFT JOIN categories c ON p.category_id = c.id 
                                     WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getCategories() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }
    
    public function updateStock($id, $quantity) {
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        return $stmt->execute([$quantity, $id]);
    }
    
    public function create($name, $category_id, $description, $price, $stock_quantity, $image = '') {
        $stmt = $this->db->prepare("INSERT INTO products (name, category_id, description, price, stock_quantity, image, is_active) 
                                     VALUES (?, ?, ?, ?, ?, ?, 1)");
        return $stmt->execute([$name, $category_id, $description, $price, $stock_quantity, $image]);
    }
    
    public function update($id, $name, $category_id, $description, $price, $stock_quantity, $image = null) {
        // If image is provided, update it; otherwise, keep existing
        if ($image !== null) {
            $stmt = $this->db->prepare("UPDATE products 
                                         SET name = ?, category_id = ?, description = ?, price = ?, stock_quantity = ?, image = ? 
                                         WHERE id = ?");
            return $stmt->execute([$name, $category_id, $description, $price, $stock_quantity, $image, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE products 
                                         SET name = ?, category_id = ?, description = ?, price = ?, stock_quantity = ? 
                                         WHERE id = ?");
            return $stmt->execute([$name, $category_id, $description, $price, $stock_quantity, $id]);
        }
    }
    
    public function toggleStatus($id, $is_active) {
        $stmt = $this->db->prepare("UPDATE products SET is_active = ? WHERE id = ?");
        return $stmt->execute([$is_active, $id]);
    }
    
    public function restoreStock($id, $quantity) {
        // Restore stock (used when order is cancelled)
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        return $stmt->execute([$quantity, $id]);
    }
    
    public function checkAndReactivate($id) {
        // Reactivate product if stock is available
        $stmt = $this->db->prepare("UPDATE products SET is_active = 1 WHERE id = ? AND stock_quantity > 0");
        return $stmt->execute([$id]);
    }
}
?>
