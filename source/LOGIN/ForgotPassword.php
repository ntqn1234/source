<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

// Nhận dữ liệu từ AJAX
$email = $_POST['email'] ?? '';

// Kiểm tra email không rỗng và hợp lệ
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email không hợp lệ."
    ]);
    exit;
}

try {
    // Kiểm tra xem email có tồn tại trong CSDL không
    $stmt = $conn->prepare("SELECT id FROM Users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Email không tồn tại trong hệ thống."
        ]);
        exit;
    }

    // Tạo token reset ngẫu nhiên và thời hạn 15 phút
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

    // Lưu token và thời hạn vào DB
    $update = $conn->prepare("UPDATE Users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email");
    $update->execute([
        ':token' => $token,
        ':expiry' => $expiry,
        ':email' => $email
    ]);

    // Tạo link reset 
    $reset_link = "http://localhost:8000/LOGIN/ResetPassword.php?token=$token&email=$email";

    // Gửi mail bằng PHPMailer
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'thanhlogn0@gmail.com';
    $mail->Password   = 'jphs esst wtgh teln';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('thanhlogn0@gmail.com', 'Len Lab');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Khôi phục mật khẩu Len Lab';
    $mail->Body = "
        <p>Chào bạn,</p>
        <p>Nhấn vào liên kết bên dưới để đặt lại mật khẩu:</p>
        <p><a href='$reset_link'>$reset_link</a></p>
        <p>Liên kết có hiệu lực trong 15 phút.</p>
        <p>Trân trọng,<br>Len Lab</p>
    ";

    $mail->send();

    echo json_encode([
        'status' => 'success',
        'message' => 'Đã gửi yêu cầu đặt lại mật khẩu. Vui lòng kiểm tra email.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Không thể gửi email. Lỗi: ' . $mail->ErrorInfo
    ]);
}
