-- Tạo database
CREATE DATABASE lenlab;
USE lenlab;

-- Bảng Users
CREATE TABLE Users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    reset_token VARCHAR(255) DEFAULT NULL, 
    reset_token_expiry DATETIME DEFAULT NULL
);

CREATE TABLE Admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(50) NOT NULL
);

-- Bảng Categories
CREATE TABLE Categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL
);

-- Bảng Products
CREATE TABLE Products (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  price INT NOT NULL,
  category_id INT,
  new BOOLEAN NOT NULL,
  image VARCHAR(255),
  color VARCHAR(255),
  size VARCHAR(255),
  quantity INT NOT NULL,
  status ENUM('còn hàng', 'hết hàng') NOT NULL DEFAULT 'còn hàng',
  description TEXT,
  FOREIGN KEY (category_id) REFERENCES Categories(id)
);

CREATE TABLE Product_Variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    variant_name VARCHAR(255), -- Tên biến thể (ví dụ: "Combo 10 cuộn len")
    image VARCHAR(255),
    price INT, -- Giá của biến thể
    FOREIGN KEY (product_id) REFERENCES Products(id)
);



-- Bảng Orders
CREATE TABLE Orders (
    order_id VARCHAR(30) PRIMARY KEY,
    user_id INT,
    email VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    specific_address VARCHAR(255) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0.00,
    payment_method ENUM('cod', 'bank_transfer') DEFAULT 'cod',
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    order_note TEXT DEFAULT NULL,
    shipping_method VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);

-- Bảng Order_Items
CREATE TABLE Order_Items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(30) NOT NULL,
    product_id INT,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(id),
    FOREIGN KEY (variant_id) REFERENCES Product_Variants(id)
);

-- Bảng Cart
CREATE TABLE Cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    variant_id INT,
    quantity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (product_id) REFERENCES Products(id),
    FOREIGN KEY (variant_id) REFERENCES Product_Variants(id)
);

CREATE TABLE Comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5), 
    comment TEXT, 
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (product_id) REFERENCES Products(id)
);

-- Bảng Vouchers
CREATE TABLE Vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('fixed', 'percent') NOT NULL DEFAULT 'fixed',
    discount_value INT NOT NULL,
    min_order_value INT DEFAULT 0,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    active BOOLEAN DEFAULT TRUE
);



INSERT INTO Admins (email, password, name) VALUES
('admin@lenlab.com', 'admin123', 'Quản lý LenLab');

INSERT INTO Users (email, password, name, gender) VALUES
('user1@example.com', 'user123', 'Nguyen Van A', 'male'),
('user2@example.com', 'user123', 'Tran Thi B', 'female'),
('user3@example.com', 'user123', 'Le Van C', 'male'),
('user4@example.com', 'user123', 'Pham Thi D', 'female'),
('user5@example.com', 'user123', 'Hoang Van E', 'male'),
('user6@example.com', 'user123', 'Bui Thi F', 'female'),
('user7@example.com', 'user123', 'Dang Van G', 'other'),
('user8@example.com', 'user123', 'Vo Thi H', 'female'),
('user9@example.com', 'user123', 'Nguyen Van K', 'male'),
('user10@example.com', 'user123', 'Tran Thi M', 'female'),
('admin_addorder@lenlab.com', 'internal123', 'Admin Nội bộ', 'other');

INSERT INTO Categories (name) VALUES
('Nguyên phụ liệu'),
('Đồ trang trí'),
('Thời trang len'),
('Combo tự làm'),
('Sách hướng dẫn'),
('Thú bông len');


INSERT INTO Products (name, price, category_id, new, image, color, size, quantity, description) VALUES

  ('Combo 10/15/20 cuộn len, gói nguyên liệu cơ bản', 70000, 1, true, '../PRODUCT-IMG/product1.1.webp', 'Đa sắc', 'S', 100, 
  'Combo Len 25gram - Lựa chọn hoàn hảo cho mọi dự án đan móc của bạn!
  \nChất liệu cao cấp: Len mềm mịn, dai bền, không xù lông, không phai màu, an toàn cho làn da nhạy cảm.
  \nĐa dạng màu sắc: Combo nhiều màu phong phú, dễ dàng phối tone hoặc sáng tạo theo phong cách riêng.
  \nTiện lợi: Mỗi cuộn 25gram, thiết kế nhỏ gọn, dễ dàng bảo quản và mang theo.
  \nPhù hợp mọi kỹ năng: Từ người mới bắt đầu đến những "nghệ nhân" đan móc chuyên nghiệp.'),

  ('Kim móc cao cấp nhẹ gọn cho người dùng', 9000, 1, true, '../PRODUCT-IMG/product2.1.webp', 'Bạc', 'M', 100, 
  'Kim Móc Cán Đỏ-dễ xài dành cho người mới học. 
  \nKim cán cầm rất thoải mái và chắc chắn, đầu kim vừa đủ độ dài giúp mũi móc nhanh gọn ít mắc sợi. 
  \nBộ gồm các size: 2 mm - 2.5 mm - 3 mm - 3.5 mm - 4 mm - 4.5 mm - 5 mm - 5.6 mm - 6 mm'),

  ('Combo 10 Kim đánh đấu mũi len bằng nhựa nhiều sắc màu', 5000, 1, false, '../PRODUCT-IMG/product3.1.webp', 'Đa sắc', 'L', 100, 
  'Bộ 10 kim đánh dấu làm từ nhựa cao cấp, giúp bạn dễ dàng theo dõi vị trí móc len khi đan.
  \nThiết kế nhỏ gọn, có nhiều màu sắc nổi bật, phù hợp với mọi loại len và sợi.'),

  ('Kẽm Cắm Hoa Giấy, Hoa Nhựa Dài 60cm', 26000, 1, true, '../PRODUCT-IMG/product4.1.webp', 'Xanh', 'S', 100, 
  'Chất liệu: Kẽm bọc nhựa cứng cáp, chắc chắn, bền, tiết kiệm và dẻo dai.
   \nKiểu mẫu: Cây thẳng dạng trơn.
   \nSử dụng: Kẽm cành cắm hoa làm hoa sáp, hoa vải, hoa voal, hoa len, hoa kẽm nhung, cỏ điểm, gấu xốp, lá táo, handmade...hoặc dán vào các vật dùng handmade trang trí khác'),

  ('Móc khóa mèo đội mũ cá', 40000, 6, false, '../PRODUCT-IMG/product5.1.webp', 'Xám', 'M', 100, 
  'Móc khóa chắc chắn, dễ dàng gắn vào chìa khóa, túi xách, balo, hộp bút… vừa làm đồ trang trí, vừa giúp bạn không bị mất đồ.
   \nChất liệu: Móc len thủ công và khóa kim loại
   \nKích thước: ~ 5-7cm (tùy thiết kế)'),

  ('Thỏ đội mũ', 90000, 6, true, '../PRODUCT-IMG/product6.1.jpg', 'Trắng', 'L', 100, 
  'Bé thỏ nhỏ xinh diện chiếc áo gấu nâu ấm áp, điểm thêm đôi tai ngộ nghĩnh và nụ cười hồn nhiên.
   \nMón đồ chơi handmade này sẽ là người bạn đáng yêu cho mọi bé yêu, vừa để ôm ấp vừa trang trí phòng cực dễ thương!'),

  ('Móc khóa gấu', 40000, 6, false, '../PRODUCT-IMG/product7.1.jpg', 'Nâu', 'M', 100, 
  'Bộ đôi móc khóa gấu nâu và gấu xám với thiết kế siêu dễ thương, được làm từ chất liệu cao cấp bền đẹp, màu sắc tươi tắn không phai.
   \nMóc khóa nhỏ xinh nhưng đầy tiện ích, giúp bạn dễ dàng tìm chìa khóa, trang trí balo, túi xách hay làm quà tặng ý nghĩa.'),

  ('Móc khóa thỏ đội nón', 45000, 6, false, '../PRODUCT-IMG/product8.1.jpg', 'Hồng', 'L', 100, 
  'Bé thỏ bông đội chiếc nón ba màu hồng-tím-đỏ rực rỡ, khuôn mặt tròn xoe đáng yêu với đôi má hồng phúng phính.
   \nMóc khóa nhỏ xinh nhưng chi tiết tinh tế, chất liệu vải dạ cao cấp mềm mịn, đường may tỉ mỉ.'),

  ('Bộ 3 gấu bông', 50000, 6, true, '../PRODUCT-IMG/product9.1.jpg', 'Đa sắc', 'M', 100, 
  'Bộ sưu tập 3 chú gấu bông đáng yêu với các màu sắc khác nhau, hoàn hảo để làm quà tặng hoặc trang trí.
   \nChất liệu vải mềm mại, đường may tỉ mỉ, thiết kế dễ thương với giá thành hợp lý khi mua combo 3 gấu.'),

  ('Dây treo trang trí', 60000, 2, true, '../PRODUCT-IMG/product10.1.jpg', 'Vàng', 'S', 100, 
  'Dây treo trang trí đa màu sắc với thiết kế tinh tế, phù hợp để trang trí phòng ngủ, phòng khách hoặc các buổi tiệc.
   \nChất liệu cao cấp, độ bền màu tốt, dễ dàng lắp đặt và tháo gỡ khi cần thiết.'),

  ('Kính trang trí hoa', 60000, 2, false, '../PRODUCT-IMG/product11.1.jpg', 'Trắng', 'M', 100, 
  'Kính trang trí hoa với thiết kế tinh tế, hoa được làm thủ công tỉ mỉ, phù hợp để trang trí bàn làm việc, phòng khách.
   \nChất liệu thủy tinh cao cấp kết hợp với hoa trang trí sang trọng, mang lại vẻ đẹp tinh tế cho không gian sống.'),

  ('Cây trang trí nhà', 200000, 2, false, '../PRODUCT-IMG/product12.1.jpg', 'Xanh', 'M', 100, 
  'Cây trang trí nhà handmade với thiết kế độc đáo, mang lại không gian ấm cúng cho ngôi nhà của bạn.
   \nChất liệu len cao cấp, an toàn, có hai phiên bản đáng yêu: thỏ ngồi xích đu và hoa đào truyền thống.'),

  ('Dây treo bình', 30000, 2, true, '../PRODUCT-IMG/product13.1.jpg', 'Nâu', 'M', 100, 
  'Dây treo bình handmade với nhiều màu sắc kết hợp đẹp mắt, chất liệu len mềm mại và chắc chắn.
   \nThiết kế tiện dụng, dễ dàng treo các loại bình, chậu cây nhỏ tạo điểm nhấn cho không gian sống.'),

  ('Áo lưới', 130000, 3, false, '../PRODUCT-IMG/product14.1.jpg', 'Trắng', 'M', 100, 
  'Áo lưới thời trang với hai kiểu dáng cổ tròn và thắt dây cá tính, chất liệu lưới co giãn nhẹ thoáng mát.
   \nThiết kế phù hợp mix cùng nhiều trang phục khác nhau, tạo điểm nhấn nổi bật cho trang phục của bạn.'),

  ('Túi thắt nơ', 120000, 3, false, '../PRODUCT-IMG/product15.2.jpg', 'Đỏ', 'M', 100, 
  'Túi thắt nơ với thiết kế tinh tế, điểm nhấn là chiếc nơ duyên dáng, phù hợp cho mọi phong cách.
   \nChất liệu bền đẹp, có hai màu đỏ và vàng, mang đến sự lựa chọn đa dạng cho người dùng'),

  ('Áo croptop hồng', 150000, 3, true, '../PRODUCT-IMG/product16.1.jpg', 'Hồng', 'M', 100, 
  'Áo croptop len hồng mềm mại, giúp tạo cảm giác thoải mái khi mặc và giữ ấm hiệu quả.
   \nThiết kế ôm nhẹ, dễ phối cùng quần jean hoặc chân váy, phù hợp với nhiều phong cách thời trang.'),

  ('Mũ len', 90000, 3, true, '../PRODUCT-IMG/product17.1.jpg', 'Xám', 'M', 100, 
  'Mũ len với thiết kế đơn giản nhưng tinh tế, mang đến sự ấm áp và phong cách cá tính.
   \nBốn màu sắc đa dạng giúp bạn dễ dàng phối đồ, chất liệu len mềm mại và co giãn tốt.'),

  ('Đầm caro đỏ xẻ tà', 400000, 3, false, '../PRODUCT-IMG/product18.1.jpg', 'Đỏ', 'M', 100, 
  'Đầm caro đỏ xẻ tà với thiết kế thanh lịch, phù hợp cho nhiều dịp.
   \nChất liệu vải cao cấp, thoải mái khi mặc, kiểu dáng xẻ tà tạo nét duyên dáng.'),

  ('Sách hướng dẫn móc len', 290000, 5, true, '../PRODUCT-IMG/product19.1.webp', 'Đa sắc', 'M', 100, 
  'Cẩm nang chi tiết giúp bạn học và thực hành nghệ thuật móc len từ cơ bản đến nâng cao.
   \nGồm hướng dẫn từng bước, hình ảnh minh họa rõ ràng, giúp bạn dễ dàng tạo ra sản phẩm len độc đáo.'),

  ('Bộ Móc Len Cho Người Mới Bắt Đầu Làm Móc Móc Khoá Cá Heo', 90000, 4, false, '../PRODUCT-IMG/product20.1.webp', 'Đa sắc', 'M', 100, 
  'Bộ len DIY giúp bạn dễ dàng tự tay móc khóa hình chú cá heo đáng yêu.
   \nBao gồm len chất lượng cao, kim móc và hướng dẫn chi tiết từng bước, phù hợp cho cả người mới bắt đầu.');

INSERT INTO Product_Variants (product_id, variant_name, image, price) VALUES
(1, 'Combo 10 cuộn len 25 gram', '../PRODUCT-IMG/product1.1.webp', 70000),
(1, 'Combo 15 cuộn len 25 gram', '../PRODUCT-IMG/product1.2.webp', 100000),
(1, 'Combo 20 cuộn len 25 gram', '../PRODUCT-IMG/product1.3.webp', 130000),
(2, '2 mm', '../PRODUCT-IMG/product2.1.webp', 9000),
(2, '2.5 mm', '../PRODUCT-IMG/product2.2.webp', 9000),
(2, '3 mm', '../PRODUCT-IMG/product2.3.webp', 9000),
(2, '3.5 mm', '../PRODUCT-IMG/product2.4.webp', 9000),
(2, '4 mm', '../PRODUCT-IMG/product2.5.webp', 9000),
(2, '4.5 mm', '../PRODUCT-IMG/product2.6.webp', 9000),
(2, '5 mm', '../PRODUCT-IMG/product2.7.webp', 9000),
(2, '5.5 mm', '../PRODUCT-IMG/product2.8.webp', 9000),
(2, '6 mm', '../PRODUCT-IMG/product2.9.webp', 9000),
(3, 'Combo 10 kim', '../PRODUCT-IMG/product3.1.webp', 5000),
(4, '50 cái', '../PRODUCT-IMG/product4.1.webp', 26000),
(4, '100 cái', '../PRODUCT-IMG/product4.2.webp', 35000),
(5, 'Mèo trắng mũ nâu', '../PRODUCT-IMG/product5.1.webp', 40000),
(5, 'Mèo đen mũ xanh rêu', '../PRODUCT-IMG/product5.2.webp', 45000),
(5, 'Mèo trắng mũ vàng', '../PRODUCT-IMG/product5.3.webp', 45000),
(6, 'Thỏ nón nâu', '../PRODUCT-IMG/product6.1.jpg', 90000),
(6, 'Thỏ nón trắng', '../PRODUCT-IMG/product6.2.jpg', 90000),
(6, 'Thỏ mũ hồng', '../PRODUCT-IMG/product6.3.jpg', 90000),
(6, 'Thỏ đồ gấu', '../PRODUCT-IMG/product6.4.jpg', 90000),
(7, 'Gấu nâu, Gấu xám', '../PRODUCT-IMG/product7.1.jpg', 40000),
(8, 'Nón hồng', '../PRODUCT-IMG/product8.1.jpg', 45000),
(8, 'Nón đỏ', '../PRODUCT-IMG/product8.2.jpg', 45000),
(8, 'Nón tím', '../PRODUCT-IMG/product8.3.jpg', 45000),
(9, 'Combo 3 gấu', '../PRODUCT-IMG/product9.1.jpg', 140000),
(10, 'Tím', '../PRODUCT-IMG/product10.1.jpg', 60000),
(10, 'Xanh', '../PRODUCT-IMG/product10.2.jpg', 60000),
(10, 'Hồng', '../PRODUCT-IMG/product10.3.jpg', 60000),
(11, 'Hồng nhạt', '../PRODUCT-IMG/product11.1.jpg', 60000),
(11, 'Hồng đậm', '../PRODUCT-IMG/product11.2.jpg', 60000),
(12, 'Thỏ ngồi xích đu', '../PRODUCT-IMG/product12.1.jpg', 200000),
(12, 'Hoa đào', '../PRODUCT-IMG/product12.2.jpg', 200000),
(13, 'Trắng hồng', '../PRODUCT-IMG/product13.1.jpg', 30000),
(13, 'Trắng xanh dương', '../PRODUCT-IMG/product13.2.jpg', 30000),
(13, 'Hồng đen', '../PRODUCT-IMG/product13.3.jpg', 30000),
(14, 'Cổ tròn', '../PRODUCT-IMG/product14.1.jpg', 130000),
(14, 'Thắt dây', '../PRODUCT-IMG/product14.2.jpg', 150000),
(15, 'Đỏ', '../PRODUCT-IMG/product15.1.jpg', 120000),
(15, 'Trắng', '../PRODUCT-IMG/product15.2.jpg', 120000),
(16, 'Hồng', '../PRODUCT-IMG/product16.1.jpg', 150000),
(17, 'Combo 4 mũ len', '../PRODUCT-IMG/product17.1.jpg', 90000),
(18, 'Đỏ', '../PRODUCT-IMG/product18.1.jpg', 400000),
(19, 'Sách', '../PRODUCT-IMG/product19.1.webp', 290000),
(20, 'Sách', '../PRODUCT-IMG/product20.1.webp', 90000);

INSERT INTO Cart (user_id, product_id, variant_id, quantity) VALUES
(1, 1, 1, 2),   
(1, 5, 16, 1),  
(2, 3, 13, 3),  
(3, 6, 19, 1),  
(4, 10, 28, 2),  
(5, 14, 38, 1),  
(6, 17, 43, 2),  
(7, 9, 27, 1),   
(8, 10, 28, 2),  
(9, 11, 31, 1),  
(10, 12, 33, 1); 


INSERT INTO Comments (user_id, product_id, rating, comment) VALUES
(1, 1, 5, 'Combo len rất đẹp, màu sắc tươi sáng, dễ móc!'), 
(2, 1, 4, 'Chất lượng tốt, nhưng giao hàng hơi chậm.'),
(3, 2, 3, 'Len ổn nhưng hơi mỏng so với kỳ vọng.'), 
(4, 4, 5, 'Kim móc rất tiện, cầm thoải mái!'), 
(5, 16, 4, 'Móc khóa dễ thương, chất lượng tốt.'); 

-- Chèn dữ liệu vào Vouchers
INSERT INTO Vouchers (code, type, discount_value, min_order_value, start_date, end_date, active) VALUES
('CHAOBANMOI', 'fixed', 30000, 50000, '2025-06-01', '2025-06-30', 1),
('LEN10', 'percent', 10, 0, NULL, NULL, 1),
('LEN20', 'percent', 20, 300000, '2025-06-01', '2025-12-31', 1);

-- Dữ liệu mới cho bảng Orders
INSERT INTO Orders (order_id, user_id, email, full_name, phone, province, district, specific_address, shipping_fee, payment_method, discount_amount, total_amount, status, created_at, order_note, shipping_method) VALUES
('100620250001', 2, 'user2@example.com', 'Tran Thi B', '0901234567', 'Hà Nội', 'Quận Ba Đình', '123 Phố Láng Hạ', 20000.00, 'cod', 10000.00, 80000.00, 'pending', '2025-06-10 09:00:00', 'Giao hàng trong ngày nếu có thể', 'delivery'),
('100620250002', 3, 'user3@example.com', 'Le Van C', '0912345678', 'TP Hồ Chí Minh', 'Quận 1', '45 Lê Lợi', 25000.00, 'bank_transfer', 0.00, 145000.00, 'processing', '2025-06-10 10:30:00', 'Kiểm tra kỹ sản phẩm trước khi giao', 'delivery'),
('100620250003', 5, 'user5@example.com', 'Hoang Van E', '0933456789', 'Đà Nẵng', 'Quận Hải Châu', '78 Nguyễn Văn Linh', 18000.00, 'cod', 20000.00, 110000.00, 'pending', '2025-06-10 14:15:00', '', 'delivery');

-- Dữ liệu mới cho bảng Order_Items
INSERT INTO Order_Items (order_id, product_id, variant_id, quantity, price) VALUES
('100620250001', 1, 1, 1, 70000.00),
('100620250001', 3, 13, 2, 5000.00),
('100620250002', 5, 16, 2, 40000.00),
('100620250002', 7, 23, 1, 40000.00),
('100620250003', 10, 28, 1, 60000.00),
('100620250003', 16, 42, 1, 150000.00);