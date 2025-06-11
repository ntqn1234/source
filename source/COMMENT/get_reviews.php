<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

try {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    if ($product_id <= 0) {
        throw new Exception("ID sản phẩm không hợp lệ");
    }
    $sql = "SELECT c.id, c.user_id, u.name AS user_name, c.rating, c.comment
            FROM Comments c
            JOIN Users u ON c.user_id = u.id
            WHERE c.product_id = :product_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reviews);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
}
?>