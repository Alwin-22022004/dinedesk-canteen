<?php
class Cart {
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    }

    public function add($id, $qty = 1) {
        if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] += $qty;
        else $_SESSION['cart'][$id] = $qty;
    }

    public function remove($id) {
        unset($_SESSION['cart'][$id]);
    }
    
    public function update($id, $qty) {
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            $this->remove($id);
        }
    }

    public function items() {
        return $_SESSION['cart'];
    }

    public function clear() {
        $_SESSION['cart'] = [];
    }
}
?>
