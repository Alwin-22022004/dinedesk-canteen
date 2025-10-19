CREATE DATABASE IF NOT EXISTS canteen_db;
USE canteen_db;

-- Drop tables if they exist to recreate with proper constraints
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- Users table with enhanced fields
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(15),
  role ENUM('student','staff','admin') DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product categories
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  icon VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table with categories
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(8,2) NOT NULL,
  image VARCHAR(255) DEFAULT 'default.jpg',
  is_active TINYINT(1) DEFAULT 1,
  stock_quantity INT DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_category (category_id),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table with enhanced status tracking
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Confirmed','Preparing','Ready','Completed','Cancelled') DEFAULT 'Pending',
  payment_method ENUM('Cash','UPI','Card') DEFAULT 'Cash',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(8,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO categories (name, icon) VALUES 
('Breakfast', '🍳'),
('Lunch', '🍱'),
('Snacks', '🍟'),
('Beverages', '☕'),
('Desserts', '🍰');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, image, stock_quantity) VALUES 
(1, 'Idli Sambar', 'Soft steamed rice cakes with sambar', 40.00, 'idli.jpg', 50),
(1, 'Masala Dosa', 'Crispy dosa with potato filling', 60.00, 'dosa.jpg', 50),
(1, 'Poha', 'Flattened rice with spices', 35.00, 'poha.jpg', 50),
(2, 'Veg Thali', 'Complete meal with rice, roti, dal, sabzi', 120.00, 'thali.jpg', 30),
(2, 'Paneer Butter Masala', 'Rich paneer curry with naan', 150.00, 'paneer.jpg', 25),
(3, 'Samosa', 'Crispy fried pastry with potato filling', 20.00, 'samosa.jpg', 100),
(3, 'Vada Pav', 'Spicy potato fritter in a bun', 25.00, 'vadapav.jpg', 80),
(3, 'Sandwich', 'Grilled vegetable sandwich', 40.00, 'sandwich.jpg', 60),
(4, 'Tea', 'Hot masala chai', 15.00, 'tea.jpg', 200),
(4, 'Coffee', 'Fresh filter coffee', 20.00, 'coffee.jpg', 200),
(4, 'Cold Coffee', 'Chilled coffee with ice cream', 50.00, 'coldcoffee.jpg', 100),
(5, 'Gulab Jamun', 'Sweet milk dumplings', 30.00, 'gulabjamun.jpg', 50),
(5, 'Ice Cream', 'Vanilla ice cream scoop', 40.00, 'icecream.jpg', 40);

-- Create default users
-- Admin user (password: admin123)
-- Staff user (password: staff123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@canteen.com', '$2y$10$EgjH3HoxK2vl8qJdKMPG3u0vQbO9BO7mKj9Q0k8sY4Fk3h2m9d0Om', 'admin'),
('Staff', 'staff@canteen.com', '$2y$10$EgjH3HoxK2vl8qJdKMPG3u0vQbO9BO7mKj9Q0k8sY4Fk3h2m9d0Om', 'staff');
