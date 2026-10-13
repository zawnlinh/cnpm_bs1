<?php
require_once __DIR__.'/app.php'; $u=require_login();
$pageTitle='Tổng quan';
$role=$u['role_code'];
$metrics=[]; $recent=[];
if (can('reports.view')) {
 $metrics=[
  ['Tổng đơn hàng',db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'Đơn đã ghi nhận','blue'],
  ['Đang chờ xử lý',db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('placed','confirmed','packing')")->fetchColumn(),'Cần theo dõi','amber'],
  ['Giá trị đơn đã đặt',money(db()->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status<>'cancelled'")->fetchColumn()),'Bao gồm đơn chưa giao','blue'],
  ['Doanh thu đã giao',money(db()->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='delivered'")->fetchColumn()),'Không gồm đơn hủy/chưa giao','green'],
  ['Khách hàng',db()->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code='customer'")->fetchColumn(),'Tài khoản Customer','purple'],
 ];
 $recent=db()->query('SELECT o.id,o.status,o.total_amount,o.created_at,u.full_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 6')->fetchAll();
 } elseif (role_is($u,'employee') && can('orders.view_all')) {
 $st=db()->prepare("SELECT COUNT(*) FROM orders WHERE assigned_to=? AND status='placed'");$st->execute([$u['id']]);$placed=$st->fetchColumn();
 $st=db()->prepare("SELECT COUNT(*) FROM orders WHERE assigned_to=? AND status='packing'");$st->execute([$u['id']]);$packing=$st->fetchColumn();
 $st=db()->prepare("SELECT COUNT(*) FROM orders WHERE assigned_to=? AND status='shipped'");$st->execute([$u['id']]);$shipped=$st->fetchColumn();
 $st=db()->prepare("SELECT COUNT(*) FROM support_tickets WHERE assigned_to=? AND status='in_progress'");$st->execute([$u['id']]);$ticketCount=$st->fetchColumn();
 $metrics=[['Chờ tôi xác nhận',$placed,'Đã giao cho tôi','amber'],['Tôi đang chuẩn bị',$packing,'Cần đóng gói','blue'],['Tôi đang giao',$shipped,'Chờ hoàn tất','purple'],['Hỗ trợ của tôi',$ticketCount,'Yêu cầu đang xử lý','green']];
 $st=db()->prepare('SELECT o.id,o.status,o.total_amount,o.created_at,u.full_name FROM orders o JOIN users u ON u.id=o.user_id WHERE o.assigned_to=? ORDER BY o.id DESC LIMIT 6');$st->execute([$u['id']]);$recent=$st->fetchAll();
} elseif (can('orders.view_all') && shop_manager($u)) {
 $metrics=[
  ['Chờ phân công',db()->query("SELECT COUNT(*) FROM orders WHERE assigned_to IS NULL AND status IN ('placed','confirmed','packing')")->fetchColumn(),'Manager cần chỉ định','amber'],
  ['Tổng đơn hàng',db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'Toàn cửa hàng','blue'],
  ['Doanh thu đã giao',money(db()->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='delivered'")->fetchColumn()),'Mô phỏng COD','green'],
  ['Hỗ trợ mới',db()->query("SELECT COUNT(*) FROM support_tickets WHERE status='open'")->fetchColumn(),'Yêu cầu khách hàng','purple'],
 ];
 $recent=db()->query('SELECT o.id,o.status,o.total_amount,o.created_at,u.full_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 6')->fetchAll();
} elseif (can('orders.own')) {
 $st=db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?'); $st->execute([$u['id']]); $count=$st->fetchColumn();
 $st=db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND status IN ('placed','confirmed','packing','shipped')"); $st->execute([$u['id']]); $pending=$st->fetchColumn();
 $st=db()->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND status='delivered'"); $st->execute([$u['id']]); $spent=$st->fetchColumn();
 $st=db()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM carts c JOIN cart_items ci ON ci.cart_id=c.id WHERE c.user_id=?'); $st->execute([$u['id']]); $cartCount=$st->fetchColumn();
 $metrics=[['Đơn đã đặt',$count,'Tổng số đơn','blue'],['Đơn đang xử lý',$pending,'Theo dõi đơn hàng','amber'],['Đã mua',money($spent),'Đơn đã giao','green'],['Trong giỏ',$cartCount,'Sản phẩm đã chọn','purple']];
 $st=db()->prepare('SELECT id,status,total_amount,created_at FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 5'); $st->execute([$u['id']]); $recent=$st->fetchAll();
} else {
 $metrics=[['Sách đang bán',db()->query('SELECT COUNT(*) FROM books WHERE is_active=1')->fetchColumn(),'Danh mục hiện tại','blue'],['Tổng nhân sự',db()->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code IN ('manager','employee')")->fetchColumn(),'Manager + Employee','purple'],['Tài khoản đang hoạt động',db()->query('SELECT COUNT(*) FROM users WHERE is_active=1')->fetchColumn(),'Toàn hệ thống','green'],['Vai trò',db()->query('SELECT COUNT(*) FROM roles')->fetchColumn(),'RBAC','amber']];
}
require __DIR__.'/partials/header.php';
?>
<div class="page-head"><div><div class="eyebrow"><?= h(strtoupper($role)) ?> WORKSPACE</div><h1>Xin chào, <?= h($u['full_name']) ?> <span class="wave">✳</span></h1><p><?= h(['admin'=>'Quản trị tài khoản, phân quyền và cấu hình cửa hàng.','manager'=>'Phân công đơn cho nhân viên, theo dõi khách hàng và hiệu quả bán hàng.','employee'=>'Nhận đơn do Manager phân công, xử lý giao hàng và tiếp nhận hỗ trợ khách hàng.','customer'=>'Khám phá sách, đặt mua và theo dõi hành trình đơn hàng.'][$role] ?? 'Trang tổng quan theo quyền truy cập.') ?></p></div><?php if(can('catalog.view')): ?><a class="btn outline" href="books.php">Khám phá sách ↗</a><?php endif; ?></div>
<div class="metric-grid"><?php foreach ($metrics as $metric): ?><div class="metric-card"><div class="metric-accent <?= h($metric[3]) ?>"></div><span class="metric-label"><?= h($metric[0]) ?></span><strong><?= h($metric[1]) ?></strong><small><?= h($metric[2]) ?></small></div><?php endforeach; ?></div>
<div class="two-col"><section class="panel"><div class="panel-head"><div><div class="eyebrow">QUICK ACTIONS</div><h2>Thao tác nhanh</h2></div></div><div class="actions">
<?php if (can('users.manage')): ?><a href="users.php" class="action"><span>♙</span><strong>Quản lý tài khoản</strong><small>Tạo, khóa và gán vai trò</small></a><?php endif; ?>
<?php if (can('roles.manage')): ?><a href="roles.php" class="action"><span>⊞</span><strong>Ma trận quyền</strong><small>Cấp quyền từng chức năng</small></a><?php endif; ?>
<?php if (can('settings.manage')): ?><a href="settings.php" class="action"><span>⚙</span><strong>Cài đặt cửa hàng</strong><small>Thông tin và tùy chọn hệ thống</small></a><?php endif; ?>
<?php if (can('support.own') || can('support.handle')): ?><a href="support.php" class="action"><span>☏</span><strong>Hỗ trợ khách hàng</strong><small>Gửi và xử lý yêu cầu</small></a><?php endif; ?>
<?php if (can('reports.view')): ?><a href="reports.php" class="action"><span>▨</span><strong>Báo cáo kinh doanh</strong><small>Doanh thu và sách bán chạy</small></a><?php endif; ?>
<?php if (can('customers.history')): ?><a href="customers.php" class="action"><span>♧</span><strong>Lịch sử khách mua</strong><small>Tra cứu theo từng Customer</small></a><?php endif; ?>
<?php if (can('orders.view_all')&&role_is($u,'admin','manager','employee')): ?><a href="orders.php" class="action"><span>▣</span><strong><?= role_is($u,'employee')?'Đơn được phân công':'Quản lý & phân công đơn' ?></strong><small><?= role_is($u,'employee')?'Xử lý đơn của tôi':'Phân công và theo dõi tất cả đơn' ?></small></a><?php endif; ?>
<?php if (can('inventory.adjust')): ?><a href="stock.php" class="action"><span>▤</span><strong>Quản lý tồn kho</strong><small>Nhập / điều chỉnh số lượng</small></a><?php endif; ?>
<?php if (can('cart.manage')): ?><a href="cart.php" class="action"><span>◧</span><strong>Giỏ hàng của tôi</strong><small>Kiểm tra và đặt mua</small></a><?php endif; ?>
<?php if (can('orders.own')): ?><a href="my_orders.php" class="action"><span>▥</span><strong>Lịch sử đặt hàng</strong><small>Xem trạng thái đơn của mình</small></a><?php endif; ?>
<?php if (can('catalog.manage')): ?><a href="categories.php" class="action"><span>◩</span><strong>Thể loại sách</strong><small>Thêm và quản lý thể loại</small></a><a href="book_form.php" class="action"><span>＋</span><strong>Thêm sách mới</strong><small>Giá, tác giả và thông tin</small></a><?php endif; ?>
</div></section><section class="panel"><div class="panel-head"><div><div class="eyebrow">RECENT ACTIVITY</div><h2><?= can('orders.view_all') || can('orders.own') ? 'Đơn hàng gần đây' : 'Quy trình hệ thống' ?></h2></div></div>
<?php if($recent): ?><div class="table-wrap"><table><thead><tr><th>Đơn</th><?php if (can('orders.view_all')): ?><th>Khách hàng</th><?php endif; ?><th>Giá trị</th><th>Trạng thái</th></tr></thead><tbody><?php foreach($recent as $o): ?><tr><td><a class="link" href="order_detail.php?id=<?= (int)$o['id'] ?>">#<?= str_pad((string)$o['id'],4,'0',STR_PAD_LEFT) ?></a></td><?php if(can('orders.view_all')): ?><td><?= h($o['full_name']) ?></td><?php endif; ?><td><?= money($o['total_amount']) ?></td><td><span class="badge <?= h(status_class($o['status'])) ?>"><?= h(status_name($o['status'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
<?php else: ?><div class="empty-small"><span class="muted">Chưa có đơn hàng nào. Hãy thử đăng nhập bằng Customer và đặt mua sách.</span></div><?php endif; ?>
<?php if(!can('orders.view_all')&&!can('orders.own')): ?><ol class="guide-list"><li>Admin tạo tài khoản và điều chỉnh quyền.</li><li>Customer thêm sách vào giỏ rồi đặt đơn.</li><li>Employee xác nhận, đóng gói và giao.</li><li>Manager xem kết quả và lịch sử Customer.</li></ol><?php endif; ?></section></div>
<?php require __DIR__.'/partials/footer.php'; ?>
