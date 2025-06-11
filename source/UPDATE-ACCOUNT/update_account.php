<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

try {
    // Kiểm tra session user_id
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Bạn chưa đăng nhập! Vui lòng đăng nhập để tiếp tục.';
        echo json_encode($response);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Ưu tiên xử lý action lấy đơn hàng trước
    if (isset($_GET['action']) && $_GET['action'] === 'get_orders') {
        try {
            $sql = "
                SELECT 
                    o.order_id AS id,
                    o.order_id AS order_code,
                    o.full_name AS customer_name,
                    o.created_at AS order_date,
                    o.total_amount AS total,
                    o.status
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $orders]);
            $conn = null; // Đóng kết nối PDO
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            $conn = null;
            exit();
        }
    }

    // Xử lý yêu cầu GET để lấy thông tin tài khoản
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("SELECT name, gender, email, password FROM Users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $response['status'] = 'success';
            $response['data'] = [
                'name' => $user['name'],
                'gender' => $user['gender'],
                'email' => $user['email'],
                'password' => $user['password']
            ];
        } else {
            $response['message'] = 'Không tìm thấy thông tin tài khoản!';
        }
        echo json_encode($response);
        exit;
    }

    // Xử lý yêu cầu POST để cập nhật thông tin
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['user-name']);
        $gender = trim($_POST['gender']);
        $email = trim($_POST['user-email']);
        $password = trim($_POST['user-password']);

        // Kiểm tra dữ liệu đầu vào
        if (empty($name) || empty($gender) || empty($email) || empty($password)) {
            $response['message'] = 'Vui lòng điền đầy đủ thông tin!';
            echo json_encode($response);
            exit;
        }

        // Kiểm tra email không trùng với người dùng khác
        $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $response['message'] = 'Email đã được sử dụng!';
            echo json_encode($response);
            exit;
        }

        // Cập nhật thông tin vào bảng Users
        $stmt = $conn->prepare("UPDATE Users SET name = ?, gender = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $gender, $email, $password, $user_id]);

        $response['status'] = 'success';
        $response['message'] = 'Cập nhật thông tin thành công!';
        echo json_encode($response);
        exit;
    }

    // Nếu không phải GET hoặc POST
    $response['message'] = 'Yêu cầu không hợp lệ!';
    echo json_encode($response);
    exit;
} catch (Exception $e) {
    $response['message'] = 'Lỗi: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}
