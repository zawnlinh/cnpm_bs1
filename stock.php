<?php
require_once __DIR__.'/app.php';$u=require_permission('inventory.adjust');
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf(); $bookId=value_int($_POST['book_id']??null);$deltaText=$_POST['delta']??null;$reason=post_string('reason',100);
 $delta=(is_string($deltaText)&&preg_match('/^-?\d{1,6}$/D',$deltaText))?(int)$deltaText:0;
 if(!$bookId||$delta===0||abs($delta)>10000||strlen($reason)<3){flash('error','Cần chọn sách, nhập số thay đổi khác 0 (từ -10000 đến 10000) và ghi lý do.');redirect('stock.php');}
 db()->beginTransaction();try{
  $st=db()->prepare('SELECT stock FROM books WHERE id=? FOR UPDATE');$st->execute([$bookId]);$b=$st->fetch();
  if(!$b||$b['stock']+$delta<0||$b['stock']+$delta>1000000) throw new DomainException('Điều chỉnh vượt số lượng tồn kho hợp lệ.');
  db()->prepare('UPDATE books SET stock=stock+? WHERE id=?')->execute([$delta,$bookId]);log_stock($bookId,$delta,(int)$u['id'],$reason);db()->commit();flash('success','Đã cập nhật số lượng và lưu lịch sử kho.');
 }catch(DomainException $e){db()->rollBack();flash('error',$e->getMessage());}catch(Throwable $e){db()->rollBack();throw $e;}
 redirect('stock.php');
}
$books=db()->query('SELECT id,title,stock,is_active FROM books ORDER BY stock ASC,title ASC')->fetchAll();
$logs=db()->query('SELECT l.*,b.title,u.full_name FROM stock_logs l JOIN books b ON b.id=l.book_id LEFT JOIN users u ON u.id=l.actor_id ORDER BY l.id DESC LIMIT 25')->fetchAll();
$pageTitle='Tồn kho';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">STOCK CONTROL</div><h1>Quản lý tồn kho</h1><p>Mọi lần nhập/xuất thủ công đều có lịch sử. Đơn hàng tự trừ kho khi đặt mua.</p></div></div>
<div class="two-col"><section class="panel"><div class="panel-head"><h2>Điều chỉnh số lượng</h2></div><form method="post" class="stack"><?= csrf_field() ?><label>Đầu sách<select name="book_id" required><option value="">Chọn sách...</option><?php foreach($books as $b): ?><option value="<?= (int)$b['id'] ?>"><?= h($b['title']) ?> (<?= (int)$b['stock'] ?> cuốn)</option><?php endforeach; ?></select></label><label>Số lượng thay đổi (+ nhập, - xuất)<input name="delta" type="number" min="-10000" max="10000" placeholder="Ví dụ: +5 hoặc -2" required></label><label>Lý do<input name="reason" maxlength="100" placeholder="Ví dụ: Nhập thêm từ nhà cung cấp" required></label><button class="btn primary">Lưu biến động kho</button></form></section>
<section class="panel"><div class="panel-head"><h2>Số lượng hiện tại</h2></div><div class="table-wrap"><table><thead><tr><th>Sách</th><th>Tồn</th></tr></thead><tbody><?php foreach($books as $b): ?><tr><td><?= h($b['title']) ?></td><td><span class="badge <?= (int)$b['stock']<=5?'red':'green' ?>"><?= (int)$b['stock'] ?></span></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<section class="panel"><div class="panel-head"><div><div class="eyebrow">AUDIT LOG</div><h2>Lịch sử nhập / xuất kho</h2></div></div><div class="table-wrap"><table><thead><tr><th>Thời gian</th><th>Sách</th><th>Thay đổi</th><th>Lý do</th><th>Thực hiện</th></tr></thead><tbody><?php foreach($logs as $l): ?><tr><td><?= h($l['created_at']) ?></td><td><?= h($l['title']) ?></td><td class="<?= (int)$l['delta']>0?'text-green':'text-red' ?>"><?= (int)$l['delta']>0?'+':'' ?><?= (int)$l['delta'] ?></td><td><?= h($l['reason']) ?></td><td><?= h($l['full_name']?:'Hệ thống') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__.'/partials/footer.php'; ?>
