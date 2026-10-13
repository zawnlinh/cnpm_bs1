<?php
require_once __DIR__.'/app.php';$u=require_permission('orders.view_all');
if(!role_is($u,'admin','manager','employee')){http_response_code(403);require __DIR__.'/403.php';exit;}
$employee=role_is($u,'employee');
$valid=['placed','confirmed','packing','shipped','delivered','cancelled'];
$filter=isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'],$valid,true)?$_GET['status']:'';
$sql='SELECT o.*,u.full_name customer_name,u.email customer_email,staff.full_name staff_name FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN users staff ON staff.id=o.assigned_to';$args=[];$filters=[];
if($employee){$filters[]='o.assigned_to=?';$args[]=$u['id'];}
if($filter!==''){$filters[]='o.status=?';$args[]=$filter;}
if($filters)$sql.=' WHERE '.implode(' AND ',$filters);$sql.=' ORDER BY o.id DESC LIMIT 200';
$st=db()->prepare($sql);$st->execute($args);$orders=$st->fetchAll();
$pageTitle='Đơn hàng cửa hàng';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">ORDER OPERATIONS</div><h1><?= $employee?'Đơn được phân công cho tôi':'Đơn hàng toàn cửa hàng' ?></h1><p><?= $employee?'Chỉ hiển thị đơn Manager đã phân công cho bạn.':'Manager phân công đơn; Employee chỉ được xử lý đơn được giao.' ?></p></div><a class="btn outline" href="dashboard.php">← Tổng quan</a></div>
<div class="tabs"><a class="<?= $filter===''?'active':'' ?>" href="orders.php">Tất cả</a><?php foreach($valid as $status): ?><a class="<?= $filter===$status?'active':'' ?>" href="orders.php?status=<?= h($status) ?>"><?= h(status_name($status)) ?></a><?php endforeach; ?></div>
<section class="panel"><div class="panel-head"><h2>Danh sách đơn <span class="muted small">(<?= count($orders) ?>)</span></h2></div><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th>Phụ trách</th><th>Chi tiết</th></tr></thead><tbody><?php foreach($orders as $o): ?><tr><td><strong>#<?= (int)$o['id'] ?></strong></td><td><?= h($o['customer_name']) ?><div class="small muted"><?= h($o['customer_email']) ?></div></td><td><?= h($o['created_at']) ?></td><td><strong><?= money($o['total_amount']) ?></strong></td><td><span class="badge <?= h(status_class($o['status'])) ?>"><?= h(status_name($o['status'])) ?></span></td><td><?= h($o['staff_name']?:'Chưa phân công') ?></td><td><a class="btn outline btn-small" href="order_detail.php?id=<?= (int)$o['id'] ?>">Xem →</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php if(!$orders): ?><div class="empty-small">Chưa có đơn hàng phù hợp.</div><?php endif; ?></section>
<?php require __DIR__.'/partials/footer.php'; ?>
