<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: products.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Dinedesk</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .validation-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .validation-icon.show {
            opacity: 1;
        }
        
        .validation-icon.valid {
            color: #10b981;
        }
        
        .validation-icon.invalid {
            color: #ef4444;
        }
        
        .validation-message {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: none;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .validation-message.show {
            display: block;
        }
        
        .validation-message.error {
            background: #fef2f2;
            color: #991b1b;
            border-left: 3px solid #ef4444;
        }
        
        .validation-message.success {
            background: #f0fdf4;
            color: #166534;
            border-left: 3px solid #10b981;
        }
        
        input.valid {
            border-color: #10b981 !important;
        }
        
        input.invalid {
            border-color: #ef4444 !important;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }
        
        .password-strength.show {
            display: block;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .strength-weak {
            width: 33%;
            background: #ef4444;
        }
        
        .strength-medium {
            width: 66%;
            background: #f59e0b;
        }
        
        .strength-strong {
            width: 100%;
            background: #10b981;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            color: #6b7280;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .requirement-icon {
            font-size: 0.9rem;
            opacity: 0.5;
        }
        
        .requirement.met {
            color: #10b981;
        }
        
        .requirement.met .requirement-icon {
            opacity: 1;
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Dinedesk</h1>
        <h2>Create Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="../controllers/auth.php" id="registerForm" novalidate>
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required minlength="3">
                    <span class="validation-icon" id="nameIcon"></span>
                </div>
                <div class="validation-message" id="nameMessage"></div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <span class="validation-icon" id="emailIcon"></span>
                </div>
                <div class="validation-message" id="emailMessage"></div>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number (Optional)</label>
                <div class="input-wrapper">
                    <input type="tel" id="phone" name="phone" placeholder="10-digit phone number" pattern="[0-9]{10}" maxlength="10">
                    <span class="validation-icon" id="phoneIcon"></span>
                </div>
                <div class="validation-message" id="phoneMessage"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Create a strong password" required minlength="6">
                    <span class="validation-icon" id="passwordIcon"></span>
                </div>
                <div class="password-strength" id="passwordStrength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="req-length">
                        <span class="requirement-icon">○</span>
                        <span>At least 6 characters</span>
                    </div>
                    <div class="requirement" id="req-letter">
                        <span class="requirement-icon">○</span>
                        <span>Contains a letter</span>
                    </div>
                    <div class="requirement" id="req-number">
                        <span class="requirement-icon">○</span>
                        <span>Contains a number</span>
                    </div>
                </div>
                <div class="validation-message" id="passwordMessage"></div>
            </div>
            
            <button type="submit" name="register" class="btn-primary" id="submitBtn">Create Account</button>
        </form>
        
        <p class="text-center mt-3">
            Already have an account? <a href="login.php"><strong>Login Here</strong></a>
        </p>
    </div>

    <script>
        // Validation functions
        const validateName = (name) => {
            if (name.length === 0) {
                return { valid: false, message: '' };
            }
            if (name.length < 3) {
                return { valid: false, message: 'Name must be at least 3 characters long' };
            }
            if (!/^[a-zA-Z\s]+$/.test(name)) {
                return { valid: false, message: 'Name should only contain letters and spaces' };
            }
            return { valid: true, message: 'Looks good!' };
        };

        const validateEmail = (email) => {
            if (email.length === 0) {
                return { valid: false, message: '' };
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                return { valid: false, message: 'Please enter a valid email address' };
            }
            return { valid: true, message: 'Valid email address' };
        };

        const validatePhone = (phone) => {
            if (phone.length === 0) {
                return { valid: true, message: '' }; // Optional field
            }
            if (!/^[0-9]+$/.test(phone)) {
                return { valid: false, message: 'Phone number should only contain digits' };
            }
            if (phone.length !== 10) {
                return { valid: false, message: 'Phone number must be exactly 10 digits' };
            }
            return { valid: true, message: 'Valid phone number' };
        };

        const validatePassword = (password) => {
            if (password.length === 0) {
                return { valid: false, message: '', strength: 0 };
            }
            
            let strength = 0;
            const requirements = {
                length: password.length >= 6,
                letter: /[a-zA-Z]/.test(password),
                number: /[0-9]/.test(password)
            };

            // Update requirements UI
            document.getElementById('req-length').classList.toggle('met', requirements.length);
            document.getElementById('req-letter').classList.toggle('met', requirements.letter);
            document.getElementById('req-number').classList.toggle('met', requirements.number);

            if (requirements.length) strength++;
            if (requirements.letter) strength++;
            if (requirements.number) strength++;

            const allMet = requirements.length && requirements.letter && requirements.number;

            if (!requirements.length) {
                return { valid: false, message: 'Password must be at least 6 characters', strength };
            }
            if (!allMet) {
                return { valid: false, message: 'Password should contain letters and numbers', strength };
            }
            
            return { valid: true, message: 'Strong password!', strength };
        };

        const showValidation = (inputId, iconId, messageId, result) => {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            const message = document.getElementById(messageId);

            if (result.message === '') {
                icon.classList.remove('show');
                message.classList.remove('show');
                input.classList.remove('valid', 'invalid');
                return;
            }

            // Update icon
            icon.textContent = result.valid ? '✓' : '✕';
            icon.className = `validation-icon show ${result.valid ? 'valid' : 'invalid'}`;

            // Update message
            if (result.message) {
                message.textContent = result.message;
                message.className = `validation-message show ${result.valid ? 'success' : 'error'}`;
            }

            // Update input border
            input.classList.toggle('valid', result.valid);
            input.classList.toggle('invalid', !result.valid);
        };

        const updatePasswordStrength = (strength) => {
            const strengthBar = document.getElementById('strengthBar');
            const strengthContainer = document.getElementById('passwordStrength');

            if (strength === 0) {
                strengthContainer.classList.remove('show');
                return;
            }

            strengthContainer.classList.add('show');
            strengthBar.className = 'password-strength-bar';

            if (strength === 1) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 2) {
                strengthBar.classList.add('strength-medium');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-strong');
            }
        };

        const checkFormValidity = () => {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;

            const nameValid = validateName(name).valid;
            const emailValid = validateEmail(email).valid;
            const phoneValid = validatePhone(phone).valid;
            const passwordValid = validatePassword(password).valid;

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = !(nameValid && emailValid && phoneValid && passwordValid);
        };

        // Event listeners
        document.getElementById('name').addEventListener('input', (e) => {
            const result = validateName(e.target.value);
            showValidation('name', 'nameIcon', 'nameMessage', result);
            checkFormValidity();
        });

        document.getElementById('email').addEventListener('input', (e) => {
            const result = validateEmail(e.target.value);
            showValidation('email', 'emailIcon', 'emailMessage', result);
            checkFormValidity();
        });

        document.getElementById('phone').addEventListener('input', (e) => {
            // Only allow numbers
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            const result = validatePhone(e.target.value);
            showValidation('phone', 'phoneIcon', 'phoneMessage', result);
            checkFormValidity();
        });

        document.getElementById('password').addEventListener('input', (e) => {
            const result = validatePassword(e.target.value);
            showValidation('password', 'passwordIcon', 'passwordMessage', result);
            updatePasswordStrength(result.strength);
            checkFormValidity();
        });

        // Initial check
        checkFormValidity();

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;

            const nameValid = validateName(name).valid;
            const emailValid = validateEmail(email).valid;
            const phoneValid = validatePhone(phone).valid;
            const passwordValid = validatePassword(password).valid;

            if (!nameValid || !emailValid || !phoneValid || !passwordValid) {
                e.preventDefault();
                alert('Please fix all validation errors before submitting');
            }
        });
    </script>
</body>
</html>
