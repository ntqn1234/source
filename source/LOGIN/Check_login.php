<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

$response = ['is_logged_in' => false, 'user_name' => ''];

if (isset($_SESSION['user_id'])) {
    $response['is_logged_in'] = true;
    $response['user_name'] = $_SESSION['user_name'];
} elseif (isset($_SESSION['admin_id'])) {
    $response['is_logged_in'] = true;
    $response['user_name'] = $_SESSION['admin_name'] . ' (Admin)';
}
// Kiểm tra tài khoản Google
elseif (isset($_SESSION['user_email'])) {
    // Truy vấn để lấy thông tin người dùng từ email
    $stmt = $conn->prepare("SELECT id, name FROM Users WHERE email = ?");
    $stmt->execute([$_SESSION['user_email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $response['is_logged_in'] = true;
        $response['user_name'] = $user['name'] . ' (Google)';
        // Lưu user_id và user_name vào session để đồng bộ với logic mua hàng
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
    }
}

echo json_encode($response);
