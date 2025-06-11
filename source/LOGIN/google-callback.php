<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require_once '../INCLUDE/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$client = new Google_Client();
$client->setClientId('675185309299-tmor33mdogbef9cb5fdj56lrl40m47qc.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/LOGIN/google-callback.php');

$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        die("Lỗi khi lấy access token từ Google: " . htmlspecialchars($token['error']));
    }

    $client->setAccessToken($token);

    // Lấy thông tin user từ Google
    $oauth = new Google_Service_Oauth2($client);
    $user_info = $oauth->userinfo->get();

    $email = $user_info->email;
    $name = $user_info->name;

    // Kiểm tra user đã tồn tại chưa
    $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        // Đăng nhập
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $email;
    } else {
        // Đăng ký
        $default_password = password_hash("DangNhapGG123", PASSWORD_DEFAULT);
        $gender = "other";

        $insert = $conn->prepare("INSERT INTO Users (email, password, name, gender) VALUES (?, ?, ?, ?)");
        $insert->execute([$email, $default_password, $name, $gender]);

        // Lấy ID của người dùng vừa tạo
        $user_id = $conn->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        // Gửi email thông báo
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'thanhlogn0@gmail.com';
            $mail->Password   = 'jphs esst wtgh teln';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('thanhlogn0@gmail.com', 'Len Lab');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Chào mừng bạn đến với Len Lab!';
            $mail->Body = "<h1>Chào $name!</h1><p>Tài khoản mua hàng của bạn đã được tạo thành công.</>
            <p>Chúc bạn có những trải nghiệm mua sắm tuyệt vời tại LenLab</p>
            <p>Trân trọng,<br>Len Lab</p>";
            $mail->send();
        } catch (Exception $e) {
            error_log("Email không thể gửi được. Error: {$mail->ErrorInfo}");
        }
    }

    // Điều hướng sang trang chính
    header("Location: ../LANDING-PAGE/landingpage.html"); // hoặc profile.php, dashboard.php...
    exit;
} else {
    echo "Không có mã code từ Google!";
}
