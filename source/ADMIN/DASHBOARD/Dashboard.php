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

// Lấy tổng khách hàng
if ($action === 'getCustomerCount') {
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM Users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['total' => $row['total']]);
    exit();
}

// Lấy tổng sản phẩm (theo Product_Variants)
if ($action === 'getProductCount') {
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM Product_Variants");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['total' => $row['total']]);
    exit();
}

// Tổng đơn hàng
if ($action === 'getOrderCount') {
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM Orders");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['total' => $row['total']]);
    exit();
}

// Đơn hàng chờ xử lý
if ($action === 'getPendingOrderCount') {
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM Orders WHERE status = 'pending'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['total' => $row['total']]);
    exit();
}

// 5 đơn hàng mới nhất
if ($action === 'getRecentOrders') {
    $stmt = $conn->query("
        SELECT o.order_id, o.full_name, o.status, o.total_amount
        FROM Orders o
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['orders' => $orders]);
    exit();
}

// Tổng doanh thu theo tháng (line chart)
if ($action === 'getRevenueByMonth') {
    $stmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%m/%Y') AS month, 
               SUM(total_amount) - SUM(discount_amount) AS revenue
        FROM Orders
        GROUP BY month
        ORDER BY MIN(created_at) ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit();
}

// Thu chi từng tháng (bar chart)
if ($action === 'getIncomeExpenseByMonth') {
    $stmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%m/%Y') AS month,
               SUM(total_amount) AS income,
               SUM(discount_amount) AS expense
        FROM Orders
        GROUP BY month
        ORDER BY MIN(created_at) ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit();
}

// Nếu không có action hợp lệ
echo json_encode(['success' => false, 'error' => 'No action']);
$conn = null;
exit();
