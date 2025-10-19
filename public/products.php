<?php
// Auth check with cache prevention
require_once '../lib/auth_check.php';

require_once '../models/Product.php';
require_once '../models/Cart.php';

$productModel = new Product();
$cart = new Cart();

// Get filters from request
$category_id = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';

$products = $productModel->all($category_id, $search);
$categories = $productModel->getCategories();
$cart_items = $cart->items();
$cart_count = array_sum($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu |  Dinedesk</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="products.php" class="brand">🍽️  Dinedesk</a>
        <div class="nav-links">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</span>
            <a href="orders.php">📦 My Orders</a>
            <a href="cart.php">
                🛒 Cart 
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="full-container">
        <h1>Our Menu</h1>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="search-box">
                <form method="GET" action="products.php" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="search" placeholder="Search for food..." 
                           value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
                    <button type="submit" class="btn-secondary btn-sm">Search</button>
                    <?php if ($search || $category_id): ?>
                        <a href="products.php" class="btn btn-sm" style="background: #e74c3c;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="category-filters">
                <a href="products.php" class="category-btn <?= !$category_id ? 'active' : '' ?>">
                    All
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="products.php?category=<?= $cat['id'] ?>" 
                       class="category-btn <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                        <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3>No items found</h3>
                <p>Try adjusting your search or filters</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <?php 
                        // Use actual product image if available
                        if (!empty($p['image']) && file_exists('assets/images/products/' . $p['image'])) {
                            $imageSrc = 'assets/images/products/' . $p['image'];
                        } else {
                            $imageSrc = 'https://via.placeholder.com/300x200?text=' . urlencode($p['name']);
                        }
                        ?>
                        <img src="<?= $imageSrc ?>" 
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                        
                        <div class="card-body">
                            <h3><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="description"><?= htmlspecialchars($p['description']) ?></p>
                            <div class="price">₹<?= number_format($p['price'], 2) ?></div>
                            
                            <!-- Stock Status -->
                            <?php if ($p['stock_quantity'] <= 0): ?>
                                <div style="background: #fee; color: #c00; padding: 0.5rem; border-radius: 8px; text-align: center; margin-bottom: 0.5rem; font-weight: bold;">
                                    ⚠️ Out of Stock
                                </div>
                            <?php elseif ($p['stock_quantity'] <= 10): ?>
                                <div style="background: #fff3cd; color: #856404; padding: 0.5rem; border-radius: 8px; text-align: center; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                    ⏳ Limited Stock
                                </div>
                            <?php else: ?>
                                <div style="background: #d4edda; color: #155724; padding: 0.5rem; border-radius: 8px; text-align: center; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                    ✅ Available
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-footer">
                                <?php if ($p['stock_quantity'] > 0): ?>
                                    <form class="add-to-cart-form" style="flex: 1;">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($p['name']) ?>">
                                        <input type="hidden" name="product_price" value="<?= $p['price'] ?>">
                                        <button type="submit" class="btn-success" style="width: 100%;">
                                            Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn" style="width: 100%; background: #aaa; cursor: not-allowed;" disabled>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="display: none;"></div>

    <script>
        // AJAX Add to Cart
        document.querySelectorAll('.add-to-cart-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('../controllers/add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    showToast(data.message || 'Added to cart!');
                    
                    // Update cart badge
                    if (data.cart_count) {
                        updateCartBadge(data.cart_count);
                    }
                } catch (error) {
                    showToast('Error adding to cart');
                }
            });
        });

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function updateCartBadge(count) {
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.textContent = count;
            } else if (count > 0) {
                const cartLink = document.querySelector('a[href="cart.php"]');
                cartLink.innerHTML += ' <span class="cart-badge">' + count + '</span>';
            }
        }
    </script>
</body>
</html>
