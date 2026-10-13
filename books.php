<?php
require_once __DIR__.'/app.php'; require_permission('catalog.view');
$q=isset($_GET['q'])&&is_string($_GET['q']) ? trim(mb_cut($_GET['q'],100)) : '';
$category=value_int($_GET['category'] ?? null);
$sql='SELECT b.*,c.name category_name FROM books b LEFT JOIN categories c ON c.id=b.category_id WHERE 1=1'; $args=[];
if (!can('catalog.manage')) $sql.=' AND b.is_active=1';
if ($q!=='') { $sql.=' AND (b.title LIKE ? OR b.author LIKE ?)'; $args[]='%'.$q.'%';$args[]='%'.$q.'%'; }
if ($category) { $sql.=' AND b.category_id=?';$args[]=$category; }
$sql.=' ORDER BY b.is_active DESC,b.id DESC'; $stmt=db()->prepare($sql);$stmt->execute($args);$books=$stmt->fetchAll();
$cats=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$pageTitle='Danh mục sách';require __DIR__.'/partials/header.php'; ?>
<?php if (trim(setting('store_notice')) !== ''): ?><div class="notice"><?= h(setting('store_notice')) ?></div><?php endif; ?>
<div class="page-head"><div><div class="eyebrow">THE COLLECTION</div><h1>Thư viện Book Shop</h1><p>Tìm sách phù hợp với bạn. Mọi thao tác được giới hạn theo vai trò đăng nhập.</p></div><?php if(can('catalog.manage')): ?><a class="btn primary" href="book_form.php">＋ Thêm sách mới</a><?php endif; ?></div>
<form class="filterbar" method="get"><input name="q" value="<?= h($q) ?>" placeholder="⌕  Tìm sách hoặc tác giả..."><select name="category"><option value="">Tất cả thể loại</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $category===(int)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select><button class="btn dark">Tìm kiếm</button></form>
<div class="book-grid"><?php foreach($books as $b): ?><article class="book-card"><div class="book-art book-art-<?= (int)$b['id']%5 ?>"><div class="art-lines">✦</div><span class="book-art-label"><?= h($b['category_name'] ?: 'Sách') ?></span><strong><?= h(mb_cut($b['title'],50)) ?></strong><small><?= h($b['author']) ?></small></div><div class="book-info"><div class="book-meta"><span class="badge <?= (int)$b['stock']>0?'green':'red' ?>"><?= (int)$b['stock']>0 ? 'Còn '.$b['stock'].' cuốn':'Hết hàng' ?></span><?php if(!(int)$b['is_active']): ?><span class="badge red">Đã ẩn</span><?php endif; ?></div><h3><a class="book-title-link" href="book_detail.php?id=<?= (int)$b['id'] ?>"><?= h($b['title']) ?></a></h3><p class="muted book-author"><?= h($b['author']) ?></p><div class="price"><?= money($b['price']) ?></div>
<div class="book-actions"><?php if(can('cart.manage')&&(int)$b['stock']>0&&(int)$b['is_active']): ?><form action="cart.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="quantity" value="1"><button class="btn outline btn-small">＋ Giỏ hàng</button></form><?php if(can('orders.place')): ?><form action="checkout.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="buy_prepare"><input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>"><button class="btn primary btn-small">Mua ngay</button></form><?php endif; ?><?php endif; ?>
<?php if(can('catalog.manage')): ?><a class="btn outline btn-small" href="book_form.php?id=<?= (int)$b['id'] ?>">Chỉnh sửa</a><?php endif; ?></div></div></article><?php endforeach; ?></div>
<?php if(!$books): ?><div class="empty"><h2>Không tìm thấy sách</h2><p>Thử từ khóa hoặc thể loại khác.</p></div><?php endif; ?>
<?php require __DIR__.'/partials/footer.php'; ?>
