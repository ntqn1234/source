<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

try {
    // Truy vấn lấy 4 sản phẩm đầu
    $sql = "SELECT id, name, image
            FROM Products 
            ORDER BY id ASC
            LIMIT 4";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = [];

    // Lấy dữ liệu và chuyển thành mảng
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = [
            "id" => (int)$row["id"],
            "name" => $row["name"],
            "image" => $row["image"]
        ];
    }

    // Trả về dữ liệu dạng JSON với khóa "products"
    echo json_encode(["products" => $products]);

} catch (PDOException $e) {
    // Trả về lỗi nếu truy vấn thất bại
    http_response_code(500);
    echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
}
?>