<?php
require_once __DIR__.'/app.php';require_permission('roles.manage');
$roles=db()->query('SELECT id,code,name FROM roles ORDER BY id')->fetchAll();
$perms=db()->query('SELECT id,code,name,section FROM permissions ORDER BY id')->fetchAll();
$permsById=array_column($perms,null,'id');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$roleCode=post_string('role_code',30);$role=null;
 foreach($roles as $r)if($r['code']===$roleCode)$role=$r;
 if(!$role||$roleCode==='admin')$error='Không được thay đổi quyền cố định của Admin.';
 else{
  $selected=$_POST['permissions']??[];if(!is_array($selected))$selected=[];
  $ids=[];$invalid=false;
  foreach($selected as $v){$id=value_int($v);if(!$id||!isset($permsById[$id])){$invalid=true;break;}$ids[$id]=true;}
  if($invalid)$error='Danh sách quyền có mục không hợp lệ.';
  else{
   db()->beginTransaction();try{
    db()->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$role['id']]);
    $st=db()->prepare('INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)');foreach(array_keys($ids) as $id)$st->execute([$role['id'],$id]);
    db()->commit();flash('success','Đã cập nhật ma trận quyền của '.ucfirst($roleCode).'.');redirect('roles.php');
   }catch(Throwable $e){db()->rollBack();throw $e;}
  }
 }
}
$raw=db()->query('SELECT r.code role_code,p.id permission_id FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id')->fetchAll();$matrix=[];
foreach($raw as $r)$matrix[$r['role_code']][(int)$r['permission_id']]=true;
$pageTitle='Ma trận phân quyền';require __DIR__.'/partials/header.php'; ?>
<div class="page-head"><div><div class="eyebrow">ROLE BASED ACCESS CONTROL</div><h1>Ma trận quyền RBAC</h1><p>Admin gán quyền cụ thể cho Manager, Employee, Customer. Cơ chế kiểm tra được thực hiện trên server ở mỗi trang và hành động.</p></div></div>
<?php if($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<section class="panel"><div class="panel-head"><h2>Ma trận quyền đang áp dụng</h2><span class="muted small">✓ Có quyền / — Không có quyền</span></div><div class="table-wrap"><table class="matrix"><thead><tr><th>Chức năng</th><?php foreach($roles as $r): ?><th><?= h(ucfirst($r['code'])) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($perms as $p): ?><tr><td><strong><?= h($p['name']) ?></strong><div class="small muted"><?= h($p['code']) ?></div></td><?php foreach($roles as $r): ?><td><span class="<?= $r['code']==='admin'||isset($matrix[$r['code']][$p['id']])?'text-green':'muted' ?>"><?= $r['code']==='admin'||isset($matrix[$r['code']][$p['id']])?'✓':'—' ?></span></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></section>
<div class="page-head" style="margin-top:26px"><div><div class="eyebrow">EDIT PERMISSIONS</div><h2>Cấu hình quyền theo vai trò</h2><p>Đánh dấu quyền mong muốn, nhấn lưu. Có thể đăng xuất/đăng nhập lại để kiểm tra giao diện theo quyền mới.</p></div></div>
<div class="role-editor-grid"><?php foreach($roles as $r): if($r['code']==='admin')continue; ?><section class="panel"><div class="panel-head"><h2><?= h(ucfirst($r['code'])) ?></h2><span class="badge blue">RBAC</span></div><form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="role_code" value="<?= h($r['code']) ?>"><?php foreach($perms as $p): ?><label class="permission-row"><input type="checkbox" name="permissions[]" value="<?= (int)$p['id'] ?>" <?= isset($matrix[$r['code']][$p['id']])?'checked':'' ?>><span><?= h($p['name']) ?><small><?= h($p['section']) ?></small></span></label><?php endforeach; ?><button class="btn primary wide">Lưu quyền <?= h(ucfirst($r['code'])) ?></button></form></section><?php endforeach; ?></div><div class="notice"><strong>Admin</strong> luôn có toàn quyền. Đăng ký công khai chỉ tạo tài khoản Customer; không thể tự gửi role=admin để thăng quyền.</div>
<?php require __DIR__.'/partials/footer.php'; ?>
