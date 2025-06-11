<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => '', 'is_admin' => false];

try {
    // Lấy dữ liệu từ form
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);


    // Kiểm tra trong bảng Admins (quản lý)
    $stmt = $conn->prepare("SELECT id, name, password FROM Admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) {
        // Tài khoản là admin
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $response['status'] = 'success';
        $response['is_admin'] = true;

        echo json_encode($response);
        exit;
    }


    // Kiểm tra trong bảng Users (khách hàng)
    $stmt = $conn->prepare("SELECT id, name, password FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        // Tài khoản là user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $response['status'] = 'success';
        $response['is_admin'] = false;

        echo json_encode($response);
        exit;
    }
    // Nếu không tìm thấy trong cả hai bảng hoặc mật khẩu sai
    $response['message'] = 'Email hoặc mật khẩu không đúng! Hãy <a href="../REGISTER/Register.html">ĐĂNG KÝ</a> nếu bạn chưa có tài khoản';
    echo json_encode($response);
    exit;
} catch (Exception $e) {
    $response['message'] = 'Lỗi: ' . $e->getMessage();
    echo json_encode($response);
}
