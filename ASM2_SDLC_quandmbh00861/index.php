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

// Add product
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
    $stmt->execute([$name, $price, $image]);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo "Image uploaded successfully!";
    } else {
        echo "Failed to upload image!";
    }
}

// Edit product
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    if ($image) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $price, $image, $id]);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Xóa các bản ghi liên quan trong order_items
    $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
    $stmt->execute([$id]);

    // Xóa sản phẩm trong bảng products
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    echo "Product and related order items deleted successfully!";
}

// Get list of products
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="abc.css">

    <title>Product Management</title>
</head>
<body>
    <h1>Product Management</h1>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="id">
        <label for="name">Product Name:</label>
        <input type="text" name="name" id="name" required>
        <label for="price">Price:</label>
        <input type="number" name="price" id="price" step="0.01" required>
        <label for="image">Image:</label>
        <input type="file" name="image" id="image">
        <button type="submit" name="add">Add</button>
        <button type="submit" name="update">Update</button>
    </form>

    <!-- Back Button -->
    <div style="margin-top: 20px;">
        <a href="product_list.php">
            <button>Back to Product List</button>
        </a>
    </div>

    <h2>Product List</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Image</th>
            <th>Added On</th>
            <th>Action</th>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= $product['id'] ?></td>
            <td><?= $product['name'] ?></td>
            <td><?= $product['price'] ?></td>
            <td><img src="uploads/<?= $product['image'] ?>" width="50"></td>
            <td><?= $product['created_at'] ?></td>
            <td>
                <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">Edit</button>
                <a href="?delete=<?= $product['id'] ?>" onclick="return confirm('Are you sure you want to delete?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <script>
        function editProduct(product) {
            document.getElementById('id').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('price').value = product.price;
        }
    </script>
</body>
</html>