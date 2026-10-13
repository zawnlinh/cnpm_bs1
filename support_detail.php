<?php
require_once __DIR__.'/app.php';$u=require_login();$ticketId=value_int($_GET['id']??null);
if(!$ticketId){http_response_code(404);exit('Không tìm thấy yêu cầu.');}
$st=db()->prepare('SELECT t.*,cu.full_name customer_name,eu.full_name employee_name FROM support_tickets t JOIN users cu ON cu.id=t.customer_id LEFT JOIN users eu ON eu.id=t.assigned_to WHERE t.id=?');$st->execute([$ticketId]);$ticket=$st->fetch();
if(!$ticket){http_response_code(404);exit('Không tìm thấy yêu cầu.');}
if(!support_scope_allows($ticket,$u)){http_response_code(403);require __DIR__.'/403.php';exit;}
$manager=shop_manager($u)&&can('support.view_all');$employee=role_is($u,'employee')&&can('support.handle');$customer=role_is($u,'customer')&&can('support.own');
$canReply= $customer || ($ticket['status']!=='resolved' && ($manager||($employee&&(int)($ticket['assigned_to']??0)===(int)$u['id'])));
$st=db()->prepare('SELECT m.*,u.full_name sender_name FROM support_messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.ticket_id=? ORDER BY m.id');$st->execute([$ticketId]);$messages=$st->fetchAll();
$pageTitle='Chi tiết hỗ trợ';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">SUPPORT #<?= (int)$ticketId ?></div><h1><?= h($ticket['subject']) ?></h1><p>Khách: <?= h($ticket['customer_name']) ?> · <?= h($ticket['created_at']) ?> · <?= h(ticket_status_name($ticket['status'])) ?></p></div><a class="btn outline" href="support.php">← Danh sách hỗ trợ</a></div>
<div class="two-col"><section class="panel"><div class="panel-head"><h2>Hội thoại hỗ trợ</h2></div><div class="timeline"><?php foreach($messages as $message):?><div class="timeline-item"><span class="timeline-dot"></span><div><strong><?= h($message['sender_name']?:'Tài khoản đã xóa') ?></strong><div class="small muted"><?= h($message['created_at']) ?></div><div class="support-message"><?= nl2br(h($message['message'])) ?></div></div></div><?php endforeach;?></div>
<?php if($canReply):?><form method="post" action="support_action.php" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="ticket_id" value="<?= (int)$ticketId ?>"><label>Phản hồi<textarea name="message" rows="4" minlength="1" maxlength="2000" required></textarea></label><button class="btn primary">Gửi phản hồi</button></form><?php endif; ?></section>
<section class="panel"><div class="panel-head"><h2>Trạng thái & phụ trách</h2></div><dl class="detail-list"><div><dt>Trạng thái</dt><dd><?= h(ticket_status_name($ticket['status'])) ?></dd></div><div><dt>Nhân viên</dt><dd><?= h($ticket['employee_name']?:'Chưa tiếp nhận') ?></dd></div><div><dt>Đơn liên quan</dt><dd><?= $ticket['order_id']?'#'.(int)$ticket['order_id']:'Không có' ?></dd></div></dl>
<?php if($employee&&$ticket['status']==='open'&&empty($ticket['assigned_to'])): ?><form method="post" action="support_action.php"><?= csrf_field() ?><input type="hidden" name="action" value="claim"><input type="hidden" name="ticket_id" value="<?= (int)$ticketId ?>"><button class="btn primary wide">Tiếp nhận yêu cầu</button></form><?php endif; ?>
<?php if(($manager||($employee&&(int)($ticket['assigned_to']??0)===(int)$u['id'])) && $ticket['status']!=='resolved'): ?><form method="post" action="support_action.php" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="resolve"><input type="hidden" name="ticket_id" value="<?= (int)$ticketId ?>"><button class="btn outline wide">Đánh dấu đã giải quyết</button></form><?php endif; ?>
<?php if($customer&&$ticket['status']==='resolved'):?><div class="notice">Bạn có thể phản hồi tiếp. Hệ thống sẽ mở lại yêu cầu để nhân viên tiếp nhận.</div><?php endif; ?>
</section></div>
<?php require __DIR__.'/partials/footer.php'; ?>
