<?php
require_once __DIR__.'/app.php'; require_permission('catalog.manage');
$id=value_int($_GET['id'] ?? null); $book=null;
if ($id) { $st=db()->prepare('SELECT * FROM books WHERE id=?');$st->execute([$id]);$book=$st->fetch();if(!$book){http_response_code(404);exit('Không tìm thấy sách.');}}
$cats=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$pageTitle=$book?'Chỉnh sửa sách':'Thêm sách';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">CATALOG MANAGEMENT</div><h1><?= $book?'Chỉnh sửa thông tin sách':'Thêm một đầu sách mới' ?></h1><p>Manager quản lý thông tin sách và giá; Employee chỉ điều chỉnh số lượng kho.</p></div><a href="books.php" class="btn outline">← Danh mục sách</a></div>
<section class="panel form-panel"><form method="post" action="book_save.php" class="stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($book['id']??0) ?>"><div class="form-grid"><label>Tên sách *<input name="title" maxlength="180" required value="<?= h($book['title']??'') ?>"></label><label>Tác giả *<input name="author" maxlength="150" required value="<?= h($book['author']??'') ?>"></label><label>Thể loại<select name="category_id"><option value="">Chưa phân loại</option><?php foreach($cats as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)($book['category_id']??0)===(int)$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option><?php endforeach; ?></select></label><label>Giá bán (VND) *<input type="number" min="0" max="9999999999" step="1000" name="price" required value="<?= h($book['price']??'0') ?>"></label></div>
<label>Mô tả<textarea name="description" rows="4" maxlength="1500"><?= h($book['description']??'') ?></textarea></label>
<?php if(!$book): ?><label>Tồn kho ban đầu<input type="number" min="0" max="1000000" name="stock" required value="10"></label><?php else: ?><div class="notice">Tồn kho hiện tại: <strong><?= (int)$book['stock'] ?></strong>. Để điều chỉnh, dùng mục <a href="stock.php">Tồn kho</a> nhằm lưu lịch sử biến động.</div><?php endif; ?>
<label class="checkrow"><input type="checkbox" name="is_active" value="1" <?= !$book||(int)$book['is_active']?'checked':'' ?>>Đang hiển thị để bán</label>
<div class="form-actions"><button class="btn primary">Lưu thông tin sách</button><a class="btn outline" href="books.php">Hủy</a></div></form></section>
<?php require __DIR__.'/partials/footer.php'; ?>
