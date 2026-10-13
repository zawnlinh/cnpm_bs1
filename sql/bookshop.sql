-- BOOK SHOP MINI: thuc hanh phan quyen 4 vai tro, PHP + MySQL / XAMPP
-- Database rieng, khong ghi de ban BS1 cu.
CREATE DATABASE IF NOT EXISTS bookshop_mini CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookshop_mini;

CREATE TABLE IF NOT EXISTS roles (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(90) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS permissions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(70) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 section VARCHAR(80) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS role_permissions (
 role_id INT UNSIGNED NOT NULL,
 permission_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(role_id,permission_id),
 FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
 FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 role_id INT UNSIGNED NOT NULL,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(role_id) REFERENCES roles(id),
 INDEX(role_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS books (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 author VARCHAR(150) NOT NULL,
 description TEXT NULL,
 price DECIMAL(12,2) NOT NULL DEFAULT 0,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 INDEX(is_active), INDEX(category_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS carts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL UNIQUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS cart_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 cart_id INT UNSIGNED NOT NULL,
 book_id INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 UNIQUE KEY cart_book(cart_id,book_id),
 FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
 FOREIGN KEY(book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'placed',
 total_amount DECIMAL(14,2) NOT NULL,
 shipping_name VARCHAR(120) NOT NULL,
 shipping_phone VARCHAR(20) NOT NULL,
 shipping_address VARCHAR(300) NOT NULL,
 customer_note VARCHAR(300) NULL,
 payment_method VARCHAR(15) NOT NULL DEFAULT 'cod',
 handled_by INT UNSIGNED NULL,
 assigned_to INT UNSIGNED NULL,
 assigned_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id),
 FOREIGN KEY(handled_by) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(status),INDEX(created_at),INDEX(user_id),INDEX(assigned_to)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_assignments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NULL,
 old_employee_id INT UNSIGNED NULL,
 new_employee_id INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(old_employee_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(new_employee_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(order_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS app_settings (
 `key` VARCHAR(70) NOT NULL PRIMARY KEY,
 `value` VARCHAR(500) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS support_tickets (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NOT NULL,
 order_id INT UNSIGNED NULL,
 assigned_to INT UNSIGNED NULL,
 subject VARCHAR(150) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'open',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_id) REFERENCES users(id),
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
 FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(customer_id),INDEX(assigned_to),INDEX(status)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS support_messages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ticket_id INT UNSIGNED NOT NULL,
 sender_id INT UNSIGNED NULL,
 message TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
 FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(ticket_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 book_id INT UNSIGNED NULL,
 book_title VARCHAR(180) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NULL,
 old_status VARCHAR(20) NOT NULL,
 new_status VARCHAR(20) NOT NULL,
 note VARCHAR(200) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS stock_logs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 book_id INT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NULL,
 order_id INT UNSIGNED NULL,
 delta INT NOT NULL,
 reason VARCHAR(100) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(book_id) REFERENCES books(id),
 FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles(id,code,name) VALUES
 (1,'admin','Admin · Quản trị hệ thống'),
 (2,'manager','Manager · Quản lý cửa hàng'),
 (3,'employee','Employee · Nhân viên'),
 (4,'customer','Customer · Khách hàng');
INSERT IGNORE INTO permissions(id,code,name,section) VALUES
 (1,'catalog.view','Xem danh mục sách','Sách'),
 (2,'catalog.manage','Thêm / sửa / ẩn sách và giá','Sách'),
 (3,'inventory.adjust','Điều chỉnh tồn kho','Kho'),
 (4,'cart.manage','Thêm / sửa / xóa giỏ hàng của mình','Mua hàng'),
 (5,'orders.place','Đặt hàng và mua ngay','Mua hàng'),
 (6,'orders.own','Xem / hủy đơn của chính mình','Mua hàng'),
 (7,'orders.view_all','Xem đơn hàng để xử lý','Đơn hàng'),
 (8,'orders.process','Xử lý và cập nhật trạng thái đơn','Đơn hàng'),
 (9,'reports.view','Thống kê kinh doanh','Quản lý'),
 (10,'customers.history','Xem lịch sử mua của khách hàng','Quản lý'),
 (11,'users.manage','Quản lý và khóa tài khoản','Hệ thống'),
 (12,'roles.manage','Cấu hình ma trận phân quyền','Hệ thống'),
 (13,'orders.assign','Phân công đơn cho nhân viên','Đơn hàng'),
 (14,'settings.manage','Cài đặt thông tin cửa hàng','Hệ thống'),
 (15,'support.own','Gửi/xem yêu cầu hỗ trợ của mình','Hỗ trợ'),
 (16,'support.handle','Tiếp nhận và xử lý hỗ trợ','Hỗ trợ'),
 (17,'support.view_all','Xem toàn bộ yêu cầu hỗ trợ','Hỗ trợ');
-- Admin co moi quyen trong ma; backend cung bao ve Admin khong bi khoa quyen.
INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT 1,id FROM permissions;
-- Manager: kiem soat san pham, kho, don hang, xem so lieu, lich su KH
INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT 2,id FROM permissions
 WHERE code IN ('catalog.view','catalog.manage','inventory.adjust','orders.view_all','orders.process','orders.assign','reports.view','customers.history','support.handle','support.view_all');
-- Employee: tiep nhan/xu ly don va cap nhat kho, khong duoc sua gia
INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT 3,id FROM permissions
 WHERE code IN ('catalog.view','inventory.adjust','orders.view_all','orders.process','support.handle');
-- Customer: xem sach, gio hang, dat mua, theo doi don cua minh
INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT 4,id FROM permissions
 WHERE code IN ('catalog.view','cart.manage','orders.place','orders.own','support.own');

INSERT IGNORE INTO categories(id,name) VALUES (1,'Văn học'),(2,'Công nghệ'),(3,'Kinh doanh'),(4,'Kỹ năng sống');
INSERT IGNORE INTO books(id,category_id,title,author,description,price,stock,is_active) VALUES
 (1,1,'Dế Mèn Phiêu Lưu Ký','Tô Hoài','Tác phẩm thiếu nhi quen thuộc về những cuộc phiêu lưu.',65000,24,1),
 (2,1,'Tôi Thấy Hoa Vàng Trên Cỏ Xanh','Nguyễn Nhật Ánh','Câu chuyện tuổi thơ, gia đình và tình bạn.',115000,17,1),
 (3,2,'Lập trình PHP và MySQL','Nhóm biên soạn','Tài liệu thực hành xây dựng ứng dụng web PHP.',150000,35,1),
 (4,1,'Nhà Giả Kim','Paulo Coelho','Tiểu thuyết về hành trình tìm kiếm ước mơ.',79000,19,1),
 (5,2,'Clean Code','Robert C. Martin','Thực hành viết mã dễ đọc và dễ bảo trì.',285000,8,1),
 (6,3,'Khởi Nghiệp Tinh Gọn','Eric Ries','Các nguyên tắc xây dựng sản phẩm và thử nghiệm.',175000,12,1),
 (7,4,'Đắc Nhân Tâm','Dale Carnegie','Những bài học về giao tiếp và ứng xử.',98000,21,1),
 (8,2,'Cấu trúc dữ liệu và Giải thuật','Nhóm biên soạn','Kiến thức nền tảng về cấu trúc dữ liệu.',210000,15,1);
-- Tai khoan demo khong hardcode mat khau trong SQL: mo setup.php sau khi import.

INSERT IGNORE INTO app_settings(`key`,`value`) VALUES
 ('store_name','Book Shop Mini'),
 ('store_email','hotro@bookshop.test'),
 ('store_phone','0900000000'),
 ('store_notice','Chào mừng bạn đến với Book Shop Mini!'),
 ('low_stock_threshold','5'),
 ('registration_enabled','1');
