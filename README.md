# 🍽️ Dinedesk - Canteen Management System

A modern, full-featured canteen management system built with PHP and MySQL.

![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Features

### 👤 **For Customers:**
- Browse menu with categories and search
- Add items to cart with real-time updates
- Stock availability checking
- Multiple payment options (Cash, Razorpay)
- Order tracking and history
- Beautiful responsive UI

### 👨‍🍳 **For Staff:**
- Real-time order dashboard
- Order status management (6 stages)
- Search and filter orders
- Order completion tracking
- Grid-based card layout

### 👨‍💼 **For Admin:**
- Comprehensive dashboard with statistics
- Product management (CRUD operations)
- Order management with search/filters
- Sales reports & analytics
- Top selling products analysis
- Payment method breakdown
- Daily sales trends with charts
- PDF report export
- Stock management

---

## 🚀 Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Charts:** Chart.js
- **Payment:** Razorpay Integration
- **Server:** Apache (XAMPP)

---

## 📦 Installation

### Prerequisites:
- XAMPP (or any PHP 7.4+ server)
- MySQL Database
- Web Browser (Chrome/Firefox recommended)

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/dinedesk.git
cd dinedesk
```

### Step 2: Import Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database: `canteen_db`
3. Import the SQL file: `database/canteen_db.sql`

### Step 3: Configure Database
Edit `lib/Database.php` with your database credentials:
```php
private $host = "localhost";
private $db_name = "canteen_db";
private $username = "root";
private $password = "";
```

### Step 4: Configure Razorpay (Optional)
Edit `config/razorpay_config.php` with your Razorpay credentials:
```php
define('RAZORPAY_KEY_ID', 'your_key_id');
define('RAZORPAY_KEY_SECRET', 'your_key_secret');
```

### Step 5: Start Server
1. Start XAMPP (Apache + MySQL)
2. Access: `http://localhost/canteen/public/`

---

## 👥 Default Credentials

### Admin:
- **Email:** admin@dinedesk.com
- **Password:** admin123

### Staff:
- **Email:** staff@dinedesk.com
- **Password:** staff123

### Customer:
- Register new account at `/public/register.php`

---

## 📁 Project Structure

```
canteen/
├── config/              # Configuration files
│   └── razorpay_config.php
├── controllers/         # Business logic controllers
│   ├── add_to_cart.php
│   ├── checkout.php
│   ├── export_pdf_simple.php
│   └── ...
├── database/            # Database files
│   └── canteen_db.sql
├── lib/                 # Core libraries
│   ├── Database.php
│   └── auth_check.php
├── models/              # Data models
│   ├── Cart.php
│   ├── Order.php
│   ├── Product.php
│   └── User.php
├── public/              # Public accessible files
│   ├── admin/          # Admin panel
│   ├── staff/          # Staff panel
│   ├── assets/         # CSS, JS, Images
│   ├── index.php       # Homepage
│   ├── login.php
│   ├── products.php
│   └── ...
└── README.md
```

---

## 🎨 Features Showcase

### 🏠 Homepage
- Beautiful landing page with menu preview
- Feature highlights
- Call-to-action buttons
- Responsive design

### 🛒 Shopping Experience
- Real-time cart updates (AJAX)
- Stock validation
- Toast notifications
- Smooth user experience

### 📊 Admin Dashboard
- Key metrics cards
- Grid layout for orders
- Search & filter functionality
- Status management

### 📈 Reports & Analytics
- Sales trends with Chart.js
- Top selling products
- Payment method breakdown
- PDF export capability

---

## 🔐 Security Features

- Session-based authentication
- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars)
- CSRF token protection (recommended to add)
- Cache prevention headers

---

## 📱 Responsive Design

- Mobile-first approach
- Tablet optimized
- Desktop enhanced
- Print-friendly reports

---

## 🛠️ Future Enhancements

- [ ] Email notifications
- [ ] SMS alerts
- [ ] Multi-branch support
- [ ] Inventory management
- [ ] Customer loyalty program
- [ ] Discount coupons
- [ ] QR code ordering
- [ ] Mobile app

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👨‍💻 Developer

Developed with ❤️ for modern canteen management

---

## 📞 Support

For support, email support@dinedesk.com or create an issue in the repository.

---

## 🙏 Acknowledgments

- Chart.js for beautiful charts
- Razorpay for payment integration
- Font Awesome for icons (if used)
- The open-source community

---

**⭐ If you find this project useful, please give it a star!**
