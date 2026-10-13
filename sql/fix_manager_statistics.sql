-- Ban va quyen Manager: bo sung quyen Thong ke neu bi mat tren CSDL hien tai.
-- An toan de chay lai; KHONG xoa don hang, khach hang, user hay gia tri quyen khac.
-- Dung tren Book Shop Mini V1/V2 da cai dat (database = bookshop_mini).
USE bookshop_mini;

INSERT IGNORE INTO permissions (code, name, section)
VALUES ('reports.view', 'Thong ke kinh doanh', 'Quan ly');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles AS r
JOIN permissions AS p ON p.code = 'reports.view'
WHERE r.code = 'manager';

-- Xac nhan da co quyen: ket qua cua Manager phai co reports.view
SELECT r.code AS role_code, p.code AS permission_code
FROM roles AS r
JOIN role_permissions AS rp ON rp.role_id = r.id
JOIN permissions AS p ON p.id = rp.permission_id
WHERE r.code = 'manager' AND p.code = 'reports.view';
