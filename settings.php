<?php
require_once __DIR__.'/app.php';$u=require_permission('settings.manage');
if(!role_is($u,'admin')){http_response_code(403);require __DIR__.'/403.php';exit;}
$fields=['store_name','store_email','store_phone','store_notice','low_stock_threshold','registration_enabled'];
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $data=[];foreach($fields as $key)$data[$key]=post_string($key,500);
 if($data['store_name']===''||(function_exists('mb_strlen')?mb_strlen($data['store_name']):strlen($data['store_name']))>120)$error='Tên cửa hàng phải từ 1 đến 120 ký tự.';
 elseif(strlen($data['store_email'])>190||!filter_var($data['store_email'],FILTER_VALIDATE_EMAIL))$error='Email hỗ trợ không hợp lệ.';
 elseif(strlen($data['store_phone'])>25||!preg_match('/^[0-9+(). \-]{7,25}$/',$data['store_phone']))$error='Số điện thoại không hợp lệ.';
 elseif(strlen($data['store_notice'])>300)$error='Thông báo tối đa 300 ký tự.';
 elseif(value_int($data['low_stock_threshold'],0,10000)===null)$error='Ngưỡng cảnh báo tồn kho phải từ 0 đến 10000.';
 elseif(!in_array($data['registration_enabled'],['0','1'],true))$error='Giá trị bật/tắt đăng ký không hợp lệ.';
 if(!$error){
  db()->beginTransaction();
  try{$st=db()->prepare('INSERT INTO app_settings(`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');foreach($data as $key=>$value)$st->execute([$key,$value]);db()->commit();flash('success','Đã lưu cấu hình cửa hàng.');redirect('settings.php');}
  catch(Throwable $e){db()->rollBack();throw $e;}
 }
}
foreach($fields as $key){if(!isset($data[$key]))$data[$key]=setting($key);}
$pageTitle='Cài đặt cửa hàng';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">ADMIN / SYSTEM SETTINGS</div><h1>Cấu hình cửa hàng</h1><p>Chỉ Admin được sửa. Thiết lập sẽ áp dụng thực tế cho cửa hàng.</p></div></div>
<?php if($error):?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<section class="panel form-panel"><form method="post" class="stack"><?= csrf_field() ?>
<label>Tên cửa hàng<input name="store_name" required maxlength="120" value="<?= h($data['store_name']) ?>"></label>
<div class="form-grid"><label>Email hỗ trợ<input name="store_email" type="email" required maxlength="190" value="<?= h($data['store_email']) ?>"></label><label>Số hotline<input name="store_phone" required maxlength="25" value="<?= h($data['store_phone']) ?>"></label></div>
<label>Thông báo hiển thị tại trang sách<textarea name="store_notice" rows="3" maxlength="300"><?= h($data['store_notice']) ?></textarea></label>
<label>Ngưỡng cảnh báo gần hết sách<input name="low_stock_threshold" type="number" min="0" max="10000" value="<?= h($data['low_stock_threshold']) ?>"></label>
<label>Cho phép Customer tự đăng ký<select name="registration_enabled"><option value="1" <?= $data['registration_enabled']==='1'?'selected':'' ?>>Bật đăng ký</option><option value="0" <?= $data['registration_enabled']==='0'?'selected':'' ?>>Tắt đăng ký</option></select></label>
<div class="notice">Tên cửa hàng hiển thị ở thanh bên/tiêu đề trang; ngưỡng tồn kho ảnh hưởng Dashboard Employee; tùy chọn đăng ký được kiểm tra ở PHP; hotline và email dùng trong trang hỗ trợ.</div>
<button class="btn primary">Lưu cấu hình</button></form></section>
<?php require __DIR__.'/partials/footer.php'; ?>
