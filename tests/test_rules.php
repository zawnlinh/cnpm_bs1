<?php
// Kiểm thử nghiệp vụ không cần PDO MySQL hoặc database.
require_once __DIR__.'/../app.php';
$cases=[
 ['placed','confirmed',false,true],['placed','cancelled',false,true],['confirmed','packing',false,true],
 ['packing','shipped',false,true],['shipped','delivered',false,true],['delivered','cancelled',false,false],
 ['cancelled','confirmed',false,false],['placed','shipped',false,false],
 ['placed','cancelled',true,true],['confirmed','cancelled',true,false],['placed','confirmed',true,false],
];
$checked=0;
function expect(bool $ok, string $message): void { global $checked; ++$checked; if(!$ok) { fwrite(STDERR, "FAIL: $message\n");exit(1); } }
foreach($cases as [$old,$new,$customer,$expected])expect(allowed_transition($old,$new,$customer)===$expected, "$old => $new (customer=$customer)");
expect(value_int('20',1,20)===20,'Quantity boundary');
expect(value_int('21',1,20)===null,'Quantity above max');
expect(value_int('-3',0,10)===null,'Negative not allowed');
expect(value_int('1abc',0,10)===null,'Malformed integer');
expect(amount('65000')==='65000.00','Price in cents');
expect(amount('-200')===null,'Negative price');
expect(amount('100.123')===null,'Invalid decimals');
expect(status_name('packing')==='Đang chuẩn bị','Status label');
$employee=['id'=>8,'role_code'=>'employee'];$manager=['id'=>9,'role_code'=>'manager'];
expect(assigned_employee(['assigned_to'=>8],$employee),'Employee assigned correct order');
expect(!assigned_employee(['assigned_to'=>7],$employee),'Employee other order forbidden');
expect(!assigned_employee(['assigned_to'=>null],$employee),'Employee unassigned order forbidden');
expect(!assigned_employee(['assigned_to'=>8],$manager),'Manager is not employee');
expect(shop_manager($manager),'Manager management role');
expect(!shop_manager($employee),'Employee not shop manager');
expect(ticket_status_name('in_progress')==='Đang hỗ trợ','Ticket status label');
expect(ticket_employee_visible(['assigned_to'=>null,'status'=>'open'],$employee),'Employee sees unclaimed open ticket');
expect(ticket_employee_visible(['assigned_to'=>8,'status'=>'in_progress'],$employee),'Employee sees assigned ticket');
expect(!ticket_employee_visible(['assigned_to'=>9,'status'=>'in_progress'],$employee),'Employee cannot see other assignee ticket');
expect(!ticket_employee_visible(['assigned_to'=>null,'status'=>'resolved'],$employee),'Employee cannot see closed unassigned ticket');
expect(!ticket_employee_visible(['assigned_to'=>8,'status'=>'in_progress'],$manager),'Manager not employee ticket scope');
echo "PASS: $checked checks (transitions, validation, labels).\n";
