<?php
require_once __DIR__.'/app.php';$u=require_login();only_post();
$orderId=value_int($_POST['order_id']??null);$next=post_string('status',20);$note=post_string('note',200);
if(!$orderId||!in_array($next,['confirmed','packing','shipped','delivered','cancelled'],true)){flash('error','Trạng thái hoặc đơn hàng không hợp lệ.');redirect('dashboard.php');}
// Quyền thao tác được đối chiếu lại SAU KHI khóa đơn trong transaction.
$staff=can('orders.process')&&role_is($u,'admin','manager','employee');$customer=role_is($u,'customer')&&can('orders.own');
if(!$staff && !$customer){http_response_code(403);require __DIR__.'/403.php';exit;}
try {
 db()->beginTransaction();
 $st=db()->prepare('SELECT id,user_id,status,assigned_to FROM orders WHERE id=? FOR UPDATE');$st->execute([$orderId]);$order=$st->fetch();
 if(!$order)throw new DomainException('Đơn hàng không tồn tại.');
 $isOwner=(int)$order['user_id']===(int)$u['id'];$customerOnly=!$staff;
 if($staff&&!may_process_order($order,$u))throw new DomainException('Bạn chưa được phân công xử lý đơn này.');
 if($customerOnly && (!$isOwner||!$customer))throw new DomainException('Bạn không có quyền sửa đơn này.');
 if(!allowed_transition($order['status'],$next,$customerOnly))throw new DomainException('Không thể chuyển trạng thái đơn theo quy trình.');
 if($next==='cancelled') {
  $st=db()->prepare('SELECT book_id,quantity FROM order_items WHERE order_id=? AND book_id IS NOT NULL ORDER BY book_id');$st->execute([$orderId]);$items=$st->fetchAll();
  foreach($items as $row){$bookId=(int)$row['book_id'];$qty=(int)$row['quantity'];$stBook=db()->prepare('SELECT id FROM books WHERE id=? FOR UPDATE');$stBook->execute([$bookId]);if(!$stBook->fetch())throw new DomainException('Không thể hoàn kho: đầu sách không tồn tại.');db()->prepare('UPDATE books SET stock=stock+? WHERE id=?')->execute([$qty,$bookId]);log_stock($bookId,$qty,(int)$u['id'],'Hoàn kho: hủy đơn #'.$orderId,$orderId);}
 }
 db()->prepare('UPDATE orders SET status=?,handled_by=CASE WHEN ?=1 THEN ? ELSE handled_by END WHERE id=?')->execute([$next,$staff?1:0,(int)$u['id'],$orderId]);
 order_event($orderId,(int)$u['id'],$order['status'],$next,$note ?: ($next==='cancelled'?'Đơn hàng đã hủy':''));
 db()->commit();flash('success','Đã cập nhật đơn #'.$orderId.' → '.status_name($next));
}catch(DomainException $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
redirect('order_detail.php?id='.$orderId);
