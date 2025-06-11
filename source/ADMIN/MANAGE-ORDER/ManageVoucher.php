<?php
require_once('../../INCLUDE/db_connect.php');
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get_vouchers') {
    $stmt = $conn->prepare("SELECT * FROM Vouchers ORDER BY id DESC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'delete_voucher') {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM Vouchers WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_vouchers') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    foreach ($ids as $id) {
        $stmt = $conn->prepare("DELETE FROM Vouchers WHERE id = ?");
        $stmt->execute([$id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Thêm voucher mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $type = $_POST['type'] ?? '';
    $discount_value = intval($_POST['discount_value'] ?? 0);

    if (!$code || !in_array($type, ['fixed', 'percent']) || $discount_value <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ và đúng thông tin voucher!']);
        exit;
    }

    // Các trường khác
    $min_order_value = intval($_POST['min_order_value'] ?? 0);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // Thêm vào database
    $stmt = $conn->prepare("INSERT INTO Vouchers (code, type, discount_value, min_order_value, start_date, end_date, active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$code, $type, $discount_value, $min_order_value, $start_date, $end_date]);
    echo json_encode(['success' => true]);
    exit;
}
