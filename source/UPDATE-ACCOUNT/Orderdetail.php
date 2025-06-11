<?php
session_start();
require_once('../INCLUDE/db_connect.php');
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Người dùng chưa đăng nhập']);
    exit();
}
$user_id = $_SESSION['user_id'];

// Kiểm tra kết nối PDO
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Không thể kết nối đến cơ sở dữ liệu']);
    exit();
}

// Lấy chi tiết đơn hàng
if ($action === 'get_order_detail' && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    try {
        // Lấy thông tin đơn hàng, chỉ cho phép xem đơn hàng của chính user
        $sqlOrder = "SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id";
        $stmtOrder = $conn->prepare($sqlOrder);
        $stmtOrder->bindParam(':order_id', $order_id, PDO::PARAM_STR);
        $stmtOrder->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtOrder->execute();
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này']);
            $conn = null;
            exit();
        }

        // Lấy danh sách sản phẩm trong đơn hàng
        $sqlItems = "
            SELECT 
                oi.*, 
                p.name AS product_name, 
                p.image AS product_image, 
                p.color, 
                p.size, 
                c.name AS category_name,
                v.variant_name, 
                v.image AS variant_image, 
                v.price AS variant_price
            FROM Order_Items oi
            LEFT JOIN Products p ON oi.product_id = p.id
            LEFT JOIN Categories c ON p.category_id = c.id
            LEFT JOIN Product_Variants v ON oi.variant_id = v.id
            WHERE oi.order_id = :order_id
        ";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bindParam(':order_id', $order_id, PDO::PARAM_STR);
        $stmtItems->execute();
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
        $conn = null;
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        $conn = null;
        exit();
    }
}

// Nếu không có action hợp lệ
echo json_encode(['success' => false, 'error' => 'No action']);
$conn = null;
exit();
