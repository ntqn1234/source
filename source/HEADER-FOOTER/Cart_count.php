<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode([
        "item_count" => 0,
        "message" => "Đăng nhập để xem sản phẩm"
    ]);
    exit();
}

// Lấy user_id từ session (có thể là user hoặc admin)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['admin_id'];

try {
    // Truy vấn tổng số sản phẩm trong giỏ hàng
    $sql = "SELECT SUM(quantity) AS total_items
            FROM Cart
            WHERE user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_items = $result['total_items'] ? (int)$result['total_items'] : 0;

    // Tạo thông báo dựa trên số lượng sản phẩm
    $message = $total_items > 0 ? "Bạn có $total_items sản phẩm trong giỏ hàng" : "Giỏ hàng của bạn trống.";

    // Trả về dữ liệu dạng JSON
    echo json_encode([
        "item_count" => $total_items,
        "message" => $message
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "item_count" => 0,
        "message" => "Lỗi khi tải giỏ hàng: " . $e->getMessage()
    ]);
}
?>