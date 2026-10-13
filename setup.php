<?php
require_once __DIR__.'/app.php';
// Setup chi duoc chay mot lan, khi database chua co bat ky user nao.
$totalUsers=(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 if ($totalUsers>0) { http_response_code(403); exit('CSDL đã có người dùng. Setup bị khóa để tránh tạo lại tài khoản mặc định.'); }
 $demo=[
  ['admin','Quản trị viên','admin@bookshop.test','Admin@123456'],
  ['manager','Quản lý cửa hàng','manager@bookshop.test','Manager@123456'],
  ['employee','Nhân viên bán hàng','employee@bookshop.test','Employee@123456'],
  ['customer','Khách hàng thử nghiệm','customer@bookshop.test','Customer@123456'],
 ];
 try {
  db()->beginTransaction(); $sql='INSERT INTO users(role_id,full_name,email,password_hash) SELECT id,?,?,? FROM roles WHERE code=?'; $st=db()->prepare($sql);
  foreach($demo as [$role,$name,$email,$pwd]) { $st->execute([$name,$email,password_hash($pwd,PASSWORD_DEFAULT),$role]); if($st->rowCount()!==1) throw new RuntimeException('Lỗi dữ liệu vai trò.'); }
  db()->commit(); flash('success','Đã tạo 4 tài khoản thử nghiệm. Nên xóa hoặc đổi tên setup.php sau khi cài đặt.'); redirect('login.php');
 } catch(Throwable $e) { if(db()->inTransaction()) db()->rollBack(); $error='Không thể khởi tạo. Kiểm tra đã import đủ file SQL.'; }
}
$pageTitle='Thiết lập lần đầu'; require __DIR__.'/partials/header.php'; ?>
<section class="auth-card"><div class="auth-brand"><span class="brand-icon">B<span>.</span></span><strong>BOOK SHOP MINI</strong></div><div class="eyebrow">FIRST TIME SETUP</div><h1>Thiết lập dữ liệu thử.</h1><p class="muted">Bước 1: Import <code>sql/bookshop.sql</code> bằng phpMyAdmin. Bước 2: Tạo đúng bốn tài khoản mẫu.</p>
<?php if($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<?php if($totalUsers>0): ?><div class="alert success">Cơ sở dữ liệu đã có người dùng. Chức năng thiết lập đã khóa.</div><a class="btn primary" href="login.php">Đăng nhập</a>
<?php else: ?><div class="setup-accounts"><strong>4 tài khoản sẽ được tạo:</strong><div>admin@bookshop.test — Admin@123456</div><div>manager@bookshop.test — Manager@123456</div><div>employee@bookshop.test — Employee@123456</div><div>customer@bookshop.test — Customer@123456</div></div><form method="post"><?= csrf_field() ?><button class="btn primary wide" type="submit">Khởi tạo 4 tài khoản demo</button></form><?php endif; ?></section>
<?php require __DIR__.'/partials/footer.php'; ?>
