<?php

session_start();
require_once '../../INCLUDE/db_connect.php';
header('Content-Type: application/json');

// Xử lý các hành động dựa trên tham số 'action'
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($action === 'get_customers') {
    try {
        
        $sql = "SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.gender,
            COUNT(o.order_id) AS total_orders,
            SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END) AS total_spent
            FROM Users u
            LEFT JOIN Orders o ON u.id = o.user_id
            GROUP BY u.id, u.name, u.email, u.gender
            ORDER BY u.id ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Trả về danh sách khách hàng
        echo json_encode([
            "success" => true,
            "data" => $customers
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
    }
} else if ($action === 'delete_customer') {
    try {
        
        // Lấy customer_id từ request
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customer_id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu hoặc không hợp lệ customer_id"]);
            exit();
        }

       // Xóa các bản ghi trong Cart liên quan đến khách hàng
        $sqlCart = "DELETE FROM Cart WHERE user_id = :customer_id";
        $stmtCart = $conn->prepare($sqlCart);
        $stmtCart->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmtCart->execute();

        // Xóa các bản ghi trong Order_Items liên quan đến đơn hàng của khách hàng
        $sqlOrderItems = "DELETE FROM Order_Items WHERE order_id IN (SELECT id FROM Orders WHERE user_id = :customer_id)";
        $stmtOrderItems = $conn->prepare($sqlOrderItems);
        $stmtOrderItems->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmtOrderItems->execute();

        // Xóa các đơn hàng của khách hàng trong Order
        $sqlOrders = "DELETE FROM Orders WHERE user_id = :customer_id";
        $stmtOrders = $conn->prepare($sqlOrders);
        $stmtOrders->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmtOrders->execute();

        // Xóa các bình luận liên quan đến sản phẩm trong Comment
        $sqlComments = "DELETE FROM Comments WHERE user_id = :customer_id";
        $stmtComments = $conn->prepare($sqlComments);
        $stmtComments->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmtComments->execute();

        // Xóa khách hàng
        $sql = "DELETE FROM Users WHERE id = :customer_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();

        // Trả về phản hồi thành công
        echo json_encode(["success" => true, "message" => "Xóa khách hàng thành công"]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Lỗi khi xóa khách hàng: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Hành động không hợp lệ"]);
}
?>