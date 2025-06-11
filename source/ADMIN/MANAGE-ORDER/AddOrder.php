<?php
session_start();
$user_id = 11; // admin tạm thời
require_once('../../INCLUDE/db_connect.php');
header('Content-Type: application/json');

// Phần lưu sản phẩm vào Cart và tính tổng tiền
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['save'])) {
    $customer = $_POST['customer'] ?? [];
    $products = $_POST['products'];

    // Xoá giỏ hàng cũ của admin
    $stmt = $conn->prepare("DELETE FROM Cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    foreach ($products as $item) {
        $code = $item['code'];
        $qty = (int)$item['quantity'];

        // Tìm product_id và variant_id
        $stmt = $conn->prepare("
            SELECT p.id AS product_id, pv.id AS variant_id
            FROM Products p
            LEFT JOIN Product_Variants pv ON pv.product_id = p.id
            WHERE p.name = ? OR p.id = ? LIMIT 1
        ");
        $stmt->execute([$code, $code]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$found) {
            echo json_encode(['success' => false, 'message' => "Không tìm thấy mã sản phẩm: $code"]);
            exit;
        }

        $product_id = $found['product_id'];
        $variant_id = $found['variant_id'];

        $stmt = $conn->prepare("INSERT INTO Cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $variant_id, $qty]);
    }

    // Tính tổng tiền
    $stmt = $conn->prepare("
        SELECT c.quantity, COALESCE(pv.price, p.price) AS price
        FROM Cart c
        JOIN Products p ON c.product_id = p.id
        LEFT JOIN Product_Variants pv ON c.variant_id = pv.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);

    $total = 0;
    while ($row = $stmt->fetch()) {
        $total += $row['quantity'] * $row['price'];
    }

    echo json_encode([
        'success' => true,
        'total' => number_format($total, 0, ',', '.') . ' đ'
    ]);
    exit;
}

// Khi nhấn "Lưu lại" → tạo đơn hàng thực tế
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['save'] == true) {
    $customer = $_POST['customer'];

    $stmt = $conn->prepare("SELECT * FROM Cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($cartItems) == 0) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
        exit;
    }

    // Tính tổng tiền từ Cart
    $stmt = $conn->prepare("
        SELECT c.quantity, COALESCE(pv.price, p.price) AS price
        FROM Cart c
        JOIN Products p ON c.product_id = p.id
        LEFT JOIN Product_Variants pv ON c.variant_id = pv.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);

    $total = 0;
    while ($row = $stmt->fetch()) {
        $total += $row['quantity'] * $row['price'];
    }

    // Tạo cart items
    $cartItems = [];
    $total = 0;

    foreach ($_POST['products'] as $p) {
        $variant_id = (int)($p['variant_id'] ?? $p['code'] ?? 0); // fallback nếu dùng 'code'
        $quantity = (int)$p['quantity'];

        if ($variant_id <= 0 || $quantity <= 0) continue;

        // Truy xuất product_id từ variant_id
        $stmt = $conn->prepare("SELECT product_id, price FROM Product_Variants WHERE id = ?");
        $stmt->execute([$variant_id]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($variant) {
            $cartItems[] = [
                'product_id' => $variant['product_id'],
                'variant_id' => $variant_id,
                'quantity' => $quantity,
                'price' => $variant['price']
            ];
            $total += $variant['price'] * $quantity;
        }
    }


    // Tạo order_id
    $order_date = date('dmY');
    if (!isset($_SESSION['order_serial_date']) || $_SESSION['order_serial_date'] !== $order_date) {
        $_SESSION['order_serial_date'] = $order_date;
        $_SESSION['order_serial'] = 1;
    } else {
        $_SESSION['order_serial']++;
    }
    $order_id = $order_date . str_pad($_SESSION['order_serial'], 4, '0', STR_PAD_LEFT) . 'S';

    // Thêm vào bảng Orders
    $stmt = $conn->prepare("
    INSERT INTO Orders (
        order_id, user_id, email, full_name, phone,
        province, district, specific_address,
        shipping_fee, payment_method,
        discount_amount, total_amount,
        status, order_note, shipping_method, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
    $stmt->execute([
        $order_id,
        $user_id,
        $customer['email'],
        $customer['name'],
        $customer['phone'],
        $customer['province'] ?? null,
        $customer['district'] ?? null,
        $customer['specific_address'] ?? null,
        $customer['shipping_fee'] ?? 0,
        $customer['payment_method'] ?? 'cod',
        $customer['discount_amount'] ?? 0,
        $total - ($customer['discount_amount'] ?? 0) + ($customer['shipping_fee'] ?? 0),
        $customer['status'],
        $customer['note'],
        $customer['shipping_method'] ?? null
    ]);

    // Ghi từng sản phẩm vào Order_Items
    foreach ($cartItems as $item) {
        $stmt = $conn->prepare("
            SELECT COALESCE(pv.price, p.price) AS price
            FROM Products p
            LEFT JOIN Product_Variants pv ON pv.id = ?
            WHERE p.id = ?
        ");
        $stmt->execute([$item['variant_id'], $item['product_id']]);
        $price = $stmt->fetchColumn();

        $stmt = $conn->prepare("
            INSERT INTO Order_Items (order_id, product_id, variant_id, quantity, price)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['variant_id'],
            $item['quantity'],
            $price
        ]);
    }

    // Xoá Cart sau khi lưu đơn hàng
    $stmt = $conn->prepare("DELETE FROM Cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true, 'order_id' => $order_id]);
    exit;
}
