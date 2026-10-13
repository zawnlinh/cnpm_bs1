<?php
/**
 * Manager / Admin: bao cao don dat va doanh thu da giao.
 * Luu y: don moi dat CHUA la doanh thu. Hien 2 chi so rieng de tranh nham lan.
 */
require_once __DIR__ . '/app.php';
require_permission('reports.view');

// Gia tri don mua: bo don huy. Doanh thu demo: chi don da giao.
$summary = db()->query(<<<'SQL'
SELECT
 COUNT(*) AS total_orders,
 COALESCE(SUM(CASE WHEN status = 'placed' THEN 1 ELSE 0 END), 0) AS new_orders,
 COALESCE(SUM(CASE WHEN status IN ('placed','confirmed','packing','shipped') THEN 1 ELSE 0 END), 0) AS processing_orders,
 COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) AS delivered_orders,
 COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_orders,
 COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_amount ELSE 0 END), 0) AS order_value,
 COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) AS delivered_revenue
FROM orders
SQL)->fetch();

// Khach moi dat hang se hien tren bieu do nay, khong can cho Employee giao xong.
$dailyOrders = db()->query(<<<'SQL'
SELECT DATE(created_at) AS report_day,
 COUNT(*) AS orders_count,
 COALESCE(SUM(total_amount), 0) AS order_value
FROM orders
WHERE status <> 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
GROUP BY DATE(created_at)
ORDER BY report_day DESC
SQL)->fetchAll();
$maxOrderValue = 1.0;
foreach ($dailyOrders as $day) {
    $maxOrderValue = max($maxOrderValue, (float)$day['order_value']);
}

// Danh sach sach duoc DAT nhieu: khong ghi nhan thanh 'da ban' neu chua giao.
$topOrderedBooks = db()->query(<<<'SQL'
SELECT oi.book_title AS book_title,
 SUM(oi.quantity) AS copies_ordered,
 SUM(oi.unit_price * oi.quantity) AS order_value
FROM order_items AS oi
INNER JOIN orders AS o ON o.id = oi.order_id
WHERE o.status <> 'cancelled'
GROUP BY oi.book_title
ORDER BY copies_ordered DESC, order_value DESC
LIMIT 6
SQL)->fetchAll();

// Quan ly xem duoc lich su dat hang moi nhat cua tat ca customer.
$recentOrders = db()->query(<<<'SQL'
SELECT o.id, o.status, o.total_amount, o.created_at, u.full_name
FROM orders AS o
INNER JOIN users AS u ON u.id = o.user_id
ORDER BY o.created_at DESC, o.id DESC
LIMIT 10
SQL)->fetchAll();

$pageTitle = 'Thống kê bán hàng';
require __DIR__ . '/partials/header.php';
?>
<div class="page-head">
 <div><div class="eyebrow">STORE PERFORMANCE</div><h1>Thống kê kinh doanh</h1>
  <p>Manager theo dõi đơn vừa đặt, tình trạng xử lý, khách mua và doanh thu đã giao trên MySQL.</p></div>
 <span class="badge green">Dữ liệu thật từ đơn hàng</span>
</div>

<div class="metric-grid">
 <div class="metric-card"><span class="metric-label">Tổng số đơn</span><strong><?= (int)$summary['total_orders'] ?></strong><small>Gồm cả đơn hủy</small></div>
 <div class="metric-card"><span class="metric-label">Đơn mới chờ xác nhận</span><strong><?= (int)$summary['new_orders'] ?></strong><small>Customer vừa đặt</small></div>
 <div class="metric-card"><span class="metric-label">Đơn đang xử lý</span><strong><?= (int)$summary['processing_orders'] ?></strong><small>Chưa hoàn tất / chưa hủy</small></div>
 <div class="metric-card"><span class="metric-label">Đơn đã giao</span><strong><?= (int)$summary['delivered_orders'] ?></strong><small>Hoàn thành giao hàng</small></div>
 <div class="metric-card"><span class="metric-label">Giá trị đơn đã đặt</span><strong><?= money($summary['order_value']) ?></strong><small>Không tính đơn hủy; chưa phải doanh thu</small></div>
 <div class="metric-card"><span class="metric-label">Doanh thu đã giao</span><strong><?= money($summary['delivered_revenue']) ?></strong><small>Chỉ tính đơn đã giao; COD mô phỏng</small></div>
</div>

<?php if ((int)$summary['total_orders'] === 0): ?>
<div class="notice">Chưa có dữ liệu đơn hàng. Hãy đăng nhập Customer, chọn sách và đặt một đơn thử, sau đó tải lại trang này bằng Manager.</div>
<?php elseif ((int)$summary['delivered_orders'] === 0): ?>
<div class="notice"><strong>Đã có đơn đặt mua nhưng chưa có đơn được giao.</strong> Vì vậy “Giá trị đơn đã đặt” có số liệu, còn “Doanh thu đã giao” vẫn là 0 đ. Manager phân công đơn cho Employee, Employee xử lý tới “Đã giao” thì doanh thu sẽ cập nhật.</div>
<?php endif; ?>

<div class="two-col">
 <section class="panel">
  <div class="panel-head"><div><div class="eyebrow">LAST 14 DAYS</div><h2>Giá trị đơn đặt theo ngày</h2></div></div>
  <?php if ($dailyOrders): ?><div class="bar-list">
   <?php foreach ($dailyOrders as $day): ?>
   <div class="bar-row"><span><?= h($day['report_day']) ?></span><div class="bar-track"><span style="width:<?= max(0, min(100, round(100 * (float)$day['order_value'] / $maxOrderValue))) ?>%"></span></div><strong><?= money($day['order_value']) ?></strong></div>
   <?php endforeach; ?>
  </div><?php else: ?><div class="empty-small">Không có đơn hợp lệ được đặt trong 14 ngày gần đây.</div><?php endif; ?>
 </section>
 <section class="panel">
  <div class="panel-head"><div><div class="eyebrow">POPULAR BOOKS</div><h2>Sách được đặt nhiều</h2></div></div>
  <div class="table-wrap"><table><thead><tr><th>Tên sách</th><th>Số cuốn đặt</th><th>Giá trị đặt</th></tr></thead><tbody>
  <?php foreach ($topOrderedBooks as $book): ?><tr><td><?= h($book['book_title']) ?></td><td><strong><?= (int)$book['copies_ordered'] ?></strong></td><td><?= money($book['order_value']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php if (!$topOrderedBooks): ?><div class="empty-small">Chưa có sách nào được đặt mua.</div><?php endif; ?>
 </section>
</div>

<section class="panel">
 <div class="panel-head"><div><div class="eyebrow">RECENT ORDERS</div><h2>Đơn hàng của khách gần đây</h2></div><a class="btn outline" href="orders.php">Xem tất cả đơn ↗</a></div>
 <?php if ($recentOrders): ?><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Giá trị</th><th>Trạng thái</th></tr></thead><tbody>
  <?php foreach ($recentOrders as $order): ?><tr>
   <td><a class="link" href="order_detail.php?id=<?= (int)$order['id'] ?>">#<?= (int)$order['id'] ?></a></td>
   <td><?= h($order['full_name']) ?></td><td><?= h($order['created_at']) ?></td>
   <td><?= money($order['total_amount']) ?></td>
   <td><span class="badge <?= h(status_class($order['status'])) ?>"><?= h(status_name($order['status'])) ?></span></td>
  </tr><?php endforeach; ?>
 </tbody></table></div><?php else: ?><div class="empty-small">Chưa có đơn hàng.</div><?php endif; ?>
</section>
<div class="notice"><strong>Quy tắc:</strong> “Giá trị đơn đã đặt” loại đơn hủy, nhưng gồm cả đơn chưa giao. “Doanh thu đã giao” chỉ tính đơn ở trạng thái “Đã giao” (mô phỏng COD, chưa đối soát thanh toán).</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
