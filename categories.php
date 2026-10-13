<?php
require_once __DIR__.'/app.php';require_permission('catalog.manage');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$action=post_string('action',20);$name=post_string('name',100);$id=value_int($_POST['category_id']??null);
 try{
  if($action==='create'){
   if(strlen($name)<2)throw new DomainException('Tên thể loại phải từ 2 ký tự.');
   db()->prepare('INSERT INTO categories(name) VALUES(?)')->execute([$name]);flash('success','Đã thêm thể loại sách.');redirect('categories.php');
  }elseif($action==='rename'){
   if(!$id||strlen($name)<2)throw new DomainException('Thể loại hoặc tên mới không hợp lệ.');
   $st=db()->prepare('UPDATE categories SET name=? WHERE id=?');$st->execute([$name,$id]);flash('success','Đã đổi tên thể loại.');redirect('categories.php');
  }elseif($action==='delete'){
   if(!$id)throw new DomainException('Thể loại không hợp lệ.');
   $st=db()->prepare('SELECT COUNT(*) FROM books WHERE category_id=?');$st->execute([$id]);if((int)$st->fetchColumn()>0)throw new DomainException('Chỉ xóa được thể loại chưa có sách.');
   db()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);flash('success','Đã xóa thể loại trống.');redirect('categories.php');
  } else throw new DomainException('Thao tác không hợp lệ.');
 }catch(DomainException $e){$error=$e->getMessage();}catch(PDOException $e){if($e->getCode()==='23000')$error='Tên thể loại đã tồn tại hoặc có dữ liệu liên quan.';else throw $e;}
}
$categories=db()->query('SELECT c.id,c.name,COUNT(b.id) total_books FROM categories c LEFT JOIN books b ON b.category_id=c.id GROUP BY c.id,c.name ORDER BY c.name')->fetchAll();
$pageTitle='Thể loại sách';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">CATALOG STRUCTURE</div><h1>Quản lý thể loại sách</h1><p>Manager tạo và đổi tên thể loại; chỉ xóa được thể loại chưa gắn với đầu sách nào.</p></div><a class="btn outline" href="books.php">← Danh mục sách</a></div>
<?php if($error):?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<div class="two-col admin-layout"><section class="panel"><div class="panel-head"><h2>Thêm thể loại</h2></div><form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="create"><label>Tên thể loại mới<input name="name" maxlength="100" required placeholder="Ví dụ: Truyện tranh"></label><button class="btn primary">＋ Lưu thể loại</button></form></section><section class="panel"><div class="panel-head"><h2>Danh sách thể loại</h2></div><div class="table-wrap"><table><thead><tr><th>Thể loại</th><th>Đầu sách</th><th>Đổi tên</th><th></th></tr></thead><tbody><?php foreach($categories as $cat): ?><tr><td><strong><?= h($cat['name']) ?></strong></td><td><span class="badge blue"><?= (int)$cat['total_books'] ?> sách</span></td><td><form class="inline-form" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="rename"><input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>"><input name="name" value="<?= h($cat['name']) ?>" maxlength="100" required><button class="btn outline btn-small">Lưu</button></form></td><td><?php if((int)$cat['total_books']===0): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>"><button class="btn danger btn-small">Xóa</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php require __DIR__.'/partials/footer.php'; ?>
