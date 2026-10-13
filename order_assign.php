<?php
require_once __DIR__.'/app.php';$u=require_permission('orders.assign');only_post();
if(!shop_manager($u)){http_response_code(403);require __DIR__.'/403.php';exit;}
$orderId=value_int($_POST['order_id']??null);$employeeId=value_int($_POST['employee_id']??null,0);
if(!$orderId||$employeeId===null){flash('error','Thông tin phân công không hợp lệ.');redirect('orders.php');}
try {
 db()->beginTransaction();
 $st=db()->prepare('SELECT id,status,assigned_to FROM orders WHERE id=? FOR UPDATE');$st->execute([$orderId]);$order=$st->fetch();
 if(!$order)throw new DomainException('Không tìm thấy đơn.');
 if(!in_array($order['status'],['placed','confirmed','packing','shipped'],true))throw new DomainException('Đơn đã giao hoặc hủy không thể phân công lại.');
 if($employeeId>0){
  $st=db()->prepare("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1 AND r.code='employee'");$st->execute([$employeeId]);
  if(!$st->fetch())throw new DomainException('Chỉ được phân công cho Employee đang hoạt động.');
 }
 $oldId=$order['assigned_to']===null?null:(int)$order['assigned_to'];$newId=$employeeId?:null;
 if($oldId===$newId)throw new DomainException('Phân công không thay đổi.');
 db()->prepare('UPDATE orders SET assigned_to=?, assigned_at=NOW() WHERE id=?')->execute([$newId,$orderId]);
 db()->prepare('INSERT INTO order_assignments(order_id,actor_id,old_employee_id,new_employee_id) VALUES(?,?,?,?)')->execute([$orderId,$u['id'],$oldId,$newId]);
 db()->commit();flash('success',$newId?'Đã phân công đơn #'.$orderId.' cho nhân viên.':'Đã bỏ phân công đơn #'.$orderId.'.');
}catch(DomainException $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
redirect('order_detail.php?id='.$orderId);
