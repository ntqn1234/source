<?php
require_once('../../INCLUDE/db_connect.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $type = $_POST['type'] ?? '';
    $discount_value = intval($_POST['discount_value'] ?? 0);
    $min_order_value = intval($_POST['min_order_value'] ?? 0);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // Kiểm tra dữ liệu bắt buộc
    if (!$code || !in_array($type, ['fixed', 'percent']) || $discount_value <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ và đúng thông tin voucher!']);
        exit;
    }

    // Thêm vào database
    $stmt = $conn->prepare("INSERT INTO Vouchers (code, type, discount_value, min_order_value, start_date, end_date, active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $result = $stmt->execute([$code, $type, $discount_value, $min_order_value, $start_date, $end_date]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể thêm voucher.']);
    }
    exit;
}
