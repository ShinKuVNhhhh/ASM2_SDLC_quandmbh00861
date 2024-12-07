<?php
require 'db.php';
session_start();

// Lấy danh sách sản phẩm
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Nếu chưa đăng nhập, chuyển hướng đến trang login
    exit;
}

// Xử lý khi người dùng thêm sản phẩm vào giỏ hàng
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Lấy thông tin sản phẩm từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch();

    // Thêm sản phẩm vào giỏ hàng trong session
    if ($product) {
        $cart_item = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $product['price'] * $quantity
        ];

        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            $_SESSION['cart'][$product_id]['total'] = $_SESSION['cart'][$product_id]['price'] * $_SESSION['cart'][$product_id]['quantity'];
        } else {
            $_SESSION['cart'][$product_id] = $cart_item;
        }
    }
}

// Xử lý giảm số lượng sản phẩm trong giỏ hàng
if (isset($_POST['decrease_quantity'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id]) && $_SESSION['cart'][$product_id]['quantity'] > 1) {
        $_SESSION['cart'][$product_id]['quantity']--;
        $_SESSION['cart'][$product_id]['total'] = $_SESSION['cart'][$product_id]['price'] * $_SESSION['cart'][$product_id]['quantity'];
    }
}

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
}

// Xử lý khi người dùng đặt hàng
if (isset($_POST['place_order'])) {
    // Lấy thông tin giỏ hàng
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $user_id = $_SESSION['user']['id'];
        $total_amount = 0;

        // Tính tổng giá trị giỏ hàng
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['total'];
        }

        // Lưu đơn hàng vào cơ sở dữ liệu (table orders)
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $total_amount]);

        // Lấy ID của đơn hàng vừa tạo
        $order_id = $conn->lastInsertId();

        // Lưu các chi tiết đơn hàng vào cơ sở dữ liệu (table order_items)
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        // Xóa giỏ hàng sau khi đặt hàng thành công
        unset($_SESSION['cart']);
        echo "<p style='color: green;'>Order placed successfully!</p>";
    } else {
        echo "<p style='color: red;'>Your cart is empty!</p>";
    }
}
// Xử lý xóa sản phẩm khỏi lịch sử mua hàng
if (isset($_POST['delete_from_history'])) {
    $order_id = $_POST['order_id'];
    $product_id = $_POST['product_id'];

    // Xóa sản phẩm khỏi bảng `order_items`
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = :order_id AND product_id = :product_id");
    $stmt->execute(['order_id' => $order_id, 'product_id' => $product_id]);

    // Nếu đơn hàng không còn sản phẩm nào, xóa đơn hàng khỏi bảng `orders`
    $stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $order_id]);
    $remaining_items = $stmt->fetchColumn();

    if ($remaining_items == 0) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = :order_id");
        $stmt->execute(['order_id' => $order_id]);
    }

    echo "<p style='color: green;'>Item removed from history successfully!</p>";
}

// Lấy lịch sử mua hàng của người dùng hiện tại
$user_id = $_SESSION['user']['id']; // Lấy ID người dùng từ session
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.created_at, oi.product_id, oi.quantity, oi.price, p.name 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = :user_id
    ORDER BY o.created_at DESC
");
$stmt->execute(['user_id' => $user_id]);
$order_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="buy.css">
    <title>Buy Products</title>
</head>
<body>
    <h1>Buy Products</h1>

    <h2>Product List</h2>
    <div class="product-container">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="uploads/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" width="200">
                <h3><?= $product['name'] ?></h3>
                <p>Price: $<?= number_format($product['price'], 2) ?></p>

                <!-- Form thêm vào giỏ hàng -->
                <form action="buy.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" min="1" value="1" required>
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Your Cart</h2>
    <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
        <table border="1">
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                <tr>
                    <td><?= $item['name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td>$<?= number_format($item['total'], 2) ?></td>
                    <td>
                        <!-- Form giảm số lượng -->
                        <form action="buy.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <button type="submit" name="decrease_quantity">-</button>
                        </form>
                        <!-- Form xóa sản phẩm -->
                        <form action="buy.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <button type="submit" name="remove_from_cart">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <form action="buy.php" method="POST">
            <button type="submit" name="place_order">Place Order</button>
        </form>
    <?php else: ?>
        <p>Your cart is empty!</p>
    <?php endif; ?>

    <h2>Your Purchase History</h2>
    <?php if (!empty($order_history)): ?>
        <table border="1">
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
            <?php foreach ($order_history as $item): ?>
                <tr>
                    <td><?= $item['order_id'] ?></td>
                    <td><?= $item['created_at'] ?></td>
                    <td><?= $item['name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td>$<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                    <td>
                        <!-- Form xóa sản phẩm khỏi lịch sử -->
                        <form action="buy.php" method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $item['order_id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <button type="submit" name="delete_from_history">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>You have no purchase history.</p>
    <?php endif; ?>
</body>
</html>