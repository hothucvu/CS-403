

-- 1. BẢNG NGƯỜI DÙNG (USERS)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    reputation_score INT DEFAULT 100 COMMENT 'Điểm uy tín của người dùng',
    barter_wallet INT DEFAULT 0 COMMENT 'Số dư điểm quy đổi (Barter Token)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. BẢNG DANH MỤC SẢN PHẨM (CATEGORIES)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- 3. BẢNG SẢN PHẨM / TIN ĐĂNG (PRODUCTS)
-- Tích hợp các cột lưu trữ dữ liệu AI (ai_tags, suggested_price)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL COMMENT 'Giá bán (nếu bán)',
    condition_percent INT NOT NULL COMMENT 'Độ mới tính theo phần trăm (%)',
    transaction_type ENUM('sell', 'barter', 'both') DEFAULT 'both' COMMENT 'Hình thức: Bán, Đổi, hoặc Cả hai',
    
    -- Các trường phục vụ AI (Nhóm 2 tương tác)
    ai_tags TEXT DEFAULT NULL COMMENT 'JSON hoặc chuỗi các tag do AI Auto-Tagging nhận diện',
    suggested_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Giá gợi ý từ AI Price Advisor',
    moderation_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' COMMENT 'Trạng thái kiểm duyệt AI/Admin',
    
    status ENUM('active', 'sold', 'bartered', 'hidden') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. BẢNG GIỎ HÀNG (CARTS)
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. BẢNG ĐƠN HÀNG (ORDERS - Mua bán truyền thống)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'COD',
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    order_status ENUM('pending', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. BẢNG CHI TIẾT ĐƠN HÀNG (ORDER_ITEMS)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. BẢNG HỆ THỐNG TRAO ĐỔI ĐỒ (BARTER_REQUESTS - Tính năng cốt lõi)
CREATE TABLE IF NOT EXISTS barter_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL COMMENT 'Người chủ động đề nghị đổi',
    receiver_id INT NOT NULL COMMENT 'Người sở hữu món đồ muốn đổi',
    offered_product_id INT INT NOT NULL COMMENT 'Món đồ bên gửi mang ra đổi',
    requested_product_id INT INT NOT NULL COMMENT 'Món đồ của bên nhận muốn lấy',
    compensation_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Tiền/điểm bù thêm nếu lệch giá trị',
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offered_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. BẢNG ĐÁNH GIÁ & UY TÍN (REVIEWS)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT DEFAULT NULL,
    barter_request_id INT DEFAULT NULL,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;