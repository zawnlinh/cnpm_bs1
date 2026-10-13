<?php
require_once __DIR__.'/app.php'; require_permission('catalog.manage');only_post();
$id=value_int($_POST['id']??null);$title=post_string('title',180);$author=post_string('author',150);$description=post_string('description',1500);$price=amount($_POST['price']??null);
$category=value_int($_POST['category_id']??null);$active=isset($_POST['is_active'])?1:0;
if($title===''||$author===''||$price===null){flash('error','Vui lòng nhập đủ thông tin và giá bán hợp lệ.');redirect('books.php');}
if($category){$st=db()->prepare('SELECT COUNT(*) FROM categories WHERE id=?');$st->execute([$category]);if(!$st->fetchColumn()){flash('error','Thể loại không tồn tại.');redirect('books.php');}}
if($id){$st=db()->prepare('UPDATE books SET title=?,author=?,description=?,price=?,category_id=?,is_active=? WHERE id=?');$st->execute([$title,$author,$description,$price,$category,$active,$id]);flash('success','Đã cập nhật sách.');}
else{$stock=value_int($_POST['stock']??null,0,1000000);if($stock===null){flash('error','Số lượng ban đầu không hợp lệ.');redirect('book_form.php');}db()->beginTransaction();try{$st=db()->prepare('INSERT INTO books(category_id,title,author,description,price,stock,is_active) VALUES(?,?,?,?,?,?,?)');$st->execute([$category,$title,$author,$description,$price,$stock,$active]);$new=(int)db()->lastInsertId();if($stock>0)log_stock($new,$stock,(int)current_user()['id'],'Nhập kho ban đầu');db()->commit();flash('success','Đã thêm sách và ghi nhận tồn kho.');}catch(Throwable $e){db()->rollBack();throw $e;}}
redirect('books.php');
