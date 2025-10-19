<?php
// Auth check with cache prevention
require_once '../../lib/auth_check.php';

// Check if user is admin
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../products.php");
    exit();
}

require_once '../../models/Product.php';

$productModel = new Product();
$all_products = $productModel->allForAdmin(); // Get all products including inactive
$categories = $productModel->getCategories();

// Separate active and inactive products
$active_products = array_filter($all_products, fn($p) => $p['is_active'] == 1);
$inactive_products = array_filter($all_products, fn($p) => $p['is_active'] == 0);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .products-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .products-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .products-table th {
            background: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
        }
        .products-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--light);
        }
        .products-table tr:hover {
            background: #f8f9fa;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Manage Products</h1>
            <button class="btn-primary" onclick="openAddModal()" style="width: auto;">
                + Add New Product
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Active Products -->
        <div class="products-table">
            <h3 style="padding: 1rem; background: #f8f9fa; margin: 0; border-radius: 15px 15px 0 0;">
                ✅ Active Products (<?= count($active_products) ?>)
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_products)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #6c757d;">
                                No active products found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($active_products as $product): ?>
                            <tr>
                                <td>#<?= $product['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                    <small style="color: #6c757d;"><?= htmlspecialchars($product['description']) ?></small>
                                </td>
                                <td><?= $product['category_icon'] ?> <?= htmlspecialchars($product['category_name']) ?></td>
                                <td><strong>₹<?= number_format($product['price'], 2) ?></strong></td>
                                <td><?= $product['stock_quantity'] ?></td>
                                <td>
                                    <span class="order-status status-completed">Active</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-secondary btn-sm" onclick='editProduct(<?= json_encode($product) ?>)'>
                                            Edit
                                        </button>
                                        <button class="btn-danger btn-sm" onclick="toggleStatus(<?= $product['id'] ?>, <?= $product['is_active'] ?>)">
                                            Disable
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Disabled Products -->
        <?php if (!empty($inactive_products)): ?>
            <div class="products-table" style="margin-top: 2rem; opacity: 0.8;">
                <h3 style="padding: 1rem; background: #fff3cd; margin: 0; border-radius: 15px 15px 0 0; color: #856404;">
                    ⚠️ Disabled Products (<?= count($inactive_products) ?>)
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactive_products as $product): ?>
                            <tr style="background: #f8f9fa;">
                                <td>#<?= $product['id'] ?></td>
                                <td>
                                    <strong style="color: #6c757d;"><?= htmlspecialchars($product['name']) ?></strong><br>
                                    <small style="color: #adb5bd;"><?= htmlspecialchars($product['description']) ?></small>
                                </td>
                                <td><?= $product['category_icon'] ?> <?= htmlspecialchars($product['category_name']) ?></td>
                                <td><strong>₹<?= number_format($product['price'], 2) ?></strong></td>
                                <td><?= $product['stock_quantity'] ?></td>
                                <td>
                                    <span class="order-status status-cancelled">Disabled</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-secondary btn-sm" onclick='editProduct(<?= json_encode($product) ?>)'>
                                            Edit
                                        </button>
                                        <button class="btn-success btn-sm" onclick="toggleStatus(<?= $product['id'] ?>, <?= $product['is_active'] ?>)">
                                            Activate
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New Product</h2>
            <form id="productForm" action="../../controllers/manage_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="product_id" id="productId">
                <input type="hidden" name="current_image" id="currentImage">
                
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (₹) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock_quantity" min="0" required>
                </div>
                
                <!-- Image Upload Section -->
                <div class="form-group">
                    <label for="productImage">Product Image</label>
                    <input type="file" id="productImage" name="product_image" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <small style="color: #6c757d; display: block; margin-top: 0.5rem;">
                        📁 Supported: JPG, PNG, WEBP | Max size: 2MB | Recommended: 300x200px
                    </small>
                    
                    <!-- Image Preview -->
                    <div id="imagePreview" style="margin-top: 1rem; display: none;">
                        <label>Current Image:</label>
                        <div style="margin-top: 0.5rem;">
                            <img id="previewImg" src="" alt="Product preview" style="max-width: 200px; max-height: 150px; border-radius: 10px; border: 2px solid #ddd;">
                        </div>
                        <small style="color: #856404; display: block; margin-top: 0.5rem;">
                            ℹ️ Upload new image to replace current one
                        </small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn-primary">Save Product</button>
                    <button type="button" class="btn-danger" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast" style="display: none;"></div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('productModal').classList.add('active');
        }

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('category').value = product.category_id;
            document.getElementById('description').value = product.description;
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock_quantity;
            document.getElementById('currentImage').value = product.image || '';
            
            // Show current image if exists
            if (product.image) {
                const imagePreview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                const imagePath = '../assets/images/products/' + product.image;
                
                previewImg.src = imagePath;
                imagePreview.style.display = 'block';
            } else {
                document.getElementById('imagePreview').style.display = 'none';
            }
            
            document.getElementById('productModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('productImage').value = '';
        }
        
        // Live preview when selecting new image
        document.getElementById('productImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image size must be less than 2MB');
                    e.target.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    const imagePreview = document.getElementById('imagePreview');
                    const previewImg = document.getElementById('previewImg');
                    previewImg.src = event.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        async function toggleStatus(productId, currentStatus) {
            const action = currentStatus ? 'disable' : 'activate';
            const actionVerb = currentStatus ? 'disabled' : 'activated';
            
            if (!confirm(`Are you sure you want to ${action} this product?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('product_id', productId);
                formData.append('is_active', currentStatus ? 0 : 1);

                const response = await fetch('../../controllers/manage_product.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    // Show success message before reload
                    alert(`Product ${actionVerb} successfully!`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error updating product status');
                console.error(error);
            }
        }

        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
