
<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Vui lòng đăng nhập để gửi đánh giá"]);
    exit;
}

try {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $user_id = $_SESSION['user_id'];

    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(["error" => "Điểm đánh giá phải từ 1 đến 5"]);
        exit;
    }

    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID sản phẩm không hợp lệ"]);
        exit;
    }

    $sql = "INSERT INTO Comments (user_id, product_id, rating, comment) 
            VALUES (:user_id, :product_id, :rating, :comment)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(["message" => "Đánh giá đã được gửi"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Lỗi khi gửi đánh giá: " . $e->getMessage()]);
}
?>
