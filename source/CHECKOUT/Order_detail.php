<?php
session_start();
header('Content-Type: application/json');
require_once '../INCLUDE/db_connect.php';


// Lấy dữ liệu đơn hàng tạm từ session để hiển thị
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['order_preview'])) {
        echo json_encode(['success' => false, 'error' => 'Không có đơn hàng trong session']);
        exit;
    }
    $order = $_SESSION['order_preview'];

    // Ưu tiên lấy user từ database nếu có đăng nhập, nếu không thì lấy từ order
    $user = null;
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT name, email, name FROM Users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user_db = $stmt->fetch(PDO::FETCH_ASSOC);
        $user = [
            'full_name' => $user_db['name'],
            'email' => $user_db['email'],
            'phone' => $user_db['phone'] ?? ''
        ];
    }
    if (!$user) {
        $user = [
            'full_name' => $order['full_name'] ?? '',
            'email' => $order['email'] ?? '',
            'phone' => $order['phone'] ?? ''
        ];
    }

    $response = [
        'success' => true,
        'user' => $user,
        'order' => $order,
    ];

    echo json_encode($response);
    exit;
}

$rawData = file_get_contents("php://input");
$postData = json_decode($rawData, true);

// Khi nhấn nút "Xác nhận đơn hàng", lưu đơn hàng vào database
$order_id = $postData['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy order_id từ frontend gửi lên làm việc.']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($postData['action']) && $postData['action'] === 'complete_order') {
        if (!isset($_SESSION['order_preview'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy thông tin đơn hàng từ phiên làm việc.'
            ]);
            exit;
        }

        if (!isset($_SESSION['order_preview'])) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin đơn hàng trong phiên']);
            exit;
        }

        $orderData = $_SESSION['order_preview'];
        $payment_method = $postData['payment_method'] ?? 'cod';

        $rawData = file_get_contents("php://input");
        $postData = json_decode($rawData, true);

        try {
            $stmt = $conn->prepare("INSERT INTO Orders (
                order_id, user_id, email, full_name, phone,
                province, district, specific_address,
                shipping_method, shipping_fee,
                discount_amount, total_amount,
                payment_method, order_note
            ) VALUES (
                :order_id, :user_id, :email, :full_name, :phone,
                :province, :district, :specific_address,
                :shipping_method, :shipping_fee,
                :discount_amount, :total_amount,
                :payment_method, :note
            )");

            $stmt->execute([
                ':order_id' => $order_id,
                ':user_id' => $orderData['user_id'],
                ':email' => $orderData['email'],
                ':full_name' => $orderData['full_name'],
                ':phone' => $orderData['phone'],
                ':province' => $orderData['province'],
                ':district' => $orderData['district'],
                ':specific_address' => $orderData['specific_address'],
                ':shipping_method' => $orderData['shipping_method'],
                ':shipping_fee' => $orderData['shipping_fee'],
                ':discount_amount' => $orderData['discount_amount'],
                ':total_amount' => $orderData['total_amount'],
                ':payment_method' => $payment_method,
                ':note' => $orderData['note']
            ]);

            // Thêm các sản phẩm trong giỏ hàng vào Order_Items
            $cart_items = $orderData['cart_items'] ?? [];
            foreach ($cart_items as $item) {
                $stmtItem = $conn->prepare("INSERT INTO Order_Items (order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
                $stmtItem->execute([
                    $order_id,
                    $item['product_id'],
                    $item['variant_id'] ?? null,
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // Xóa giỏ hàng trong database
            $user_id = $orderData['user_id'] ?? null;
            if ($user_id) {
                $stmt = $conn->prepare("DELETE FROM Cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }

            // Xóa giỏ hàng và đơn hàng tạm trong session
            echo json_encode(['success' => true]);
            unset($_SESSION['cart']);
            unset($_SESSION['order_preview']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Thiếu action hoặc action không hợp lệ']);
        exit;
    }
}
