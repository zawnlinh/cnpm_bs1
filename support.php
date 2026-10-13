<?php
require_once __DIR__.'/app.php';$u=require_login();
$manager=shop_manager($u)&&can('support.view_all');
$employee=role_is($u,'employee')&&can('support.handle');
$customer=role_is($u,'customer')&&can('support.own');
if(!$manager&&!$employee&&!$customer){http_response_code(403);require __DIR__.'/403.php';exit;}
if($manager){$tickets=db()->query('SELECT t.*,cu.full_name customer_name,eu.full_name employee_name FROM support_tickets t JOIN users cu ON cu.id=t.customer_id LEFT JOIN users eu ON eu.id=t.assigned_to ORDER BY t.id DESC LIMIT 200')->fetchAll();}
elseif($employee){$st=db()->prepare("SELECT t.*,cu.full_name customer_name,eu.full_name employee_name FROM support_tickets t JOIN users cu ON cu.id=t.customer_id LEFT JOIN users eu ON eu.id=t.assigned_to WHERE t.assigned_to=? OR (t.status='open' AND t.assigned_to IS NULL) ORDER BY t.id DESC LIMIT 200");$st->execute([$u['id']]);$tickets=$st->fetchAll();}
else {$st=db()->prepare('SELECT t.*,cu.full_name customer_name,eu.full_name employee_name FROM support_tickets t JOIN users cu ON cu.id=t.customer_id LEFT JOIN users eu ON eu.id=t.assigned_to WHERE t.customer_id=? ORDER BY t.id DESC LIMIT 200');$st->execute([$u['id']]);$tickets=$st->fetchAll();}
$orders=[];if($customer){$st=db()->prepare('SELECT id FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 50');$st->execute([$u['id']]);$orders=$st->fetchAll();}
$pageTitle='Hỗ trợ khách hàng';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">CUSTOMER SUPPORT</div><h1>Yêu cầu hỗ trợ</h1><p><?= $customer?'Gửi câu hỏi tới cửa hàng và theo dõi phản hồi.':($employee?'Tiếp nhận yêu cầu chưa được nhận hoặc xem các yêu cầu được giao cho mình.':'Theo dõi toàn bộ yêu cầu và tiến độ hỗ trợ.') ?></p></div></div>
<?php if($customer): ?>
<section class="panel form-panel"><div class="panel-head"><h2>Tạo yêu cầu mới</h2></div><form method="post" action="support_action.php" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<label>Tiêu đề<input name="subject" required minlength="4" maxlength="150" placeholder="Ví dụ: Hỏi về đơn hàng"></label>
<label>Liên quan tới đơn hàng (tùy chọn)<select name="order_id"><option value="0">Không chọn đơn</option><?php foreach($orders as $o):?><option value="<?= (int)$o['id'] ?>">Đơn #<?= (int)$o['id'] ?></option><?php endforeach;?></select></label>
<label>Nội dung<textarea name="message" required minlength="5" maxlength="2000" rows="4" placeholder="Mô tả vấn đề bạn gặp phải"></textarea></label><button class="btn primary">Gửi yêu cầu</button></form></section>
<?php endif; ?>
<section class="panel"><div class="panel-head"><h2><?= $employee?'Hàng chờ & việc của tôi':($manager?'Toàn bộ yêu cầu':'Yêu cầu của tôi') ?></h2><span class="muted small"><?= count($tickets) ?> yêu cầu gần nhất</span></div><div class="table-wrap"><table><thead><tr><th>Mã</th><th>Tiêu đề</th><?php if(!$customer):?><th>Khách hàng</th><?php endif; ?><th>Trạng thái</th><th>Phụ trách</th><th>Chi tiết</th></tr></thead><tbody><?php foreach($tickets as $t):?><tr><td>#<?= (int)$t['id'] ?></td><td><?= h($t['subject']) ?><div class="small muted"><?= h($t['created_at']) ?></div></td><?php if(!$customer):?><td><?= h($t['customer_name']) ?></td><?php endif; ?><td><span class="badge <?= $t['status']==='resolved'?'green':($t['status']==='open'?'amber':'blue') ?>"><?= h(ticket_status_name($t['status'])) ?></span></td><td><?= h($t['employee_name']?:'Chưa nhận') ?></td><td><a class="btn outline btn-small" href="support_detail.php?id=<?= (int)$t['id'] ?>">Xem →</a></td></tr><?php endforeach;?></tbody></table></div><?php if(!$tickets):?><div class="empty-small">Chưa có yêu cầu nào.</div><?php endif; ?></section>
<div class="notice">Hotline: <?= h(setting('store_phone','Chưa cập nhật')) ?> · Email: <?= h(setting('store_email','Chưa cập nhật')) ?>. Đây là hệ thống nhắn tin trong ứng dụng, chưa gửi email tự động.</div>
<?php require __DIR__.'/partials/footer.php'; ?>
