<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
require_once '../models/Product.php';

$productModel = new Product();
$categories = $productModel->getCategories();
$featured_products = $productModel->all(); // Get all products
// Limit to 8 featured items for homepage
$featured_products = array_slice($featured_products, 0, 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Dinedesk - Delicious Food Awaits!</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Hero Section */
        .hero-section {
            background: url('assets/images/products/home.jpg') center/cover no-repeat;
            color: white;
            text-align: center;
            padding: 5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1.2s ease;
        }
        
        .hero-btn {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            cursor: pointer;
        }
        
        .hero-btn-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .hero-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .hero-btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }
        
        .hero-btn-secondary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-3px);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Features Section */
        .features-section {
            padding: 4rem 2rem;
            background: #f9fafb;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .feature-desc {
            color: #6b7280;
            line-height: 1.6;
        }
        
        /* Menu Preview Section */
        .menu-section {
            padding: 4rem 2rem;
            background: white;
        }
        
        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 3rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: #6b7280;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            text-decoration: none;
        }
        
        .menu-card:hover .menu-card-title {
            text-decoration: none;
        }
        
        .menu-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .menu-card-body {
            padding: 1.5rem;
        }
        
        .menu-card-category {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }
        
        .menu-card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            text-decoration: none;
        }
        
        .menu-card-desc {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .menu-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .menu-card-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .menu-card-cta {
            background: #667eea;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .menu-card:hover .menu-card-cta {
            background: #5568d3;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .cta-button {
            background: white;
            color: #ff6b6b;
            padding: 1.2rem 3rem;
            font-size: 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .cta-button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        /* Footer */
        .footer {
            background: #1f2937;
            color: white;
            text-align: center;
            padding: 2rem;
        }
        
        .footer-text {
            opacity: 0.8;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .cta-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-title">🍽️ Welcome to Dinedesk</div>
            <div class="hero-subtitle">
                Delicious food made with love, just a click away!
            </div>
            <div class="hero-buttons">
                <a href="login.php" class="hero-btn hero-btn-primary">Order Now</a>
                <a href="login.php" class="hero-btn hero-btn-secondary">View Menu</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">Fast Service</div>
                <div class="feature-desc">Quick preparation and pickup times for your convenience</div>
            </div>
            
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon">🍔</div>
                <div class="feature-title">Fresh Food</div>
                <div class="feature-desc">Made fresh daily with quality ingredients</div>
            </div>
            
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon">💳</div>
                <div class="feature-title">Easy Payment</div>
                <div class="feature-desc">Multiple payment options including cash and online</div>
            </div>
            
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Order Tracking</div>
                <div class="feature-desc">Track your order status in real-time</div>
            </div>
        </div>
    </section>

    <!-- Menu Preview Section -->
    <section class="menu-section">
        <div class="section-header">
            <h2 class="section-title">Our Menu</h2>
            <p class="section-subtitle">Explore our delicious selection of freshly prepared meals</p>
        </div>
        
        <div class="menu-grid">
            <?php foreach ($featured_products as $product): ?>
                <a href="login.php" class="menu-card">
                    <?php 
                    // Use actual product image if available
                    if (!empty($product['image']) && file_exists('assets/images/products/' . $product['image'])) {
                        $imageSrc = 'assets/images/products/' . $product['image'];
                    } else {
                        $imageSrc = 'https://via.placeholder.com/300x200?text=' . urlencode($product['name']);
                    }
                    ?>
                    <img src="<?= $imageSrc ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         class="menu-card-image"
                         onerror="this.src='https://via.placeholder.com/300x200?text=<?= urlencode($product['name']) ?>'">
                    
                    <div class="menu-card-body">
                        <span class="menu-card-category">
                            <?= htmlspecialchars($product['category_icon'] ?? '🍽️') ?> 
                            <?= htmlspecialchars($product['category_name'] ?? 'Food') ?>
                        </span>
                        <h3 class="menu-card-title"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="menu-card-desc"><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</p>
                        <div class="menu-card-footer">
                            <span class="menu-card-price">₹<?= number_format($product['price'], 2) ?></span>
                            <span class="menu-card-cta">Order Now →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2 class="cta-title">Ready to Order?</h2>
        <p class="cta-text">Join us today and enjoy delicious food delivered fresh!</p>
        <a href="login.php" class="cta-button">Get Started Now →</a>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p class="footer-text">&copy; 2024 Dinedesk. All rights reserved. | Made with ❤️</p>
    </footer>

    <script>
        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
