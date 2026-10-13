<?php
require_once __DIR__.'/app.php';$u=require_permission('orders.place');$uid=(int)$u['id'];
$mode=(isset($_GET['mode']) && $_GET['mode']==='now')?'now':'cart';$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf(); $action=post_string('action',30);
 if($action==='buy_prepare') {
  $id=value_int($_POST['book_id']??null);
  if(!$id){flash('error','Sách không hợp lệ.');redirect('books.php');}
  $st=db()->prepare('SELECT id FROM books WHERE id=? AND is_active=1 AND stock>0');$st->execute([$id]);
  if(!$st->fetch()){flash('error','Sách này đang hết hoặc ngừng bán.');redirect('books.php');}
  $_SESSION['buy_now']=['book_id'=>$id,'quantity'=>1];redirect('checkout.php?mode=now');
 }
 if($action==='place') {
  $mode=post_string('mode',10)==='now'?'now':'cart';$name=post_string('shipping_name',120);$phone=post_string('shipping_phone',20);$address=post_string('shipping_address',300);$note=post_string('customer_note',300);
  if(strlen($name)<2 || !preg_match('/^\+?[0-9]{9,12}$/D',$phone)||strlen($address)<8){$error='Họ tên, số điện thoại (9–12 số) hoặc địa chỉ nhận hàng không hợp lệ.';}
  else {
   try {
    db()->beginTransaction();$rows=[];
    if($mode==='now'){
     $pending=$_SESSION['buy_now']??null;
     if(!is_array($pending)||!value_int($pending['book_id']??null)||!value_int($pending['quantity']??null,1,20))throw new DomainException('Yêu cầu mua ngay đã hết hạn.');
     $rows=[['book_id'=>(int)$pending['book_id'],'quantity'=>(int)$pending['quantity']]];
    } else {
     // Khoa gio hang de tranh thay doi dong thoi khi checkout.
     $s=db()->prepare('SELECT id FROM carts WHERE user_id=? FOR UPDATE');$s->execute([$uid]);$cartId=$s->fetchColumn();
     if(!$cartId)throw new DomainException('Giỏ hàng đang trống.');
     $s=db()->prepare('SELECT book_id,quantity FROM cart_items WHERE cart_id=? ORDER BY book_id FOR UPDATE');$s->execute([$cartId]);$rows=$s->fetchAll();
    }
    if(!$rows)throw new DomainException('Không có sách để đặt mua.');
    usort($rows,fn($a,$b)=>(int)$a['book_id']<=>(int)$b['book_id']);
    $selected=[];$total=0;
    foreach($rows as $row){
     $bookId=(int)$row['book_id'];$qty=(int)$row['quantity'];
     if($qty<1||$qty>20)throw new DomainException('Số lượng trong giỏ không hợp lệ.');
     $s=db()->prepare('SELECT id,title,price,stock,is_active FROM books WHERE id=? FOR UPDATE');$s->execute([$bookId]);$book=$s->fetch();
     if(!$book||!(int)$book['is_active']||(int)$book['stock']<$qty)throw new DomainException('Một sách trong đơn đã hết, không đủ số lượng hoặc ngừng bán. Hãy kiểm tra lại giỏ.');
     $selected[]=['book'=>$book,'qty'=>$qty];$total+=(float)$book['price']*$qty;
    }
    $s=db()->prepare('INSERT INTO orders(user_id,status,total_amount,shipping_name,shipping_phone,shipping_address,customer_note,payment_method) VALUES(?,\'placed\',?,?,?,?,?,\'cod\')');
    $s->execute([$uid,number_format($total,2,'.',''),$name,$phone,$address,$note]);$orderId=(int)db()->lastInsertId();
    $itemStmt=db()->prepare('INSERT INTO order_items(order_id,book_id,book_title,unit_price,quantity) VALUES(?,?,?,?,?)');
    $stockStmt=db()->prepare('UPDATE books SET stock=stock-? WHERE id=?');
    foreach($selected as $s){$b=$s['book'];$qty=$s['qty'];$itemStmt->execute([$orderId,$b['id'],$b['title'],$b['price'],$qty]);$stockStmt->execute([$qty,$b['id']]);log_stock((int)$b['id'],-$qty,$uid,'Khách đặt đơn #'.$orderId,$orderId);}
    order_event($orderId,$uid,'','placed','Khách đặt hàng (COD)');
    if($mode==='cart')db()->prepare('DELETE FROM cart_items WHERE cart_id=?')->execute([$cartId]);
    db()->commit();if($mode==='now')unset($_SESSION['buy_now']);flash('success','Đã ghi nhận đơn #'.$orderId.'. Nhân viên sẽ xử lý đơn của bạn.');redirect('order_detail.php?id='.$orderId);
   } catch(DomainException $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
   catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
  }
 }
}
$items=[];
if($mode==='now' && isset($_SESSION['buy_now']['book_id'])){
 $s=db()->prepare('SELECT id book_id,title,author,price,stock,is_active FROM books WHERE id=?');$s->execute([$_SESSION['buy_now']['book_id']]);$one=$s->fetch();if($one){$one['quantity']=(int)$_SESSION['buy_now']['quantity'];$items=[$one];}
}elseif($mode==='cart'){
 $s=db()->prepare('SELECT b.id book_id,b.title,b.author,b.price,b.stock,b.is_active,ci.quantity FROM carts c JOIN cart_items ci ON ci.cart_id=c.id JOIN books b ON b.id=ci.book_id WHERE c.user_id=? ORDER BY b.id');$s->execute([$uid]);$items=$s->fetchAll();
}
$total=0;foreach($items as $item)$total+=(float)$item['price']*(int)$item['quantity'];
$pageTitle='Xác nhận đặt hàng';require __DIR__.'/partials/header.php';?>
<div class="page-head"><div><div class="eyebrow">SECURE CHECKOUT · DEMO</div><h1>Xác nhận đơn hàng</h1><p>Thanh toán COD mô phỏng. Không kết nối thanh toán online, không thu tiền thật.</p></div></div>
<?php if($error):?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<?php if($items):?><div class="cart-layout"><section class="panel"><div class="panel-head"><h2>Thông tin nhận hàng</h2></div><form id="checkout-form" method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="place"><input type="hidden" name="mode" value="<?= h($mode) ?>"><label>Họ tên người nhận *<input name="shipping_name" required maxlength="120" value="<?= old('shipping_name') ?: h($u['full_name']) ?>"></label><label>Số điện thoại *<input name="shipping_phone" required maxlength="20" value="<?= old('shipping_phone') ?>" placeholder="09xxxxxxxx"></label><label>Địa chỉ nhận hàng *<textarea name="shipping_address" rows="3" maxlength="300" required placeholder="Số nhà, đường, phường/xã, tỉnh/thành..."><?= old('shipping_address') ?></textarea></label><label>Ghi chú (không bắt buộc)<textarea name="customer_note" rows="2" maxlength="300"><?= old('customer_note') ?></textarea></label><div class="notice">Phương thức: <strong>Thanh toán khi nhận hàng (COD) — MÔ PHỎNG</strong></div><button class="btn primary" type="submit">Xác nhận đặt hàng →</button></form></section><aside class="panel summary-panel"><div class="eyebrow">YOUR ORDER</div><h2>Danh sách sản phẩm</h2><?php foreach($items as $i): ?><div class="summary-item"><div><strong><?= h($i['title']) ?></strong><small class="muted">x<?= (int)$i['quantity'] ?></small></div><strong><?= money((float)$i['price']*(int)$i['quantity']) ?></strong></div><?php endforeach; ?><hr><div class="summary-line big"><strong>Tổng tạm tính</strong><strong><?= money($total) ?></strong></div><p class="muted small">Giá và số lượng tồn kho được kiểm tra lại khi nhấn xác nhận.</p></aside></div>
<?php else: ?><div class="empty"><h2>Chưa có sản phẩm để thanh toán</h2><a class="btn primary" href="books.php">Chọn sách</a></div><?php endif; ?>
<?php require __DIR__.'/partials/footer.php'; ?>
