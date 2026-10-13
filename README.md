# BOOK SHOP MINI V2 — 4 vai trò · phân công đơn · hỗ trợ khách hàng · cấu hình cửa hàng

Đồ án mẫu PHP thuần + MySQL/MariaDB, chạy **XAMPP**. Chức năng chính là **RBAC: phân quyền chức năng và quyền truy cập dữ liệu**. Đây là bản nâng cấp Book Shop Mini V1: bổ sung đúng ba tính năng mới, **không xóa các module cũ**.

## Khắc phục Manager không xem được Thống kê (bản sửa lỗi)

Bản này cải thiện trang `reports.php` để **đơn vừa được Customer đặt cũng hiển thị trong thống kê**, ngay cả khi Employee chưa giao; "Giá trị đơn đã đặt" luôn tách riêng "Doanh thu đã giao".

**Nếu đã cài Book Shop Mini V2 trước đó**:
1. Sao lưu MySQL `bookshop_mini` qua phpMyAdmin → Export.
2. Chép đè mã PHP bản sửa lỗi lên `C:\xampp\htdocs\bookshop-mini`. Giữ nguyên cơ sở dữ liệu, **không chạy lại `bookshop.sql` hoặc `upgrade_v2.sql`** nếu V2 đã hoạt động.
3. Mở phpMyAdmin → Import **`sql/fix_manager_statistics.sql`** (chỉ cần nếu thiếu quyền thống kê Manager hoặc menu Thống kê bị ẩn; chạy lại cũng an toàn).
4. Đăng xuất Manager, đăng nhập lại, mở `http://localhost/bookshop-mini/reports.php`.
5. Kiểm thử: Customer đặt một đơn → Manager thấy **Tổng đơn** và **Giá trị đơn đã đặt** tăng → Employee xử lý đơn tới **Đã giao** → Manager thấy **Doanh thu đã giao** tăng.

**Nếu vẫn báo 403:** Admin vào Ma trận quyền `roles.php`, kiểm tra Manager có tick "Thống kê kinh doanh"; SQL bên trên thêm lại quyền này. **Nếu lỗi 500 hoặc màn hình trắng:** vui lòng chụp thông báo lỗi / log Apache của XAMPP vì môi trường tại đây chưa kết nối được tới MySQL trên máy của bạn.

## 1. Cài đặt trên XAMPP

Yêu cầu: **PHP >= 8.0**, Apache, MySQL/MariaDB, extension `pdo_mysql`.

### Cài hoàn toàn mới (chưa từng có Book Shop Mini)

1. Giải nén sao cho có file `C:\xampp\htdocs\bookshop-mini\app.php`.
2. Bật **Apache** và **MySQL** trong XAMPP.
3. Vào `http://localhost/phpmyadmin` → **Import** file `sql/bookshop.sql`. File tạo database riêng tên **bookshop_mini**.
4. Nếu MySQL có mật khẩu/port khác, sửa `config.php`.
5. Truy cập `http://localhost/bookshop-mini/setup.php`, nhấn tạo 4 tài khoản demo (chỉ khi CSDL chưa có user).
6. Mở `http://localhost/bookshop-mini/`.
7. Xóa hoặc đổi tên `setup.php` sau khi tạo tài khoản. Chỉ dùng mật khẩu demo trên localhost.

### Nâng cấp từ bản `bookshop-mini-4-vai-tro-xampp.zip` cũ (cần giữ dữ liệu)

1. **Backup** database `bookshop_mini` trong phpMyAdmin (**Export → SQL**) trước.
2. Giải nén **file code V2** và chép đè thư mục `C:\xampp\htdocs\bookshop-mini`. **Không xóa MySQL**.
3. Import **chỉ** `sql/upgrade_v2.sql` vào phpMyAdmin. File này thêm các bảng và cột mới, giữ nguyên đơn hàng, sách và tài khoản.
4. **Chỉ import migration một lần**. Nếu đã nâng cấp rồi thì không import lại. Không chạy `bookshop.sql` vào database cũ thay thế migration.
5. Đăng nhập các tài khoản hiện tại. **Không chạy lại setup.php** (đã có tài khoản).
6. Các đơn cũ mặc định **chưa phân công**. Manager vào Đơn hàng → Chi tiết → Phân công Employee; sau đó Employee mới xem được đơn đó.

Nếu import lỗi do cột `assigned_to` đã tồn tại, rất có thể nâng cấp đã chạy trước đó; kiểm tra bảng/cột trong phpMyAdmin, **không import lặp**. Nếu thấy lỗi thiếu bảng `app_settings`, `support_tickets`, `order_assignments` thì phải chạy đúng migration trước khi mở code V2.

### Tài khoản demo (cài mới)

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@bookshop.test` | `Admin@123456` |
| Manager | `manager@bookshop.test` | `Manager@123456` |
| Employee | `employee@bookshop.test` | `Employee@123456` |
| Customer | `customer@bookshop.test` | `Customer@123456` |

## 2. 4 vai trò và nghiệp vụ

- **Admin:** tạo/khóa/đổi vai trò tài khoản, chỉnh ma trận quyền RBAC, chỉnh thông tin và tùy chọn hệ thống (`settings.php`); có quyền quan sát/quản lý mọi đơn.
- **Manager:** quản lý sách/giá/kho, xem toàn bộ đơn và lịch sử Customer, xem thống kê, **phân công hoặc phân công lại đơn cho Employee** ở trang chi tiết; có thể xử lý đơn nếu được cấp quyền. Xem toàn bộ ticket hỗ trợ.
- **Employee:** chỉ thấy **đơn đã phân công cho chính mình**, và chỉ được đổi trạng thái những đơn đó. Xem ticket **mới chưa ai nhận** hoặc ticket **do mình nhận**, bấm Tiếp nhận → Phản hồi → Giải quyết. Không quản lý user hoặc phân quyền.
- **Customer:** xem sách, giỏ hàng (lưu SQL), đặt hàng COD mô phỏng, theo dõi/hủy đơn của mình, tạo yêu cầu hỗ trợ có thể gắn với đơn **của mình**, trao đổi với nhân viên, mở lại yêu cầu đã giải quyết.

### Ma trận quyền V2 (mặc định)

| Chức năng | Admin | Manager | Employee | Customer |
|---|:---:|:---:|:---:|:---:|
| Xem sách (`catalog.view`) | ✓ | ✓ | ✓ | ✓ |
| Quản lý sách/giá (`catalog.manage`) | ✓ | ✓ | — | — |
| Điều chỉnh tồn kho (`inventory.adjust`) | ✓ | ✓ | ✓ | — |
| Quản lý giỏ (`cart.manage`) | ✓ | — | — | ✓ |
| Đặt mua (`orders.place`) | ✓ | — | — | ✓ |
| Đơn của mình (`orders.own`) | ✓ | — | — | ✓ |
| Xem đơn (`orders.view_all`) | ✓ | ✓ | **chỉ được giao** | — |
| Cập nhật đơn (`orders.process`) | ✓ | ✓ | **chỉ được giao** | — |
| **Phân công đơn** (`orders.assign`) | ✓ | ✓ | — | — |
| Thống kê (`reports.view`) | ✓ | ✓ | — | — |
| Lịch sử khách (`customers.history`) | ✓ | ✓ | — | — |
| Quản lý user (`users.manage`) | ✓ | — | — | — |
| Phân quyền (`roles.manage`) | ✓ | — | — | — |
| **Cấu hình cửa hàng** (`settings.manage`) | ✓ | — | — | — |
| **Hỗ trợ của khách** (`support.own`) | ✓ | — | — | ✓ |
| **Tiếp nhận hỗ trợ** (`support.handle`) | ✓ | ✓ | ✓ | — |
| **Xem toàn bộ hỗ trợ** (`support.view_all`) | ✓ | ✓ | — | — |

Quyền chi tiết do Admin chỉnh ở `roles.php`. **Phạm vi dữ liệu (ownership/assigned employee)** còn được kiểm tra ở backend nên không thể mở URL của đơn/ticket người khác để xem hay sửa. Các vai trò không được tự nâng cấp quyền của mình.

## 3. 3 tính năng mới và nơi sử dụng

**Admin → Cài đặt cửa hàng (`settings.php`)**:
- Đổi tên shop → cập nhật tên sidebar và `<title>` trong trình duyệt.
- Đổi hotline/email → hiển thị ở trang Hỗ trợ khách hàng.
- Đổi thông báo shop → hiển thị tại Kho sách.
- Đổi ngưỡng tồn kho → thay đổi ngưỡng cảnh báo số sách gần hết trên dashboard Employee (nếu có quyền xem đơn).
- Bật/tắt đăng ký Customer → `register.php` kiểm tra ở server, ngăn đăng ký nếu tắt.

**Manager → Danh sách đơn → Chi tiết → Phân công**:
- Phải chọn tài khoản có role Employee đang hoạt động.
- Ghi nhận lịch sử thay đổi người phụ trách (`order_assignments`).
- Chỉ cho phân công đơn đang xử lý, không cho phân công đơn đã giao/hủy.
- Employee chỉ thấy đơn được gán, và `order_status.php` kiểm tra lại sau khi khóa transaction `FOR UPDATE`.
- `handled_by` vẫn ghi người cập nhật **trạng thái gần nhất**; `assigned_to` là người được giao, không bị ghi đè khi Manager cập nhật trạng thái.

**Customer → Hỗ trợ khách hàng → Tạo yêu cầu**:
- Có tiêu đề, mô tả và chọn một trong các đơn của mình (hoặc không chọn).
- Employee chỉ nhìn thấy yêu cầu còn mở chưa ai nhận hoặc yêu cầu được giao cho mình; bấm Tiếp nhận → Phản hồi → Đã giải quyết.
- Customer chỉ xem các ticket của mình, xem phản hồi và được mở lại sau khi đóng.
- Manager/Admin xem tất cả ticket. Đây là trao đổi **nội bộ ứng dụng**, không tự gửi email.

## 4. Kịch bản demo trước giảng viên (5–10 phút)

1. **Admin:** vào Cài đặt cửa hàng, sửa thông báo → qua Kho sách kiểm tra; vào Ma trận quyền giới thiệu RBAC.
2. **Customer:** chọn sách → thêm giỏ → nhập thông tin và đặt đơn #N → thấy Đơn của tôi → tạo ticket hỗ trợ liên quan đơn #N.
3. **Manager:** vào Đơn hàng → thấy #N → chọn Employee → Lưu phân công; mở lịch sử phân công; xem Thống kê và Khách hàng.
4. **Employee:** vào Đơn hàng → chỉ thấy #N của mình → xác nhận → đóng gói → đang giao → đã giao. Vào Hỗ trợ → Tiếp nhận ticket của Customer → Phản hồi → Giải quyết.
5. **Customer:** xem trạng thái đơn đã giao và câu trả lời hỗ trợ. Thử đổi ID của đơn/ticket thành của khách khác → **403**.
6. **Quyền bị chặn:** Employee mở `settings.php`, `users.php`, `roles.php` → **403**; Employee B mở chi tiết đơn phân công cho Employee A → **403**.

## 5. Dữ liệu & bảo mật

- CSDL: bảng cũ giữ nguyên, thêm `orders.assigned_to`, `orders.assigned_at`, `order_assignments`, `app_settings`, `support_tickets`, `support_messages`.
- Phân quyền RBAC + kiểm tra **dữ liệu theo chủ sở hữu**; API update phải qua POST + CSRF. Truy vấn PDO prepared; mật khẩu lưu hash.
- Đặt hàng và hoàn kho vẫn chạy qua transaction. Doanh thu lấy đơn `delivered` và chỉ là **mô phỏng COD**, chưa có xác thực thanh toán thực.
- Đây là ứng dụng cho lớp học / **localhost**, chưa đầy đủ an ninh để deploy công khai (rate-limit, nhật ký giám sát, HTTPS, email, thanh toán thật).
- SQL migration chỉ hỗ trợ nâng cấp trực tiếp **Book Shop Mini V1 có database `bookshop_mini`**, không dùng cho gói BS1 cũ có tên CSDL khác.

## 6. Kiểm tra

- Trên máy có PHP: `php -l settings.php`, `php -l support_action.php`, `php tests/test_rules.php`.
- XAMPP cài đặt và xác thực luồng MySQL: kiểm tra thủ công theo kịch bản mục 4.
- Khi chưa chạy MySQL trong môi trường kiểm tra, **lint/static test không thay thế kiểm thử tích hợp với XAMPP**.
