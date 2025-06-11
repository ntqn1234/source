<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

try {
    // Lấy dữ liệu từ form
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
   
    // Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($gender)) {
        $response['message'] = 'Vui lòng điền đầy đủ thông tin !';
        echo json_encode($response);
        exit;
    }

    // Kiểm tra email đã tồn tại
    $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $response['message'] = 'Email đã được sử dụng. Vui lòng chọn email khác !';
        echo json_encode($response);
        exit;
    }

    //Kiểm tra mật khẩu
    if (strlen($password) < 6) {
        $response['message'] = 'Mật khẩu phải có ít nhất 6 ký tự. Vui lòng nhập lại !';
        echo json_encode($response);
        exit;
    }

    // Lưu vào database
    $stmt = $conn->prepare("INSERT INTO Users (email, password, name, gender) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $password, $fullname, $gender]);

    $response['status'] = 'success';
} catch (Exception $e) {
    $response['message'] = 'Lỗi: ' . $e->getMessage();
}

echo json_encode($response);
?>
