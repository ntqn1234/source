<?php
$file = __DIR__ . '/vendor/autoload.php';
if (!file_exists($file)) {
    die("Không tìm thấy vendor/autoload.php tại: $file");
}
require_once $file;

echo "Autoload loaded OK<br>";

require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('675185309299-tmor33mdogbef9cb5fdj56lrl40m47qc.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/LOGIN/google-callback.php');
$client->addScope("email");
$client->addScope("profile");

// Chuyển hướng người dùng đến Google để đăng nhập
header('Location: ' . $client->createAuthUrl());
exit;
