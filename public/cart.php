<?php
// Auth check with cache prevention
require_once '../lib/auth_check.php';

require_once '../models/Cart.php';
require_once '../models/Product.php';

$cart = new Cart();
$productModel = new Product();
$items = $cart->items();
$cart_count = array_sum($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Dinedesk</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="products.php" class="brand">🍽️ Dinedesk</a>
        <div class="nav-links">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</span>
            <a href="products.php">🏠 Menu</a>
            <a href="orders.php">📦 My Orders</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="full-container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <h3>Your cart is empty</h3>
                <p>Add some delicious items from our menu!</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 1rem; width: auto;">
                    Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    $has_stock_issues = false;
                    foreach($items as $id => $qty):
                        $p = $productModel->find($id);
                        if (!$p) continue;
                        $line_total = $p['price'] * $qty;
                        $total += $line_total;
                        
                        // Check if quantity exceeds stock
                        $stock_exceeded = $qty > $p['stock_quantity'];
                        if ($stock_exceeded) {
                            $has_stock_issues = true;
                        }
                    ?>
                        <div class="cart-item" data-product-id="<?= $id ?>" data-stock="<?= $p['stock_quantity'] ?>">
                            <?php 
                            // Use actual product image if available
                            if (!empty($p['image']) && file_exists('assets/images/products/' . $p['image'])) {
                                $cartImageSrc = 'assets/images/products/' . $p['image'];
                            } else {
                                $cartImageSrc = 'https://via.placeholder.com/100?text=' . urlencode($p['name']);
                            }
                            ?>
                            <img src="<?= $cartImageSrc ?>" 
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 onerror="this.src='https://via.placeholder.com/100?text=No+Image'">
                            
                            <div class="cart-item-details">
                                <h3><?= htmlspecialchars($p['name']) ?></h3>
                                <p class="item-price">₹<?= number_format($p['price'], 2) ?> each</p>
                                
                                <?php if ($stock_exceeded): ?>
                                    <div class="stock-warning" style="background: #fee2e2; color: #991b1b; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem; margin-top: 0.5rem; border-left: 3px solid #ef4444;">
                                        ⚠️ Only <?= $p['stock_quantity'] ?> available in stock!
                                    </div>
                                <?php elseif ($p['stock_quantity'] == 0): ?>
                                    <div class="stock-warning" style="background: #fee2e2; color: #991b1b; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem; margin-top: 0.5rem; border-left: 3px solid #ef4444;">
                                        ⚠️ Out of stock!
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-actions">
                                <div class="quantity-control">
                                    <button type="button" class="qty-decrease" data-id="<?= $id ?>">-</button>
                                    <input type="number" class="qty-input" value="<?= $qty ?>" 
                                           min="1" max="<?= $p['stock_quantity'] ?>" readonly>
                                    <button type="button" class="qty-increase" data-id="<?= $id ?>" 
                                            <?= $qty >= $p['stock_quantity'] ? 'disabled' : '' ?>>+</button>
                                </div>
                                
                                <div style="text-align: center; min-width: 80px;">
                                    <strong class="item-total">₹<?= number_format($line_total, 2) ?></strong>
                                </div>
                                
                                <button type="button" class="btn-danger btn-sm remove-item" data-id="<?= $id ?>">
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
                    
                    <?php if ($has_stock_issues): ?>
                        <div id="stockAlert" style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #ef4444; font-size: 0.9rem;">
                            <strong>⚠️ Stock Issue</strong><br>
                            Some items exceed available stock. Please adjust quantities to proceed.
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">₹<?= number_format($total, 2) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax (5%):</span>
                        <span id="tax">₹<?= number_format($total * 0.05, 2) ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="total">₹<?= number_format($total * 1.05, 2) ?></span>
                    </div>
                    
                    <form id="checkoutForm" action="../controllers/checkout.php" method="POST" style="margin-top: 2rem;">
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select name="payment_method" id="payment_method" required>
                                <option value="Cash">Cash on Pickup</option>
                                <option value="Razorpay">💳 Pay Online</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Special Instructions (Optional)</label>
                            <textarea name="notes" id="notes" rows="3" 
                                      placeholder="Any special requests?"></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary" id="checkoutBtn" 
                                <?= $has_stock_issues ? 'disabled' : '' ?>
                                style="<?= $has_stock_issues ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                            <?= $has_stock_issues ? '⚠️ Adjust Quantities' : 'Proceed to Checkout' ?>
                        </button>
                        
                        <div id="checkoutError" style="display: none; background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 6px; margin-top: 1rem; font-size: 0.85rem; text-align: center;">
                            Please adjust item quantities within available stock
                        </div>
                    </form>
                    
                    <a href="products.php" style="display: block; text-align: center; margin-top: 1rem;">
                        ← Continue Shopping
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Razorpay SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <script>
        // Get cart total for Razorpay (this will be updated when cart changes)
        let cartTotal = <?= $total * 1.05 ?>;
        
        // Handle checkout form submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            // Validate stock before proceeding
            if (!validateStock()) {
                e.preventDefault();
                const checkoutError = document.getElementById('checkoutError');
                if (checkoutError) {
                    checkoutError.style.display = 'block';
                }
                return false;
            }
            
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (paymentMethod === 'Razorpay') {
                e.preventDefault();
                initiateRazorpayPayment();
            }
            // For Cash payment, form submits normally
        });
        
        function initiateRazorpayPayment() {
            // Show loading
            const btn = document.getElementById('checkoutBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            // Get form data
            const notes = document.getElementById('notes').value;
            
            // Get current total from the page (most accurate)
            const currentTotal = parseFloat(document.getElementById('total').textContent.replace('₹', '').replace(',', ''));
            const amountInPaise = Math.round(currentTotal * 100);
            
            console.log('Cart Total: ₹' + currentTotal);
            console.log('Amount in Paise: ' + amountInPaise);
            
            // Create order on server first
            fetch('../controllers/razorpay_create_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: amountInPaise, // Convert to paise
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openRazorpayCheckout(data.order_id, data.amount, notes);
                } else {
                    alert('Error creating order: ' + data.message);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error initiating payment');
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }
        
        function openRazorpayCheckout(orderId, amount, notes) {
            const options = {
                "key": "<?php require_once '../config/razorpay_config.php'; echo RAZORPAY_KEY_ID; ?>",
                "amount": amount,
                "currency": "INR",
                "name": "Dinedesk",
                "description": "Order Payment",
                "order_id": orderId,
                "handler": function (response) {
                    verifyPaymentAndPlaceOrder(response, notes);
                },
                "prefill": {
                    "name": "<?= htmlspecialchars($_SESSION['user']['name']) ?>",
                    "email": "<?= htmlspecialchars($_SESSION['user']['email']) ?>"
                },
                "theme": {
                    "color": "#ff6b6b"
                },
                "modal": {
                    "ondismiss": function() {
                        document.getElementById('checkoutBtn').disabled = false;
                        document.getElementById('checkoutBtn').textContent = 'Proceed to Checkout';
                    }
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        }
        
        function verifyPaymentAndPlaceOrder(paymentResponse, notes) {
            // Show processing
            const btn = document.getElementById('checkoutBtn');
            btn.textContent = 'Verifying Payment...';
            
            // Send payment details to server for verification
            const formData = new FormData();
            formData.append('razorpay_order_id', paymentResponse.razorpay_order_id);
            formData.append('razorpay_payment_id', paymentResponse.razorpay_payment_id);
            formData.append('razorpay_signature', paymentResponse.razorpay_signature);
            formData.append('payment_method', 'Razorpay');
            formData.append('notes', notes);
            
            fetch('../controllers/checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Payment successful but order placement failed. Please contact support.');
            });
        }
        
        // Validate stock availability
        function validateStock() {
            let hasIssues = false;
            const cartItems = document.querySelectorAll('.cart-item');
            
            cartItems.forEach(item => {
                const qty = parseInt(item.querySelector('.qty-input').value);
                const stock = parseInt(item.dataset.stock);
                const warning = item.querySelector('.stock-warning');
                const increaseBtn = item.querySelector('.qty-increase');
                
                // Remove existing warning if present
                if (warning) {
                    warning.remove();
                }
                
                // Check stock
                if (stock === 0) {
                    hasIssues = true;
                    const details = item.querySelector('.cart-item-details');
                    details.innerHTML += '<div class="stock-warning" style="background: #fee2e2; color: #991b1b; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem; margin-top: 0.5rem; border-left: 3px solid #ef4444;">⚠️ Out of stock!</div>';
                } else if (qty > stock) {
                    hasIssues = true;
                    const details = item.querySelector('.cart-item-details');
                    details.innerHTML += '<div class="stock-warning" style="background: #fee2e2; color: #991b1b; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem; margin-top: 0.5rem; border-left: 3px solid #ef4444;">⚠️ Only ' + stock + ' available in stock!</div>';
                }
                
                // Disable/enable increase button based on stock
                if (increaseBtn) {
                    increaseBtn.disabled = qty >= stock;
                }
            });
            
            // Update checkout button
            const checkoutBtn = document.getElementById('checkoutBtn');
            const stockAlert = document.getElementById('stockAlert');
            const checkoutError = document.getElementById('checkoutError');
            
            if (hasIssues) {
                checkoutBtn.disabled = true;
                checkoutBtn.style.opacity = '0.5';
                checkoutBtn.style.cursor = 'not-allowed';
                checkoutBtn.textContent = '⚠️ Adjust Quantities';
                
                if (!stockAlert) {
                    const summary = document.querySelector('.cart-summary');
                    const alert = document.createElement('div');
                    alert.id = 'stockAlert';
                    alert.style.cssText = 'background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #ef4444; font-size: 0.9rem;';
                    alert.innerHTML = '<strong>⚠️ Stock Issue</strong><br>Some items exceed available stock. Please adjust quantities to proceed.';
                    summary.insertBefore(alert, summary.firstChild.nextSibling);
                }
            } else {
                checkoutBtn.disabled = false;
                checkoutBtn.style.opacity = '1';
                checkoutBtn.style.cursor = 'pointer';
                checkoutBtn.textContent = 'Proceed to Checkout';
                
                if (stockAlert) {
                    stockAlert.remove();
                }
                if (checkoutError) {
                    checkoutError.style.display = 'none';
                }
            }
            
            return !hasIssues;
        }
        
        // Initial validation
        validateStock();
        
        // Update quantity
        document.querySelectorAll('.qty-decrease, .qty-increase').forEach(btn => {
            btn.addEventListener('click', async function() {
                const productId = this.dataset.id;
                const cartItem = this.closest('.cart-item');
                const input = cartItem.querySelector('.qty-input');
                const stock = parseInt(cartItem.dataset.stock);
                let qty = parseInt(input.value);
                
                if (this.classList.contains('qty-decrease') && qty > 1) {
                    qty--;
                } else if (this.classList.contains('qty-increase') && qty < stock) {
                    qty++;
                } else {
                    return;
                }
                
                input.value = qty;
                await updateCartQuantity(productId, qty);
                validateStock();
            });
        });

        // Remove item
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (confirm('Remove this item from cart?')) {
                    const productId = this.dataset.id;
                    await removeFromCart(productId);
                }
            });
        });

        async function updateCartQuantity(productId, quantity) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('action', 'update');
                formData.append('quantity', quantity);
                
                const response = await fetch('../controllers/update_cart.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    updateCartUI(data);
                }
            } catch (error) {
                console.error('Error updating cart:', error);
            }
        }

        async function removeFromCart(productId) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('action', 'remove');
                
                const response = await fetch('../controllers/update_cart.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                    cartItem.remove();
                    
                    if (data.cart_count === 0) {
                        location.reload();
                    } else {
                        updateCartUI(data);
                    }
                }
            } catch (error) {
                console.error('Error removing item:', error);
            }
        }

        function updateCartUI(data) {
            if (data.subtotal !== undefined) {
                document.getElementById('subtotal').textContent = '₹' + data.subtotal.toFixed(2);
                document.getElementById('tax').textContent = '₹' + data.tax.toFixed(2);
                document.getElementById('total').textContent = '₹' + data.total.toFixed(2);
                
                // ⭐ UPDATE RAZORPAY CART TOTAL - This is crucial!
                cartTotal = data.total;
            }
            
            // Update item total
            if (data.product_id && data.line_total !== undefined) {
                const cartItem = document.querySelector(`[data-product-id="${data.product_id}"]`);
                if (cartItem) {
                    cartItem.querySelector('.item-total').textContent = '₹' + data.line_total.toFixed(2);
                }
            }
        }
    </script>
</body>
</html>
