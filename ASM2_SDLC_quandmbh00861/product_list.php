<?php
$servername = "localhost";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=banquanao", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Lấy danh sách sản phẩm
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="product_list.css">
    <title>Product List</title>
</head>
<body>
    <h1>Product List</h1>

    <!-- Buttons for Add Product and Logout -->
    <div class="button-container">
        <a href="index.php"><button class="action-btn">Add Product</button></a>
        <a href="login.php"><button class="action-btn logout-btn">Logout</button></a>
    </div>

    <!-- Product List -->
    <div class="product-container">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="uploads/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" width="200">
                <h3><?= $product['name'] ?></h3>
                <p>Price: $<?= number_format($product['price'], 2) ?></p>
                <p>Added On: <?= $product['created_at'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>

