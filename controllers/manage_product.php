<?php
require_once __DIR__ . '/../models/Product.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header("Location: ../public/login.php");
    exit();
}

$productModel = new Product();
$action = $_POST['action'] ?? '';

// Function to handle image upload
function handleImageUpload($currentImage = '') {
    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentImage; // No new image uploaded, keep current
    }
    
    $file = $_FILES['product_image'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    // Validate file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Image size must be less than 2MB');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and WEBP allowed');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_', true) . '.' . $extension;
    $uploadDir = __DIR__ . '/../public/assets/images/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded image');
    }
    
    // Delete old image if exists and different from new one
    if (!empty($currentImage) && file_exists($uploadDir . $currentImage)) {
        @unlink($uploadDir . $currentImage);
    }
    
    return $filename;
}

try {
    if ($action === 'add') {
        // Add new product
        $name = trim($_POST['name'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        
        if (empty($name) || $category_id <= 0 || $price <= 0) {
            $_SESSION['error'] = 'Please fill all required fields';
            header("Location: ../public/admin/products.php");
            exit();
        }
        
        // Handle image upload
        $image = handleImageUpload();
        
        $result = $productModel->create($name, $category_id, $description, $price, $stock_quantity, $image);
        
        if ($result) {
            $_SESSION['success'] = 'Product added successfully!';
        } else {
            $_SESSION['error'] = 'Failed to add product';
        }
        header("Location: ../public/admin/products.php");
        exit();
        
    } elseif ($action === 'edit') {
        // Update existing product
        $product_id = intval($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        
        if ($product_id <= 0 || empty($name) || $category_id <= 0 || $price <= 0) {
            $_SESSION['error'] = 'Invalid product data';
            header("Location: ../public/admin/products.php");
            exit();
        }
        
        // Handle image upload (keeps current image if no new one uploaded)
        $image = handleImageUpload($current_image);
        
        $result = $productModel->update($product_id, $name, $category_id, $description, $price, $stock_quantity, $image);
        
        if ($result) {
            $_SESSION['success'] = 'Product updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update product';
        }
        header("Location: ../public/admin/products.php");
        exit();
        
    } elseif ($action === 'toggle_status') {
        // Toggle product active status (AJAX)
        header('Content-Type: application/json');
        
        $product_id = intval($_POST['product_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        $result = $productModel->toggleStatus($product_id, $is_active);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Status updated' : 'Failed to update status'
        ]);
        exit();
        
    } else {
        $_SESSION['error'] = 'Invalid action';
        header("Location: ../public/admin/products.php");
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    header("Location: ../public/admin/products.php");
    exit();
}
?>
