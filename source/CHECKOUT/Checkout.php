<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để tiếp tục.']);
        exit;
    }
    // Lấy dữ liệu từ form và giỏ hàng gửi lên
    $email = $data['email'] ?? '';
    $full_name = $data['full_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $province = $data['province'] ?? '';
    $district = $data['district'] ?? '';
    $specific_address = $data['specific_address'] ?? '';
    $shipping_method = $data['shipping_method'];
    $note = $data['note'] ?? '';
    $voucher_code = $data['voucher_code'] ?? null;
    $cart_items = $data['cart_items'] ?? [];
    $total_amount = $data['total_amount'] ?? 0;

    // Kiểm tra giỏ hàng gửi lên
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'error' => 'Giỏ hàng trống.']);
        exit;
    }

    // Tính lại tổng tiền từ cart_items
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Tính phí vận chuyển
    $south = [
        "TP.HCM",
        "TP. Hồ Chí Minh",
        "Cần Thơ",
        "Vũng Tàu",
        "Đồng Nai",
        "Bình Dương",
        "Long An",
        "Tiền Giang",
        "Bến Tre",
        "An Giang",
        "Bạc Liêu",
        "Cà Mau",
        "Đồng Tháp",
        "Hậu Giang",
        "Kiên Giang",
        "Sóc Trăng",
        "Trà Vinh",
        "Vĩnh Long",
        "Bình Phước",
        "Tây Ninh"
    ];
    $central = [
        "Đà Nẵng",
        "Khánh Hòa",
        "Quảng Nam",
        "Bình Định",
        "Thừa Thiên Huế",
        "Thanh Hóa",
        "Nghệ An",
        "Hà Tĩnh",
        "Quảng Bình",
        "Quảng Trị",
        "Phú Yên",
        "Ninh Thuận",
        "Bình Thuận",
        "Đắk Lắk",
        "Gia Lai",
        "Lâm Đồng",
        "Kon Tum",
        "Đắk Nông"
    ];
    $north = [
        "Hà Nội",
        "Hải Phòng",
        "Quảng Ninh",
        "Lào Cai",
        "Lạng Sơn",
        "Bắc Ninh",
        "Hòa Bình",
        "Sơn La",
        "Điện Biên",
        "Lai Châu",
        "Yên Bái",
        "Hà Giang",
        "Tuyên Quang",
        "Cao Bằng",
        "Bắc Kạn",
        "Thái Nguyên",
        "Phú Thọ",
        "Bắc Giang",
        "Hải Dương",
        "Hưng Yên",
        "Nam Định",
        "Thái Bình",
        "Ninh Bình",
        "Vĩnh Phúc",
        "Hà Nam"
    ];

    if (in_array($province, $south)) {
        $shipping_fee = 15000;
    } elseif (in_array($province, $central)) {
        $shipping_fee = 25000;
    } elseif (in_array($province, $north)) {
        $shipping_fee = 35000;
    } else {
        $shipping_fee = 30000;
    }

    // Tính giảm giá voucher nếu có
    $discount_amount = 0;
    if ($voucher_code) {
        $stmt_voucher = $conn->prepare("SELECT * FROM Vouchers WHERE code = ? AND active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())");
        $stmt_voucher->execute([$voucher_code]);
        $voucher = $stmt_voucher->fetch(PDO::FETCH_ASSOC);

        if ($voucher && $total_amount >= $voucher['min_order_value']) {
            if ($voucher['type'] === 'percent') {
                $discount_amount = floor($total_amount * ($voucher['discount_value'] / 100));
                if (!empty($voucher['max_discount']) && $discount_amount > $voucher['max_discount']) {
                    $discount_amount = $voucher['max_discount'];
                }
            } else {
                $discount_amount = $voucher['discount_value'];
            }
        }
    }

    $final_total = max($total_amount + $shipping_fee - $discount_amount, 0);

    // Lưu tạm vào session
    $order_preview = [
        'user_id' => $user_id,
        'email' => $email,
        'full_name' => $full_name,
        'phone' => $phone,
        'province' => $province,
        'district' => $district,
        'specific_address' => $specific_address,
        'shipping_method' => $shipping_method,
        'shipping_fee' => $shipping_fee,
        'note' => $note,
        'discount_amount' => $data['discount_amount'] ?? 0,
        'voucher_code' => $voucher_code,
        'total_amount' => $total_amount,
        'final_total' => $final_total,
        'cart_items' => $cart_items,
    ];

    // Sinh mã order_id tạm thời
    $order_time = date('dmYHis');
    if (!isset($_SESSION['order_serial'])) {
        $_SESSION['order_serial'] = 1;
    } else {
        $_SESSION['order_serial']++;
    }
    $order_id = $order_time . str_pad($_SESSION['order_serial'], 4, '0', STR_PAD_LEFT);
    $order_preview['note'] = $note;
    $order_preview['order_id'] = $order_id;
    $_SESSION['order_preview'] = $order_preview;

    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Phương thức không hợp lệ hoặc dữ liệu không hợp lệ']);
    exit;
}
