<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('bookshop_mini_session');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(503);
        exit('<!doctype html><html lang="vi"><meta charset="utf-8"><title>Lỗi cơ sở dữ liệu</title><body style="font:16px system-ui;padding:50px;max-width:700px;margin:auto"><h2>Không thể kết nối MySQL</h2><p>Hãy bật MySQL trong XAMPP, import <code>sql/bookshop.sql</code> và kiểm tra <code>config.php</code>.</p><p><a href="index.php">Thử lại</a></p></body></html>');
    }
}
function h(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path): string { return $path; }
function redirect(string $to): void { header('Location: ' . url($to), true, 303); exit; }
function csrf_token(): string { if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">'; }
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['_csrf']) || !is_string($_POST['_csrf']) || !hash_equals(csrf_token(), $_POST['_csrf'])) {
        http_response_code(419); exit('Yêu cầu không hợp lệ (CSRF). Hãy tải lại trang và thử lần nữa.');
    }
}
function only_post(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); } check_csrf(); }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type' => $type, 'message' => $message]; }
function old(string $key): string { return h($_POST[$key] ?? ''); }
function current_user(bool $refresh = false): ?array {
    static $cached = false;
    if (!isset($_SESSION['uid'])) return null;
    if ($cached !== false && !$refresh) return $cached;
    $stmt = db()->prepare('SELECT u.id,u.full_name,u.email,u.is_active,r.code role_code,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? LIMIT 1');
    $stmt->execute([(int)$_SESSION['uid']]);
    $u = $stmt->fetch();
    if (!$u || !(int)$u['is_active']) { unset($_SESSION['uid']); $cached = false; return null; }
    $cached = $u; return $u;
}
function require_login(): array { $u = current_user(); if (!$u) { flash('error','Vui lòng đăng nhập.'); redirect('login.php'); } return $u; }
function is_admin(): bool { $u = current_user(); return $u && $u['role_code'] === 'admin'; }
function can(string $permission): bool {
    $u = current_user(); if (!$u) return false;
    if ($u['role_code'] === 'admin') return true;
    static $perms = [];
    $key = (int)$u['id'];
    if (!isset($perms[$key])) {
        $stmt = db()->prepare('SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id JOIN users u ON u.role_id=rp.role_id WHERE u.id=?');
        $stmt->execute([$key]);
        $perms[$key] = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    }
    return isset($perms[$key][$permission]);
}
function require_permission(string $permission): array {
    $u = require_login();
    if (!can($permission)) { http_response_code(403); require __DIR__ . '/403.php'; exit; }
    return $u;
}
function value_int(mixed $v, int $min = 1, int $max = 1000000): ?int {
    if (is_array($v) || !is_scalar($v) || !preg_match('/^[0-9]+$/D', (string)$v)) return null;
    $num = filter_var((string)$v, FILTER_VALIDATE_INT);
    return $num !== false && $num >= $min && $num <= $max ? $num : null;
}
function amount(mixed $v): ?string {
    if (is_array($v) || !is_scalar($v) || !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', trim((string)$v))) return null;
    $s = number_format((float)$v, 2, '.', '');
    return (float)$s >= 0 && (float)$s <= 9999999999 ? $s : null;
}
function money(mixed $value): string { return number_format((float)$value, 0, ',', '.') . ' đ'; }
function status_name(string $status): string { return [ 'placed'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','packing'=>'Đang chuẩn bị','shipped'=>'Đang giao','delivered'=>'Đã giao','cancelled'=>'Đã hủy' ][$status] ?? $status; }
function status_class(string $status): string { return [ 'placed'=>'amber','confirmed'=>'blue','packing'=>'blue','shipped'=>'purple','delivered'=>'green','cancelled'=>'red' ][$status] ?? 'gray'; }
function allowed_transition(string $current, string $next, bool $customer = false): bool {
    if ($customer) return $current === 'placed' && $next === 'cancelled';
    return in_array($next, [
        'placed'=>['confirmed','cancelled'], 'confirmed'=>['packing','cancelled'],
        'packing'=>['shipped','cancelled'], 'shipped'=>['delivered'],
        'delivered'=>[], 'cancelled'=>[],
    ][$current] ?? [], true);
}
function order_event(int $orderId, ?int $actorId, string $from, string $to, string $note=''): void {
    db()->prepare('INSERT INTO order_events(order_id,actor_id,old_status,new_status,note) VALUES(?,?,?,?,?)')->execute([$orderId,$actorId,$from,$to,mb_cut($note,200)]);
}
function mb_cut(string $s, int $n): string { return function_exists('mb_substr') ? mb_substr($s,0,$n) : substr($s,0,$n); }
function log_stock(int $bookId, int $delta, ?int $actorId, string $reason, ?int $orderId=null): void {
    db()->prepare('INSERT INTO stock_logs(book_id,actor_id,order_id,delta,reason) VALUES(?,?,?,?,?)')->execute([$bookId,$actorId,$orderId,$delta,$reason]);
}
function post_string(string $key, int $max=200): string {
    $v=$_POST[$key] ?? ''; return is_string($v) ? trim(mb_cut($v,$max)) : '';
}

// V2: Phan biet phan quyen RBAC va pham vi du lieu theo vai tro.
function role_is(array $user, string ...$roles): bool { return in_array($user['role_code'], $roles, true); }
function shop_manager(array $user): bool { return role_is($user,'admin','manager'); }
function assigned_employee(array $item, array $user): bool {
    return role_is($user,'employee') && (int)($item['assigned_to'] ?? 0) > 0 && (int)$item['assigned_to'] === (int)$user['id'];
}
function order_scope_allows(array $order, array $user): bool {
    if (shop_manager($user) && can('orders.view_all')) return true;
    if (role_is($user,'employee') && can('orders.view_all'))
        return assigned_employee($order,$user);
    return (int)$order['user_id'] === (int)$user['id'] && can('orders.own') && role_is($user,'customer');
}
function may_process_order(array $order, array $user): bool {
    if (!can('orders.process')) return false;
    if (shop_manager($user)) return true;
    return assigned_employee($order,$user);
}
function ticket_employee_visible(array $ticket, array $user): bool {
    if (!role_is($user,'employee')) return false;
    return ((int)($ticket['assigned_to'] ?? 0) === (int)$user['id']) ||
        ($ticket['status']==='open' && empty($ticket['assigned_to']));
}
function support_scope_allows(array $ticket, array $user): bool {
    if (shop_manager($user) && can('support.view_all')) return true;
    if (role_is($user,'customer') && can('support.own') && (int)$ticket['customer_id'] === (int)$user['id']) return true;
    if (role_is($user,'employee') && can('support.handle'))
        return ticket_employee_visible($ticket,$user);
    return false;
}
function setting(string $key, string $fallback = ''): string {
    static $cache=[];
    if (!array_key_exists($key,$cache)) {
        $s=db()->prepare('SELECT `value` FROM app_settings WHERE `key`=? LIMIT 1');$s->execute([$key]);
        $value=$s->fetchColumn();$cache[$key]=$value===false?$fallback:(string)$value;
    }
    return $cache[$key];
}
function ticket_status_name(string $status): string {
    return ['open'=>'Mới gửi','in_progress'=>'Đang hỗ trợ','resolved'=>'Đã giải quyết'][$status] ?? $status;
}
