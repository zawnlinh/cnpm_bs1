<?php
require_once __DIR__.'/app.php';$u=require_permission('cart.manage');$uid=(int)$u['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$action=post_string('action',30);$bookId=value_int($_POST['book_id']??null);
 if(!$bookId||!in_array($action,['add','update','remove'],true)){flash('error','Thao tác giỏ hàng không hợp lệ.');redirect('cart.php');}
 $quantity=value_int($_POST['quantity']??null,1,20);
 if($action!=='remove' && !$quantity){flash('error','Số lượng phải từ 1 đến 20.');redirect('cart.php');}
 db()->beginTransaction();
 try {
  db()->prepare('INSERT IGNORE INTO carts(user_id) VALUES(?)')->execute([$uid]);
  $st=db()->prepare('SELECT id FROM carts WHERE user_id=? FOR UPDATE');$st->execute([$uid]);$cartId=(int)$st->fetchColumn();
  if($action==='remove'){
   db()->prepare('DELETE FROM cart_items WHERE cart_id=? AND book_id=?')->execute([$cartId,$bookId]);
  } else {
   $st=db()->prepare('SELECT stock,is_active FROM books WHERE id=?');$st->execute([$bookId]);$b=$st->fetch();
   if(!$b||!(int)$b['is_active']) throw new DomainException('Sách không còn được bán.');
   $st=db()->prepare('SELECT quantity FROM cart_items WHERE cart_id=? AND book_id=?');$st->execute([$cartId,$bookId]);$current=(int)($st->fetchColumn()?:0);
   $new=$action==='add'?$current+$quantity:$quantity;
   if($new>20 || $new>(int)$b['stock']) throw new DomainException('Số lượng vượt giới hạn hoặc tồn kho (tối đa 20 cuốn mỗi loại).');
   db()->prepare('INSERT INTO cart_items(cart_id,book_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)')->execute([$cartId,$bookId,$new]);
  }
  db()->commit();flash('success',$action==='remove'?'Đã xóa sách khỏi giỏ.':'Đã cập nhật giỏ hàng.');
 } catch(DomainException $e){ db()->rollBack();flash('error',$e->getMessage()); }
 catch(Throwable $e){db()->rollBack();throw $e;}
 redirect($action==='add'?'books.php':'cart.php');
}
$st=db()->prepare('SELECT b.id,b.title,b.author,b.price,b.stock,b.is_active,ci.quantity FROM carts c JOIN cart_items ci ON ci.cart_id=c.id JOIN books b ON b.id=ci.book_id WHERE c.user_id=? ORDER BY b.title');$st->execute([$uid]);$items=$st->fetchAll();
$total=0;foreach($items as $item){$total+=(float)$item['price']*(int)$item['quantity'];}
$pageTitle='Giỏ hàng';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">SHOPPING CART</div><h1>Giỏ hàng của tôi</h1><p>Kiểm tra số lượng trước khi xác nhận đặt hàng. Giá và tồn kho sẽ được kiểm tra lại khi đặt.</p></div><a class="btn outline" href="books.php">← Tiếp tục chọn sách</a></div>
<?php if($items): ?><div class="cart-layout"><section class="panel"><div class="table-wrap"><table><thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Tạm tính</th><th></th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><strong><?= h($item['title']) ?></strong><div class="muted small"><?= h($item['author']) ?><?= !(int)$item['is_active']?' · Đã ngừng bán':'' ?></div></td><td><?= money($item['price']) ?></td><td><form class="inline-form" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="book_id" value="<?= (int)$item['id'] ?>"><input class="qty-input" type="number" name="quantity" min="1" max="20" value="<?= (int)$item['quantity'] ?>"><button class="btn outline btn-small">Lưu</button></form></td><td><strong><?= money((float)$item['price']*(int)$item['quantity']) ?></strong></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="book_id" value="<?= (int)$item['id'] ?>"><button class="btn subtle btn-small" title="Xóa sách">✕</button></form></td></tr><?php endforeach; ?></tbody></table></div></section><aside class="panel summary-panel"><div class="eyebrow">ORDER SUMMARY</div><h2>Đơn hàng</h2><div class="summary-line"><span><?= count($items) ?> đầu sách</span><strong><?= money($total) ?></strong></div><div class="summary-line"><span>Thanh toán</span><span>Khi nhận hàng (COD)</span></div><hr><div class="summary-line big"><strong>Tạm tính</strong><strong><?= money($total) ?></strong></div><a class="btn primary wide" href="checkout.php">Tiến hành đặt hàng →</a><p class="muted small">Chưa bao gồm phí vận chuyển thực tế (demo chưa tính phí giao hàng).</p></aside></div>
<?php else: ?><div class="empty"><span class="empty-icon">◧</span><h2>Giỏ hàng đang trống</h2><p>Chọn một vài cuốn sách để bắt đầu.</p><a class="btn primary" href="books.php">Xem sách ngay</a></div><?php endif; ?>
<?php require __DIR__.'/partials/footer.php'; ?>
