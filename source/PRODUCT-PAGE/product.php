<?php
session_start();
header('Content-Type: application/json');
require_once '../INCLUDE/db_connect.php'; // File kết nối database

// Xử lý các hành động dựa trên tham số 'action'
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($action === 'get_product') {
    try {
        // Lấy product_id từ query string
         $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

        if ($product_id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu hoặc không hợp lệ product_id"]);
            exit();
        }

        // Truy vấn thông tin sản phẩm
        $sql = "SELECT p.id, p.name, p.price, p.image, p.color, p.size, p.new, p.description,
                   c.name AS category_name
                FROM Products p
                INNER JOIN Categories c ON p.category_id = c.id
                WHERE p.id = :product_id";
    
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode(["error" => "Không tìm thấy sản phẩm"]);
            exit();
        }

        // Truy vấn các biến thể của sản phẩm
        $sql_variants = "SELECT id, variant_name, image,price 
                        FROM Product_Variants 
                        WHERE product_id = :product_id";
        $stmt_variants = $conn->prepare($sql_variants);
        $stmt_variants->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt_variants->execute();
        $variants = $stmt_variants->fetchAll(PDO::FETCH_ASSOC);

        // Truy vấn các sản phẩm liên quan (ví dụ: cùng danh mục hoặc sản phẩm mới)
        $sql_related = "SELECT p.id, p.name, p.price, p.image, p.color, p.size, p.new
                        FROM Products p
                        WHERE p.category_id = :category_id AND p.id != :product_id
                        LIMIT 4";
        $stmt_related = $conn->prepare($sql_related);
        $stmt_related->bindParam(':category_id', $product['category_id'], PDO::PARAM_INT);
        $stmt_related->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt_related->execute();
        $related_products = $stmt_related->fetchAll(PDO::FETCH_ASSOC);

        // Trả về dữ liệu
        echo json_encode([
            "product" => $product,
            "variants" => $variants,
            "related_products" => $related_products
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
    }
} 
else if ($action === 'add_to_cart') {
    try {
        // Kiểm tra người dùng đã đăng nhập chưa
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng"]);
            exit();
        }

        // Lấy user_id từ session (có thể là user hoặc admin)
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['admin_id'];

        // Lấy dữ liệu từ yêu cầu POST
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        // Kiểm tra dữ liệu đầu vào
        if ($product_id <= 0 || $variant_id <= 0 || $quantity <= 0 || $user_id <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Dữ liệu không hợp lệ"]);
            exit();
        }

        // Kiểm tra xem sản phẩm/biến thể đã có trong giỏ hàng của người dùng chưa
        $sql_check = "SELECT id, quantity FROM Cart 
                      WHERE user_id = :user_id AND product_id = :product_id AND variant_id = :variant_id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_check->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt_check->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $cart_item = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            // Nếu đã có trong giỏ hàng, cập nhật số lượng
            $new_quantity = $cart_item['quantity'] + $quantity;
            $sql_update = "UPDATE Cart 
                           SET quantity = :quantity 
                           WHERE id = :cart_id";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
            $stmt_update->bindParam(':cart_id', $cart_item['id'], PDO::PARAM_INT);
            $stmt_update->execute();
        } else {
            // Nếu chưa có, thêm mới vào giỏ hàng
            $sql_insert = "INSERT INTO Cart (user_id, product_id, variant_id, quantity) 
                           VALUES (:user_id, :product_id, :variant_id, :quantity)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt_insert->execute();
        }

        // Trả về phản hồi thành công
        echo json_encode(["success" => true, "message" => "Đã thêm vào giỏ hàng"]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Lỗi khi thêm vào giỏ hàng: " . $e->getMessage()]);
    }
} else if ($action === 'get_4product') {
    // Truy vấn lấy 4 sản phẩm đầu
    $sql = "SELECT id, name, image, price
            FROM Products 
            ORDER BY id ASC
            LIMIT 4";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = [];

    // Lấy dữ liệu và chuyển thành mảng
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = [
            "id" => (int)$row["id"],
            "name" => $row["name"],
            "image" => $row["image"],
            "price" => $row["price"]
        ];
    }

    // Trả về dữ liệu dạng JSON với khóa "products"
    echo json_encode(["products" => $products]);
}
else {
    http_response_code(400);
    echo json_encode(["error" => "Hành động không hợp lệ"]);
}
?>