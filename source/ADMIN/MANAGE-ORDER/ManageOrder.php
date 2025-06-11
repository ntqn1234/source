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

// Xử lý hành động lấy danh sách đơn hàng
if ($action === 'get_orders') {
    try {
        $sql = "
            SELECT 
                o.order_id AS id,
                o.order_id AS order_code,
                o.full_name AS customer_name,
                o.created_at AS order_date,
                o.total_amount AS total,
                o.status
            FROM orders o
            ORDER BY o.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $orders]);
        $conn = null; // Đóng kết nối PDO
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        $conn = null;
        exit();
    }
}

// Xử lý hành động xóa nhiều đơn hàng cùng lúc
if ($action === 'delete_order') {
    if (isset($_POST['ids']) && is_array($_POST['ids'])) {
        // Xóa nhiều đơn hàng
        foreach ($_POST['ids'] as $orderId) {
            // Xóa Order_Items
            $sqlItems = "DELETE FROM Order_Items WHERE order_id = :order_id";
            $stmtItems = $conn->prepare($sqlItems);
            $stmtItems->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmtItems->execute();

            // Xóa orders
            $sql = "DELETE FROM orders WHERE order_id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->execute();
        }
        echo json_encode(['success' => true, 'message' => 'Các đơn hàng đã được xóa.']);
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
if ($action === 'edit_order') {
    if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        $conn = null;
        exit();
    }

    $orderId = $_POST['order_id'];
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    try {
        $sql = "UPDATE orders SET status = :status WHERE order_id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Order not found or no changes made']);
        }
        $conn = null; // Đóng kết nối PDO
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        $conn = null;
        exit();
    }
}

// Nếu không có hành động hợp lệ
echo json_encode(['success' => false, 'error' => 'No action']);
$conn = null;
exit();
