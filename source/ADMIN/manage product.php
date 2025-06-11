<?php

session_start();
require_once '../INCLUDE/db_connect.php';
header('Content-Type: application/json');

// Xử lý các hành động dựa trên tham số 'action'
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($action === 'get_products') {
    try {
        

        // Truy vấn danh sách sản phẩm
        $sql = "SELECT p.id, p.name, p.price, p.quantity, p.image, p.new, p.color, p.size, p.description, c.name AS category_name
                FROM Products p INNER JOIN Categories c ON p.category_id = c.id
                ORDER BY p.id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy thông tin biến thể cho từng sản phẩm
        foreach ($products as &$product) {
            $sqlVariants = "SELECT id, variant_name, image, price FROM Product_Variants WHERE product_id = :product_id";
            $stmtVariants = $conn->prepare($sqlVariants);
            $stmtVariants->bindParam(':product_id', $product['id'], PDO::PARAM_INT);
            $stmtVariants->execute();
            $product['variants'] = $stmtVariants->fetchAll(PDO::FETCH_ASSOC);
        }

        // Trả về danh sách sản phẩm
        echo json_encode([
            "success" => true,
            "data" => $products
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Lỗi truy xuất dữ liệu: " . $e->getMessage()]);
    }
} else if ($action === 'delete_product') {
    try {
        
        // Lấy product_id từ request
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu hoặc không hợp lệ product_id"]);
            exit();
        }

        // Xóa các bản ghi trong Cart liên quan đến sản phẩm
        $sqlCart = "DELETE FROM Cart WHERE product_id = :product_id";
        $stmtCart = $conn->prepare($sqlCart);
        $stmtCart->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmtCart->execute();

        // Xóa các bản ghi trong Order_Items liên quan đến sản phẩm
        $sqlOrderItems = "DELETE FROM Order_Items WHERE product_id = :product_id";
        $stmtOrderItems = $conn->prepare($sqlOrderItems);
        $stmtOrderItems->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmtOrderItems->execute();

        // Xóa các bình luận liên quan đến sản phẩm trong Comment
        $sqlComments = "DELETE FROM Comments WHERE product_id = :product_id";
        $stmtComments = $conn->prepare($sqlComments);
        $stmtComments->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmtComments->execute();

        // Xóa các biến thể của sản phẩm trong bảng Product_Variant
        $sqlVariants = "DELETE FROM Product_Variants WHERE product_id = :product_id";
        $stmtVariants = $conn->prepare($sqlVariants);
        $stmtVariants->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmtVariants->execute();

        // Xóa sản phẩm
        $sql = "DELETE FROM Products WHERE id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();

        // Trả về phản hồi thành công
        echo json_encode(["success" => true, "message" => "Xóa sản phẩm thành công"]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Lỗi khi xóa sản phẩm: " . $e->getMessage()]);
    }
} else if ($action === 'update_product') {
    try {
        // Lấy dữ liệu từ request
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? (int)$_POST['price'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $new = isset($_POST['new']) ? filter_var($_POST['new'], FILTER_VALIDATE_BOOLEAN) : false;
        $color = isset($_POST['color']) ? trim($_POST['color']) : '';
        $size = isset($_POST['size']) ? trim($_POST['size']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $variants = isset($_POST['variants']) ? json_decode($_POST['variants'], true) : [];
        $currentProductImage = isset($_POST['current_product_image']) ? trim($_POST['current_product_image']) : '';


        // Đường dẫn thư mục lưu trữ ảnh
        $productUploadDir = '../UPLOAD/PRODUCT/';
        $variantUploadDir = '../UPLOAD/VARIANT/';

        // Bắt đầu giao dịch
        $conn->beginTransaction();

        // Xử lý ảnh sản phẩm
        $imagePath = $currentProductImage; // Mặc định sử dụng ảnh cũ

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image'];
            // Kiểm tra định dạng ảnh
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            if (!in_array($image['type'], $allowedTypes)) {
                $conn->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "Chỉ chấp nhận file JPEG, PNG, GIF hoặc WebP"]);
                exit();
            }
            // Kiểm tra kích thước ảnh (ví dụ: tối đa 5MB)
            if ($image['size'] > 5 * 1024 * 1024) {
                $conn->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "File ảnh quá lớn, tối đa 5MB"]);
                exit();
            }

            // Xử lý tên file: thay dấu cách bằng gạch dưới và loại bỏ ký tự đặc biệt
            $imageName = preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', basename($image['name']));
            $imageName = time() . '_' . $imageName; // Thêm timestamp để tránh trùng
            $imagePath = $productUploadDir . $imageName;
            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                $conn->rollBack();
                http_response_code(500);
                echo json_encode(["error" => "Lỗi khi tải ảnh sản phẩm lên server"]);
                exit();
            }

        }

        // Cập nhật sản phẩm
        $sql = "UPDATE Products 
                SET name = :name, price = :price, quantity = :quantity, category_id = :category_id, 
                    new = :new, color = :color, size = :size, description = :description, image = :image 
                WHERE id = :product_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':new', $new, PDO::PARAM_BOOL);
        $stmt->bindParam(':color', $color, PDO::PARAM_STR);
        $stmt->bindParam(':size', $size, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':image', $imagePath, PDO::PARAM_STR);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();

        // Xóa các bản ghi trong Cart liên quan đến biến thể của sản phẩm
        $sqlDeleteCart = "DELETE FROM Cart WHERE variant_id IN (SELECT id FROM Product_Variants WHERE product_id = :product_id)";
        $stmtDeleteCart = $conn->prepare($sqlDeleteCart);
        $stmtDeleteCart->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmtDeleteCart->execute();

        // Xóa các bản ghi trong Cart liên quan đến biến thể của sản phẩm
        $sqlDeleteOrderItem = "DELETE FROM Order_Items WHERE variant_id IN (SELECT id FROM Product_Variants WHERE product_id = :product_id)";
        $sqlDeleteOrderItem = $conn->prepare($sqlDeleteOrderItem);
        $sqlDeleteOrderItem->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $sqlDeleteOrderItem->execute();

        // Xóa các biến thể cũ
        $sqlDeleteVariants = "DELETE FROM Product_Variants WHERE product_id = :product_id";
        $stmtDeleteVariants = $conn->prepare($sqlDeleteVariants);
        $stmtDeleteVariants->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmtDeleteVariants->execute();

        // Thêm các biến thể mới
        if (!empty($variants)) {
            if (!is_array($variants)) {
                $conn->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "Dữ liệu biến thể không hợp lệ"]);
                exit();
            }
            $sqlInsertVariant = "INSERT INTO Product_Variants (product_id, variant_name, image, price) 
                                 VALUES (:product_id, :variant_name, :image, :price)";
            $stmtInsertVariant = $conn->prepare($sqlInsertVariant);
            foreach ($variants as $index => $variant) {
                $variantName = isset($variant['variant_name']) ? trim($variant['variant_name']) : '';
                $variantPrice = isset($variant['price']) ? (int)$variant['price'] : 0;
                $variantImage = isset($variant['image']) ? trim($variant['image']) : ''; // Sử dụng ảnh từ variants.image

                // Xử lý ảnh biến thể
                $fileKey = 'variant_image_' . $index;
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $variantFile = $_FILES[$fileKey];
                    // Kiểm tra định dạng ảnh
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
                    if (!in_array($variantFile['type'], $allowedTypes)) {
                        $conn->rollBack();
                        http_response_code(400);
                        echo json_encode(["error" => "Chỉ chấp nhận file JPEG, PNG, GIF hoặc WebP cho biến thể"]);
                        exit();
                    }
                    // Kiểm tra kích thước ảnh
                    if ($variantFile['size'] > 5 * 1024 * 1024) {
                        $conn->rollBack();
                        http_response_code(400);
                        echo json_encode(["error" => "File ảnh biến thể quá lớn, tối đa 5MB"]);
                        exit();
                    }

                    // Xử lý tên file: thay dấu cách bằng gạch dưới và loại bỏ ký tự đặc biệt
                    $variantImageName = preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', basename($variantFile['name']));
                    $variantImageName = time() . '_' . $variantImageName;
                    $variantImage = $variantUploadDir . $variantImageName;
                    if (!move_uploaded_file($variantFile['tmp_name'], $variantImage)) {
                        $conn->rollBack();
                        http_response_code(500);
                        echo json_encode(["error" => "Lỗi khi tải ảnh biến thể lên server"]);
                        exit();
                    }

                }
                
                if ($variantName && $variantPrice > 0 && !is_nan($variantPrice)) {
                    $stmtInsertVariant->bindParam(':product_id', $productId, PDO::PARAM_INT);
                    $stmtInsertVariant->bindParam(':variant_name', $variantName, PDO::PARAM_STR);
                    $stmtInsertVariant->bindParam(':image', $variantImage, PDO::PARAM_STR);
                    $stmtInsertVariant->bindParam(':price', $variantPrice, PDO::PARAM_INT);
                    $stmtInsertVariant->execute();
                } else {
                    $conn->rollBack();
                    http_response_code(400);
                    echo json_encode(["error" => "Dữ liệu biến thể không hợp lệ: " . json_encode($variant)]);
                    exit();
                }
            }
        }

        // Hoàn tất giao dịch
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Cập nhật sản phẩm thành công"]);

    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Lỗi khi cập nhật sản phẩm: " . $e->getMessage() . " tại dòng " . $e->getLine()]);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Lỗi chung: " . $e->getMessage() . " tại dòng " . $e->getLine()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Hành động không hợp lệ"]);
}
?>