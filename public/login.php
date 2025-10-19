<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: products.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Dinedesk</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="container">
        <h1>🍽️ Dinedesk</h1>
        <h2>Welcome Back!</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="../controllers/auth.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" name="login" class="btn-primary">Login</button>
        </form>
        
        <p class="text-center mt-3">
            Don't have an account? <a href="register.php"><strong>Register Now</strong></a>
        </p>
        
    </div>
</body>
</html>
