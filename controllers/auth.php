<?php
require_once __DIR__ . '/../models/User.php';
session_start();

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Registration
    if (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        
        // Validation
        if (strlen($name) < 3) {
            $_SESSION['error'] = "Name must be at least 3 characters long";
            header("Location: ../public/register.php");
            exit();
        }
        
        if (strlen($password) < 6) {
            $_SESSION['error'] = "Password must be at least 6 characters long";
            header("Location: ../public/register.php");
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format";
            header("Location: ../public/register.php");
            exit();
        }
        
        try {
            $result = $userModel->register($name, $email, $password, $phone);
            if ($result) {
                $_SESSION['success'] = "Account created successfully! Please login.";
                header("Location: ../public/login.php");
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
                header("Location: ../public/register.php");
            }
        } catch (PDOException $e) {
            // Check for duplicate entry error (error code 23000)
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = "Email already exists. Please use a different email.";
            } else {
                // Database connection or other error
                $_SESSION['error'] = "Database error: " . $e->getMessage() . ". Please make sure the database is set up correctly.";
            }
            header("Location: ../public/register.php");
        } catch (Exception $e) {
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
            header("Location: ../public/register.php");
        }
        exit();
    }

    // Login
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Please enter both email and password";
            header("Location: ../public/login.php");
            exit();
        }
        
        $user = $userModel->login($email, $password);
        if ($user) {
            $_SESSION['user'] = $user;
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../public/admin/dashboard.php");
            } elseif ($user['role'] === 'staff') {
                header("Location: ../public/staff/dashboard.php");
            } else {
                header("Location: ../public/products.php");
            }
        } else {
            $_SESSION['error'] = "Invalid email or password. Please try again.";
            header("Location: ../public/login.php");
        }
        exit();
    }
}

// If accessed directly without POST, redirect to login
header("Location: ../public/login.php");
exit();
?>
