<?php
session_start();
session_destroy(); // Hủy toàn bộ session
header('Location: ../LOGIN/Login.html'); // Chuyển hướng về trang đăng nhập
exit();
?>