<?php
$pageTitle = $pageTitle ?? 'Book Shop';
$u = current_user();
$nav = [];
if ($u) {
    $nav[] = ['dashboard.php','Tổng quan','▦'];
    if (can('catalog.view')) $nav[] = ['books.php','Kho sách','▤'];
    if (can('catalog.manage')) $nav[] = ['categories.php','Thể loại','◩'];
    if (can('cart.manage')) $nav[] = ['cart.php','Giỏ hàng','◧'];
    if (can('orders.own')) $nav[] = ['my_orders.php','Đơn của tôi','▥'];
    if (can('orders.view_all')) $nav[] = ['orders.php','Xử lý đơn','▣'];
    if (can('inventory.adjust')) $nav[] = ['stock.php','Tồn kho','▥'];
    if (can('reports.view')) $nav[] = ['reports.php','Thống kê','▨'];
    if (can('customers.history')) $nav[] = ['customers.php','Khách hàng','♙'];
    if (can('users.manage')) $nav[] = ['users.php','Tài khoản','♧'];
    if (can('roles.manage')) $nav[] = ['roles.php','Ma trận quyền','⊞'];
    if (can('settings.manage')) $nav[] = ['settings.php','Cài đặt cửa hàng','⚙'];
    if (can('support.own') || can('support.handle') || can('support.view_all')) $nav[] = ['support.php','Hỗ trợ khách hàng','☏'];
}
$current = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?> · <?= h(setting('store_name','Book Shop Mini')) ?></title><link rel="stylesheet" href="assets/style.css"></head><body>
<?php if ($u): ?>
<div class="shell"><aside class="sidebar">
 <a class="brand" href="dashboard.php"><span class="brand-icon">B<span>.</span></span><span><strong><?= h(setting('store_name','Book Shop Mini')) ?></strong><small>MINI MANAGEMENT</small></span></a>
 <div class="sidebar-caption">HỆ THỐNG</div><nav class="nav">
 <?php foreach ($nav as $item): ?><a class="nav-link <?= $current === $item[0] ? 'active' : '' ?>" href="<?= h($item[0]) ?>"><span class="nav-icon"><?= h($item[2]) ?></span><?= h($item[1]) ?></a><?php endforeach; ?>
 </nav><div class="sidebar-bottom"><div class="user-avatar"><?= h(strtoupper(substr($u['full_name'],0,1))) ?></div><div class="user-mini"><strong><?= h($u['full_name']) ?></strong><small><?= h($u['role_name']) ?></small></div>
 <form action="logout.php" method="post" class="logout-form"><?= csrf_field() ?><button title="Đăng xuất" class="logout" type="submit">↪</button></form></div>
 </aside><div class="main-area"><header class="topbar"><div><span class="top-breadcrumb">BOOK SHOP <span>/</span> <?= h($pageTitle) ?></span></div><div class="top-right"><span class="dot-live"></span> Hệ thống nội bộ <span class="pill-role"><?= h(strtoupper($u['role_code'])) ?></span></div></header><main class="content">
<?php else: ?><div class="auth-layout"><div class="auth-wrap">
<?php endif; ?>
<?php if (!empty($_SESSION['flash'])): $f=$_SESSION['flash']; unset($_SESSION['flash']); ?><div class="alert <?= h($f['type']) ?>"><?= h($f['message']) ?></div><?php endif; ?>
