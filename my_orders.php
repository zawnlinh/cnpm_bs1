<?php
require_once __DIR__.'/app.php';$u=require_permission('orders.own');
$st=db()->prepare('SELECT o.*,(SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) product_lines FROM orders o WHERE o.user_id=? ORDER BY o.id DESC LIMIT 150');$st->execute([$u['id']]);$orders=$st->fetchAll();
$pageTitle='Đơn của tôi';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">ORDER HISTORY</div><h1>Đơn hàng của tôi</h1><p>Chỉ xem được đơn đặt bằng tài khoản của bạn. Mọi tiến độ do nhân viên xử lý sẽ được cập nhật tại đây.</p></div><a class="btn outline" href="books.php">Tiếp tục mua sắm</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Số đầu sách</th><th>Giá trị</th><th>Trạng thái</th><th></th></tr></thead><tbody><?php foreach($orders as $o): ?><tr><td><strong>#<?= (int)$o['id'] ?></strong></td><td><?= h($o['created_at']) ?></td><td><?= (int)$o['product_lines'] ?></td><td><?= money($o['total_amount']) ?></td><td><span class="badge <?= h(status_class($o['status'])) ?>"><?= h(status_name($o['status'])) ?></span></td><td><a href="order_detail.php?id=<?= (int)$o['id'] ?>" class="btn outline btn-small">Theo dõi →</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php if(!$orders): ?><div class="empty"><span class="empty-icon">▤</span><h2>Chưa có đơn hàng nào</h2><a class="btn primary" href="books.php">Mua sách</a></div><?php endif; ?></section>
<?php require __DIR__.'/partials/footer.php'; ?>
