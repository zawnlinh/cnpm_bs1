<?php
require_once __DIR__.'/app.php';$u=require_permission('users.manage');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();$action=post_string('action',30);$targetId=value_int($_POST['user_id']??null);
 try {
  if($action==='create') {
   $name=post_string('full_name',120);$email=strtolower(post_string('email',190));$pass=$_POST['password']??null;$role=post_string('role',30);
   if(strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||!is_string($pass)||strlen($pass)<8||!in_array($role,['admin','manager','employee','customer'],true)) throw new DomainException('Thông tin tài khoản không hợp lệ, mật khẩu phải từ 8 ký tự.');
   $st=db()->prepare('INSERT INTO users(role_id,full_name,email,password_hash) SELECT id,?,?,? FROM roles WHERE code=?');$st->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
   if($st->rowCount()!==1)throw new DomainException('Vai trò không tồn tại.');flash('success','Đã tạo tài khoản mới.');redirect('users.php');
  }
  if(!$targetId)throw new DomainException('Chưa chọn tài khoản.');
  $st=db()->prepare('SELECT u.id,u.role_id,r.code role_code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?');$st->execute([$targetId]);$target=$st->fetch();if(!$target)throw new DomainException('Không tìm thấy tài khoản.');
  if($action==='role') {
   $role=post_string('role',30);if(!in_array($role,['admin','manager','employee','customer'],true))throw new DomainException('Vai trò không hợp lệ.');
   if($targetId===(int)$u['id']&&$role!=='admin')throw new DomainException('Admin không thể tự hạ quyền của mình.');
   $st=db()->prepare('UPDATE users SET role_id=(SELECT id FROM roles WHERE code=?) WHERE id=?');$st->execute([$role,$targetId]);flash('success','Đã cập nhật vai trò.');redirect('users.php');
  } elseif($action==='toggle') {
   if($targetId===(int)$u['id'])throw new DomainException('Bạn không thể tự khóa tài khoản.');
   db()->prepare('UPDATE users SET is_active=1-is_active WHERE id=?')->execute([$targetId]);flash('success','Đã cập nhật trạng thái tài khoản.');redirect('users.php');
  } elseif($action==='reset') {
   $pass=$_POST['new_password']??'';if(!is_string($pass)||strlen($pass)<8)throw new DomainException('Mật khẩu mới phải từ 8 ký tự.');
   db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),$targetId]);flash('success','Đã thay đổi mật khẩu.');redirect('users.php');
  } else throw new DomainException('Thao tác không hợp lệ.');
 } catch(DomainException $e){$error=$e->getMessage();}
 catch(PDOException $e){if($e->getCode()==='23000')$error='Email đã được sử dụng.';else throw $e;}
}
$users=db()->query('SELECT u.id,u.full_name,u.email,u.is_active,u.created_at,r.code role_code,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id')->fetchAll();
$pageTitle='Quản lý tài khoản';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">IDENTITY & ACCESS</div><h1>Người dùng hệ thống</h1><p>Chỉ Admin được tạo tài khoản nhân sự, thay đổi vai trò, khóa tài khoản và đặt lại mật khẩu.</p></div><a class="btn outline" href="roles.php">Xem ma trận quyền →</a></div>
<?php if($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<div class="two-col admin-layout"><section class="panel"><div class="panel-head"><h2>Tạo tài khoản mới</h2></div><form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="create"><label>Họ và tên<input name="full_name" maxlength="120" required value="<?= old('full_name') ?>"></label><label>Email<input type="email" name="email" maxlength="190" required value="<?= old('email') ?>"></label><label>Mật khẩu ban đầu<input type="password" name="password" minlength="8" required></label><label>Vai trò<select name="role"><?php foreach(['admin'=>'Admin','manager'=>'Manager','employee'=>'Employee','customer'=>'Customer'] as $k=>$label): ?><option value="<?= h($k) ?>" <?= isset($_POST['role'])&&$_POST['role']===$k?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label><button class="btn primary">＋ Tạo tài khoản</button></form></section>
<section class="panel"><div class="panel-head"><h2>Cách phân quyền hoạt động</h2></div><div class="guide-steps"><div><span>01</span><p><strong>Role (Vai trò)</strong><br>Admin / Manager / Employee / Customer</p></div><div><span>02</span><p><strong>Permission (Quyền)</strong><br>Chức năng cụ thể như xử lý đơn, xem báo cáo.</p></div><div><span>03</span><p><strong>Server check</strong><br>PHP chặn truy cập nếu tài khoản không có quyền.</p></div><div><span>04</span><p><strong>Owner check</strong><br>Customer chỉ được xem và hủy đơn của chính mình.</p></div></div></section></div>
<section class="panel"><div class="panel-head"><h2>Danh sách tài khoản (<?= count($users) ?>)</h2></div><div class="table-wrap"><table><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Đổi vai trò</th><th>Khóa / Mở</th><th>Đặt lại mật khẩu</th></tr></thead><tbody><?php foreach($users as $item): ?><tr><td><strong><?= h($item['full_name']) ?></strong><div class="muted small"><?= h($item['email']) ?></div></td><td><span class="badge blue"><?= h(strtoupper($item['role_code'])) ?></span></td><td><span class="badge <?= (int)$item['is_active']?'green':'red' ?>"><?= $item['is_active']?'Hoạt động':'Đã khóa' ?></span></td><td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?= (int)$item['id'] ?>"><select class="small-select" name="role"><?php foreach(['admin','manager','employee','customer'] as $role): ?><option value="<?= h($role) ?>" <?= $item['role_code']===$role?'selected':'' ?>><?= h(ucfirst($role)) ?></option><?php endforeach; ?></select><button class="btn outline btn-small">Lưu</button></form></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= (int)$item['id'] ?>"><button class="btn <?= (int)$item['is_active']?'subtle':'outline' ?> btn-small" <?= (int)$item['id']===(int)$u['id']?'disabled':'' ?>><?= $item['is_active']?'Khóa':'Mở khóa' ?></button></form></td><td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="user_id" value="<?= (int)$item['id'] ?>"><input class="reset-input" name="new_password" type="password" minlength="8" required placeholder="Mật khẩu mới"><button class="btn outline btn-small">Đổi</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__.'/partials/footer.php'; ?>
