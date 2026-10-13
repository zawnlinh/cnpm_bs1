<?php
require_once __DIR__.'/app.php';
if (current_user()) redirect('dashboard.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $email = strtolower(post_string('email',190)); $password = $_POST['password'] ?? '';
    if (!is_string($password)) $password = '';
    $st = db()->prepare('SELECT id,password_hash,is_active FROM users WHERE email=? LIMIT 1'); $st->execute([$email]); $u=$st->fetch();
    if ($u && (int)$u['is_active'] && password_verify($password,$u['password_hash'])) {
        session_regenerate_id(true); $_SESSION['uid']=(int)$u['id']; redirect('dashboard.php');
    }
    $error='Email hoặc mật khẩu không đúng, hoặc tài khoản đang bị khóa.';
}
$pageTitle='Đăng nhập'; require __DIR__.'/partials/header.php'; ?>
<section class="auth-card"><div class="auth-brand"><span class="brand-icon">B<span>.</span></span><strong>BOOK SHOP <em>MINI</em></strong></div>
<div class="eyebrow">WELCOME BACK</div><h1>Đăng nhập vào<br>hệ thống.</h1><p class="muted">4 vai trò · 4 giao diện nghiệp vụ · Một hệ thống chung.</p>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="stack"><?= csrf_field() ?><label>Email<input type="email" name="email" value="<?= old('email') ?>" autocomplete="username" placeholder="admin@bookshop.test" required></label>
<label>Mật khẩu<input type="password" name="password" autocomplete="current-password" required placeholder="••••••••"></label>
<button class="btn primary wide">Đăng nhập →</button></form><p class="small muted">Chưa có tài khoản? <a href="register.php">Đăng ký khách hàng</a></p><p class="small muted">Lần đầu cài đặt? <a href="setup.php">Tạo 4 tài khoản demo</a></p></section>
<?php require __DIR__.'/partials/footer.php'; ?>
