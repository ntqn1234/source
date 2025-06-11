<?php
session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Vui lòng đăng nhập để xem giỏ hàng"]);
    exit();
}

// Lấy user_id từ session (có thể là user hoặc admin)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['admin_id'];

try {
    // Xử lý các hành động dựa trên tham số 'action'
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

    // Lấy giỏ hàng
    if ($action === 'get_cart') {
        $stmt = $conn->prepare("
            SELECT c.id AS cart_id, c.product_id, c.variant_id, c.quantity,
                   p.name AS product_name, p.price AS product_price, p.color, p.size,
                   cat.name AS category_name,
                   pv.variant_name, pv.price AS variant_price, pv.image AS variant_image
            FROM Cart c
            INNER JOIN Products p ON c.product_id = p.id
            INNER JOIN Categories cat ON p.category_id = cat.id
            LEFT JOIN Product_Variants pv ON c.variant_id = pv.id
            WHERE c.user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subtotal = 0;
        foreach ($cart_items as &$item) {
            $price = $item['variant_price'] ?? $item['product_price'];
            $item['price'] = $price;
            $item['item_total'] = $price * $item['quantity'];
            $subtotal += $item['item_total'];
        }

        $voucher = $_SESSION['applied_voucher'] ?? null;
        $discount = $voucher['discount'] ?? 0;
        $total = max($subtotal - $discount, 0);

        echo json_encode([
            "items" => $cart_items,
            "subtotal" => $subtotal,
            "discount" => $discount,
            "voucher" => $voucher,
            "total" => $total,
            "item_count" => count($cart_items)
        ]);
        exit;
    }

    // Thêm sản phẩm vào giỏ hàng
    if ($action === 'add_to_cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = $_POST['product_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $variant_id = $_POST['variant_id'] ?? null;

        if ($product_id && $quantity > 0) {
            // Kiểm tra sản phẩm đã có trong giỏ chưa
            $sql = "SELECT id, quantity FROM Cart WHERE user_id = :user_id AND product_id = :product_id";
            if ($variant_id) {
                $sql .= " AND variant_id = :variant_id";
            } else {
                $sql .= " AND variant_id IS NULL";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            if ($variant_id) $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
            $stmt->execute();
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cart_item) {
                // Nếu đã có, cập nhật số lượng
                $new_quantity = $cart_item['quantity'] + $quantity;
                $stmt = $conn->prepare("UPDATE Cart SET quantity = :quantity WHERE id = :id");
                $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
                $stmt->bindParam(':id', $cart_item['id'], PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Nếu chưa có, thêm mới
                $stmt = $conn->prepare("INSERT INTO Cart (user_id, product_id, variant_id, quantity) VALUES (:user_id, :product_id, :variant_id, :quantity)");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                if ($variant_id) {
                    $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
                } else {
                    $variant_id = null;
                    $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_NULL);
                }
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->execute();
            }
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Dữ liệu không hợp lệ"]);
        }
        exit;
    }

    // Cập nhật số lượng sản phẩm trong giỏ hàng
    if ($action === 'update_quantity' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cart_id = $_POST['cart_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 0);

        if ($cart_id && $quantity > 0) {
            $sql = "UPDATE Cart SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Dữ liệu không hợp lệ"]);
        }
        exit;
    }

    // Xóa sản phẩm khỏi giỏ hàng
    if ($action === 'delete_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cart_id = $_POST['cart_id'] ?? 0;
        if ($cart_id) {
            $sql = "DELETE FROM Cart WHERE id = :cart_id AND user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Dữ liệu không hợp lệ"]);
        }
        exit;
    }

    // Lấy danh sách voucher đang hoạt động
    if ($action === 'get_vouchers') {
        $stmt = $conn->query("SELECT * FROM Vouchers WHERE active = 1");
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($vouchers);
        exit;
    }

    // Áp dụng voucher
    if ($action === 'apply_voucher' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = $_POST['code'] ?? '';
        $total = intval($_POST['total'] ?? 0);

        $sql = "SELECT * FROM Vouchers
                WHERE code = :code AND active = 1
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date IS NULL OR end_date >= CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($voucher) {
            if ($total < $voucher['min_order_value']) {
                echo json_encode([
                    'success' => false,
                    'error' => "Đơn hàng tối thiểu phải từ " . number_format($voucher['min_order_value']) . "đ để dùng mã này."
                ]);
                exit;
            }

            $discount = 0;
            if ($voucher['type'] === 'percent') {
                $discount = floor($total * $voucher['discount_value'] / 100);
            } else {
                $discount = $voucher['discount_value'];
            }
            $discount = min($discount, $total);

            $_SESSION['applied_voucher'] = [
                'code' => $code,
                'discount' => $discount,
                'type' => $voucher['type'],
                'value' => $voucher['discount_value']
            ];

            echo json_encode([
                'success' => true,
                'discount' => $discount,
                'type' => $voucher['type'],
                'value' => $voucher['discount_value']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.'
            ]);
        }
        exit;
    }

    // Xác nhận đặt hàng (nếu có)
    if ($action === 'confirm_checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || empty($data['cart_items']) || empty($data['email'])) {
            echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $_SESSION['order_preview'] = $data;
        echo json_encode(['success' => true]);
        exit;
    }

    // Nếu không khớp action nào
    http_response_code(400);
    echo json_encode(["error" => "Hành động không hợp lệ"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
}
