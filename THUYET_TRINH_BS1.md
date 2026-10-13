# KỊCH BẢN THUYẾT TRÌNH — BOOK SHOP MINI V2 / MÔN CÔNG NGHỆ PHẦN MỀM

## Giới thiệu

Em xây dựng Book Shop mini bằng **PHP thuần + MySQL + XAMPP**. Trọng tâm của module **BS1 là phân quyền**, áp dụng RBAC cùng giới hạn phạm vi dữ liệu theo vai trò. Có 4 role, có thể có nhiều tài khoản cho một role.

## Phân quyền chức năng

- **Admin**: quản lý tài khoản và ma trận quyền, cấu hình thông tin cửa hàng.
- **Manager**: quản lý kho/giá/sách, phân công đơn, thống kê doanh thu và lịch sử mua.
- **Employee**: chỉ thấy đơn được phân công, cập nhật trạng thái và tiếp nhận hỗ trợ khách.
- **Customer**: xem sách, giỏ, đặt đơn, theo dõi/hủy đơn của mình, gửi yêu cầu hỗ trợ.

## Lược đồ nghiệp vụ

`Customer đặt hàng → orders / order_items → Manager phân công → orders.assigned_to → Employee xử lý → order_events → Manager xem báo cáo`

`Customer tạo ticket → support_tickets → Employee tiếp nhận → support_messages → Employee giải quyết → Customer xem phản hồi`

Admin thay đổi quyền qua `roles.php` và cấu hình hệ thống qua `settings.php`. Quyền chức năng được lưu ở `permissions`, `role_permissions`. Người xử lý đơn được lưu ở `orders.assigned_to`, lịch sử phân công ở `order_assignments`.

## Mô tả điểm chấm về bảo mật

- Menu ẩn theo quyền, **nhưng server kiểm tra lại quyền** khi vào trang và gửi POST.
- Customer chỉ xem đơn của chính mình. Employee chỉ thấy đơn được giao và không thể cập nhật đơn của người khác kể cả tự sửa ID trên URL/POST.
- Employee chỉ nhận ticket còn mở chưa ai nhận; một ticket chỉ có thể được nhận bởi một Employee nhờ transaction + `SELECT ... FOR UPDATE`.
- Manager phân công cho tài khoản có đúng role Employee và đang hoạt động.
- Admin sửa tên cửa hàng, thông báo, bật/tắt đăng ký; khác với một trang cấu hình chỉ hiển thị cho có.

## Demo thực tế

1. Admin đăng nhập, mở `Cài đặt cửa hàng`, thay đổi tên và thông báo, lưu và kiểm tra.
2. Customer đăng nhập, đặt đơn hàng và tạo ticket hỗ trợ gắn với đơn.
3. Manager đăng nhập, vào `Đơn hàng`, mở đơn và phân công Employee, kiểm tra lịch sử phân công.
4. Employee đăng nhập, nhìn thấy đơn được giao, cập nhật `confirmed → packing → shipped → delivered`. Vào hỗ trợ, tiếp nhận và phản hồi ticket.
5. Manager đăng nhập lại, xem doanh thu đơn đã giao, lịch sử mua Customer; Customer xem phản hồi hỗ trợ.
6. Truy cập trái phép `roles.php` bằng Employee hoặc đơn của khách A bằng khách B: trả `403`.

## Lưu ý khi được hỏi

- Doanh thu dựa trên đơn **đã giao**, vì hệ thống đang mô phỏng COD. Chưa đối soát tiền thu thực.
- Giỏ hàng lưu MySQL (carts/cart_items), không mất khi logout.
- Đơn hàng cũ sau nâng cấp chưa được phân công, Manager phải vào phân công trước khi Employee xử lý.
- `handled_by`: tài khoản cập nhật trạng thái gần nhất; `assigned_to`: nhân viên được giao. Hai thông tin không đồng nghĩa.
- Hệ thống chưa có thanh toán/gửi email thật; phạm vi chính là học tập và phân quyền trên XAMPP.
