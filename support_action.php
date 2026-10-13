<?php
require_once __DIR__.'/app.php';$u=require_login();only_post();
$action=post_string('action',20);
$manager=shop_manager($u)&&can('support.view_all');
$employee=role_is($u,'employee')&&can('support.handle');
$customer=role_is($u,'customer')&&can('support.own');
if(!$manager&&!$employee&&!$customer){http_response_code(403);require __DIR__.'/403.php';exit;}
if($action==='create'){
 if(!$customer){http_response_code(403);require __DIR__.'/403.php';exit;}
 $subject=post_string('subject',151);$message=post_string('message',2001);$orderId=value_int($_POST['order_id']??null,0);
 if(strlen($subject)<4||strlen($subject)>150||strlen($message)<5||strlen($message)>2000||$orderId===null){flash('error','Nội dung hỗ trợ không hợp lệ.');redirect('support.php');}
 try{
  db()->beginTransaction();
  if($orderId>0){$st=db()->prepare('SELECT id FROM orders WHERE id=? AND user_id=?');$st->execute([$orderId,$u['id']]);if(!$st->fetch())throw new DomainException('Bạn không được chọn đơn hàng của người khác.');}
  db()->prepare('INSERT INTO support_tickets(customer_id,order_id,subject) VALUES(?,?,?)')->execute([$u['id'],$orderId?:null,$subject]);$id=(int)db()->lastInsertId();
  db()->prepare('INSERT INTO support_messages(ticket_id,sender_id,message) VALUES(?,?,?)')->execute([$id,$u['id'],$message]);
  db()->commit();flash('success','Đã gửi yêu cầu hỗ trợ #'.$id);redirect('support_detail.php?id='.$id);
 }catch(DomainException $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());redirect('support.php');}
 catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
$ticketId=value_int($_POST['ticket_id']??null);
if(!$ticketId||!in_array($action,['claim','reply','resolve'],true)){flash('error','Thao tác hỗ trợ không hợp lệ.');redirect('support.php');}
try{
 db()->beginTransaction();
 $st=db()->prepare('SELECT id,customer_id,assigned_to,status FROM support_tickets WHERE id=? FOR UPDATE');$st->execute([$ticketId]);$ticket=$st->fetch();
 if(!$ticket)throw new DomainException('Yêu cầu không tồn tại.');
 if(!support_scope_allows($ticket,$u))throw new DomainException('Bạn không có quyền truy cập yêu cầu này.');
 if($action==='claim'){
  if(!$employee||$ticket['status']!=='open'||!empty($ticket['assigned_to']))throw new DomainException('Yêu cầu đã được tiếp nhận hoặc không thể nhận.');
  db()->prepare("UPDATE support_tickets SET assigned_to=?,status='in_progress' WHERE id=?")->execute([$u['id'],$ticketId]);
 }elseif($action==='resolve'){
  if(!$manager&&!($employee && (int)($ticket['assigned_to']??0)===(int)$u['id']))throw new DomainException('Chỉ người phụ trách hoặc quản lý được hoàn tất yêu cầu.');
  if($ticket['status']==='resolved')throw new DomainException('Yêu cầu đã được giải quyết.');
  db()->prepare("UPDATE support_tickets SET status='resolved' WHERE id=?")->execute([$ticketId]);
 }else {
  $message=post_string('message',2001);
  if(!$message||strlen($message)>2000)throw new DomainException('Phản hồi phải có 1 đến 2000 ký tự.');
  if(!$manager&&!$customer&&!($employee&&(int)($ticket['assigned_to']??0)===(int)$u['id']))throw new DomainException('Bạn phải tiếp nhận yêu cầu trước khi phản hồi.');
  if($ticket['status']==='resolved'){
   if(!$customer)throw new DomainException('Yêu cầu đã đóng, khách hàng có thể gửi tin để mở lại.');
   db()->prepare("UPDATE support_tickets SET status='open',assigned_to=NULL WHERE id=?")->execute([$ticketId]);
  }
  db()->prepare('INSERT INTO support_messages(ticket_id,sender_id,message) VALUES(?,?,?)')->execute([$ticketId,$u['id'],$message]);
 }
 db()->commit();flash('success','Đã cập nhật yêu cầu hỗ trợ #'.$ticketId);
}catch(DomainException $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
redirect('support_detail.php?id='.$ticketId);
