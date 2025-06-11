<?php
session_start();
require_once '../../INCLUDE/db_connect.php';
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
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

    // Xác thực dữ liệu
    if (empty($name) || empty($email) || empty($password) || empty($gender)) {
        http_response_code(400);
        echo json_encode(["error" => "Vui lòng điền đầy đủ các trường bắt buộc."]);
        exit();
    }

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Địa chỉ email không hợp lệ."]);
        exit();
    }

    // Kiểm tra email đã tồn tại
    $sql = "SELECT COUNT(*) FROM Users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Email đã được sử dụng."]);
        exit();
    }

    // Kiểm tra giá trị gender hợp lệ
    $valid_genders = ['male', 'female', 'other'];
    if (!in_array($gender, $valid_genders)) {
        http_response_code(400);
        echo json_encode(["error" => "Giới tính không hợp lệ."]);
        exit();
    }

    // Bắt đầu giao dịch
    $conn->beginTransaction();

    // Chèn khách hàng vào bảng Users
    $sql = "INSERT INTO Users (name, email, password, gender) 
            VALUES (:name, :email, :password, :gender)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
    $stmt->execute();

    // Hoàn tất giao dịch
    $conn->commit();

    // Trả về phản hồi thành công
    echo json_encode([
        "success" => true,
        "message" => "Thêm khách hàng thành công"
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Lỗi khi thêm khách hàng: " . $e->getMessage()]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Lỗi chung: " . $e->getMessage()]);
}
?>