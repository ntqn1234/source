<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

try {
    // Kiểm tra phương thức yêu cầu là POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Phương thức không được phép"]);
        exit();
    }

    // Thu thập dữ liệu từ biểu mẫu
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $price = isset($_POST['price']) ? (int)$_POST['price'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $new = isset($_POST['new']) ? filter_var($_POST['new'], FILTER_VALIDATE_BOOLEAN) : false;
    $color = isset($_POST['color']) ? trim($_POST['color']) : '';
    $size = isset($_POST['size']) ? trim($_POST['size']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : ''; // Thêm mô tả
    $image = '';

    // Kiểm tra và xử lý upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../UPLOAD/PRODUCT/'; // Thư mục lưu ảnh

        // Kiểm tra định dạng ảnh
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["error" => "Chỉ chấp nhận file JPEG, PNG, GIF, WebP hoặc JPG"]);
            exit();
        }

        // Kiểm tra kích thước ảnh (tối đa 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["error" => "File ảnh quá lớn, tối đa 5MB"]);
            exit();
        }

        // Tạo tên file duy nhất
        $imageName = time() . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', basename($_FILES['image']['name']));
        $imagePath = $uploadDir . $imageName;

        // Di chuyển file ảnh vào thư mục
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            http_response_code(500);
            echo json_encode(["error" => "Lỗi khi tải ảnh lên server"]);
            exit();
        }

        $image = $imagePath; // Lưu đường dẫn ảnh
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Vui lòng chọn ảnh sản phẩm"]);
        exit();
    }

    // Bắt đầu giao dịch
    $conn->beginTransaction();

    // Chèn sản phẩm vào bảng Products
    $sql = "INSERT INTO Products (name, price, quantity, image, category_id, new, color, size, status, description) 
            VALUES (:name, :price, :quantity, :image, :category_id, :new, :color, :size, :status, :description)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':image', $image, PDO::PARAM_STR);
    $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $stmt->bindParam(':new', $new, PDO::PARAM_BOOL);
    $stmt->bindParam(':color', $color, PDO::PARAM_STR);
    $stmt->bindParam(':size', $size, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR); // Thêm mô tả
    $stmt->execute();

    // Hoàn tất giao dịch
    $conn->commit();

    // Trả về phản hồi thành công
    echo json_encode([
        "success" => true,
        "message" => "Thêm sản phẩm thành công"
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Lỗi khi thêm sản phẩm: " . $e->getMessage()]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Lỗi chung: " . $e->getMessage()]);
}
?>