<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once '../INCLUDE/db_connect.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// Kiểm tra có token và email không
if (empty($email) || empty($token)) {
    die('Liên kết không hợp lệ.');
}

// Truy vấn để kiểm tra token
$stmt = $conn->prepare("SELECT * FROM Users WHERE email = :email AND reset_token = :token AND reset_token_expiry > NOW()");
$stmt->execute([
    ':email' => $email,
    ':token' => $token
]);

if ($stmt->rowCount() === 0) {
    die('Liên kết không hợp lệ hoặc đã hết hạn.');
}

// Nếu hợp lệ, hiển thị form đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Mật khẩu không khớp.";
    } else {
        $update = $conn->prepare("UPDATE Users SET password = :pass, reset_token = NULL, reset_token_expiry = NULL WHERE email = :email");
        $update->execute([
            ':pass' => $newPassword, // Mật khẩu mới
            ':email' => $email
        ]);

        $success = "Mật khẩu đã được cập nhật thành công. Bạn có thể đăng nhập lại.";
        // Chuyển hướng về trang đăng nhập sau 3 giây
        header("refresh:3;url=Login.html");
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link rel="stylesheet" href="../HEADER-FOOTER/Footer.css" />
    <link rel="stylesheet" href="../HEADER-FOOTER/Header.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <style>
        body {
            background-color: #fef6e4;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .reset-container {
            max-width: 400px;
            margin: 60px auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(17%);
            cursor: pointer;
            color: #888;
            font-size: 18px;
        }

        .reset-btn {
            width: 100%;
            padding: 10px;
            background-color: #e56332;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
        }

        .message {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }

        .message.success {
            color: green;
        }

        .message.error {
            color: red;
        }
    </style>
</head>

<body>
    <div class="container-fluid cart-container">
        <div id="header-placeholder"></div>
        <script>
            $(function() {
                $("#header-placeholder").load("../HEADER-FOOTER/Header.html");
                $("#footer-placeholder").load("../HEADER-FOOTER/Footer.html");
            });
        </script>
        <div class="reset-container">
            <h2>ĐẶT LẠI MẬT KHẨU</h2>

            <?php if (!empty($error)): ?>
                <div class="message error"><?= $error ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="message success"><?= $success ?></div>
            <?php else: ?>
                <form method="post">
                    <div class="form-group position-relative">
                        <label>Mật khẩu mới:</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                        <i class="fa fa-eye toggle-password" toggle="#new_password"></i>
                    </div>

                    <div class="form-group position-relative">
                        <label>Nhập lại mật khẩu:</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <i class="fa fa-eye toggle-password" toggle="#confirm_password"></i>
                    </div>

                    <button type="submit" class="reset-btn">CẬP NHẬT</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $(".toggle-password").click(function() {
                const target = $(this).attr("toggle");
                const input = $(target);
                const type = input.attr("type") === "password" ? "text" : "password";
                input.attr("type", type);
                $(this).toggleClass("fa-eye fa-eye-slash");
            });
        });
    </script>

    <div id="footer-placeholder"></div>
</body>

</html>