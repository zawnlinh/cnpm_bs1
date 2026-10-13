-- V2 upgrade cho database `bookshop_mini` cua ZIP Book Shop Mini 4 vai tro.
-- SAO LUU CSDL truoc khi import. CHI CHAY 1 LAN cho ban cu; ban moi import bookshop.sql.
USE bookshop_mini;
ALTER TABLE orders
 ADD COLUMN assigned_to INT UNSIGNED NULL,
 ADD COLUMN assigned_at DATETIME NULL,
 ADD INDEX idx_orders_assigned_to(assigned_to),
 ADD CONSTRAINT fk_orders_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

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

INSERT IGNORE INTO permissions(id,code,name,section) VALUES
 (13,'orders.assign','Phân công đơn cho nhân viên','Đơn hàng'),
 (14,'settings.manage','Cài đặt thông tin cửa hàng','Hệ thống'),
 (15,'support.own','Gửi/xem yêu cầu hỗ trợ của mình','Hỗ trợ'),
 (16,'support.handle','Tiếp nhận và xử lý hỗ trợ','Hỗ trợ'),
 (17,'support.view_all','Xem toàn bộ yêu cầu hỗ trợ','Hỗ trợ');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
 SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
 WHERE r.code='admin' AND p.code IN ('orders.assign','settings.manage','support.own','support.handle','support.view_all');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
 SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
 WHERE r.code='manager' AND p.code IN ('orders.assign','support.handle','support.view_all');
INSERT IGNORE INTO role_permissions(role_id,permission_id)
 SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
 WHERE r.code='employee' AND p.code='support.handle';
INSERT IGNORE INTO role_permissions(role_id,permission_id)
 SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
 WHERE r.code='customer' AND p.code='support.own';
INSERT IGNORE INTO app_settings(`key`,`value`) VALUES
 ('store_name','Book Shop Mini'),('store_email','hotro@bookshop.test'),
 ('store_phone','0900000000'),('store_notice','Chào mừng bạn đến với Book Shop Mini!'),
 ('low_stock_threshold','5'),('registration_enabled','1');
-- Cac don cu CHUA co nhan vien duoc phan cong: Manager phai phan cong tren giao dien.
