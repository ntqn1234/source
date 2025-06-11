<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');


try {
    // Lấy từ khóa tìm kiếm từ query string (áp dụng khi search)
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    // Truy vấn lấy tất cả sản phẩm
    $sql = "SELECT p.id AS id, p.name AS name, p.price AS price,c.name AS category,p.new AS new,p.image AS image
            FROM Products p INNER JOIN Categories c ON p.category_id = c.id";

    if ($keyword) {
        $sql .= " WHERE p.name LIKE :keyword OR c.name LIKE :keyword";
    }

    $stmt = $conn->prepare($sql);

     if ($keyword) {
        $searchTerm = "%" . $keyword . "%";
        $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
    }

    $stmt->execute();

    $products = [];

    // Lấy dữ liệu và chuyển thành mảng
    while ($row = $stmt->fetch()) {
        $products[] = [
            "id" => (int)$row["id"],
            "name" => $row["name"],
            "price" => (int)$row["price"],
            "category" => $row["category"],
            "new" => (bool)$row["new"],
            "image" => $row["image"]
        ];
    }

    // Trả về dữ liệu dạng JSON
    echo json_encode($products);

} catch (PDOException $e) {
    // Trả về lỗi nếu truy vấn thất bại
    http_response_code(500);
    echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
}

?>