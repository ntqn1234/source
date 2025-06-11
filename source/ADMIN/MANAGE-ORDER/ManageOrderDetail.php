<?php
session_start();
require_once('../../INCLUDE/db_connect.php');
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Kiểm tra kết nối PDO
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Xử lý hành động lấy danh sách chi tiết đơn hàng 
if ($action === 'get_order_detail' && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    try {
        // Lấy thông tin đơn hàng
        $sqlOrder = "SELECT * FROM orders WHERE order_id = :order_id";
        $stmtOrder = $conn->prepare($sqlOrder);
        $stmtOrder->bindParam(':order_id', $order_id, PDO::PARAM_STR);
        $stmtOrder->execute();
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

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

// Xử lý hành động xóa đơn hàng
if ($action === 'delete_order') {
    if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        $conn = null;
        exit();
    }

    $orderId = $_POST['order_id'];

    try {
        // Xóa sản phẩm trong đơn hàng trước (nếu có)
        $sqlItems = "DELETE FROM Order_Items WHERE order_id = :order_id";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bindParam(':order_id', $orderId, PDO::PARAM_STR);
        $stmtItems->execute();

        // Xóa đơn hàng
        $sql = "DELETE FROM orders WHERE order_id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Order not found or already deleted']);
        }
        $conn = null;
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        $conn = null;
        exit();
    }
}

// Xử lý hành động sửa đơn hàng
if ($_POST['action'] === 'update_order') {
    // Cập nhật thông tin đơn hàng
    $stmt = $conn->prepare("
    UPDATE Orders SET
      full_name = ?, phone = ?, email = ?,
      specific_address = ?, district = ?, province = ?,
      payment_method = ?, status = ?
    WHERE order_id = ?
  ");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['specific_address'],
        $_POST['district'],
        $_POST['province'],
        $_POST['payment_method'],
        $_POST['status'],
        $_POST['order_id']
    ]);

    echo json_encode(['success' => true]);
    exit;
}


// Nếu không có hành động hợp lệ
echo json_encode(['success' => false, 'error' => 'No action']);
$conn = null;
exit();
