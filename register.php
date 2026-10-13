<?php
require_once __DIR__.'/app.php'; if (current_user()) redirect('dashboard.php');
if (setting('registration_enabled','1') !== '1') { http_response_code(403); exit('Cửa hàng đang tạm dừng đăng ký tài khoản mới.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf(); $name=post_string('name',120); $email=strtolower(post_string('email',190)); $pass=$_POST['password'] ?? '';
 if (!is_string($pass) || strlen($pass)<8) $error='Mật khẩu phải từ 8 ký tự.';
 elseif (strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Họ tên hoặc email không hợp lệ.';
 else {
  try { $st=db()->prepare("INSERT INTO users(role_id,full_name,email,password_hash) SELECT id,?,?,? FROM roles WHERE code='customer'"); $st->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]); flash('success','Tạo tài khoản khách hàng thành công. Hãy đăng nhập.'); redirect('login.php'); }
  catch (PDOException $e) { if ($e->getCode()==='23000') $error='Email đã tồn tại.'; else throw $e; }
 }
}
$pageTitle='Đăng ký'; require __DIR__.'/partials/header.php'; ?>
<section class="auth-card"><div class="auth-brand"><span class="brand-icon">B<span>.</span></span><strong>BOOK SHOP MINI</strong></div><div class="eyebrow">NEW ACCOUNT</div><h1>Đăng ký<br>khách hàng.</h1><p class="muted">Tài khoản đăng ký công khai luôn được gán vai trò Customer.</p>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="stack"><?= csrf_field() ?><label>Họ và tên<input name="name" maxlength="120" value="<?= old('name') ?>" required></label><label>Email<input type="email" name="email" value="<?= old('email') ?>" required></label><label>Mật khẩu (tối thiểu 8 ký tự)<input type="password" name="password" minlength="8" required></label><button class="btn primary wide">Tạo tài khoản →</button></form><p class="small muted">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p></section>
<?php require __DIR__.'/partials/footer.php'; ?>
