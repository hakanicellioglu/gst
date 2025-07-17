<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed, true)) {
            $dir = __DIR__ . '/uploads/products';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = uniqid('prod_', true) . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $imagePath = 'uploads/products/' . $filename;
            }
        }
    }
    if ($imagePath) {
        $stmt = $pdo->prepare('INSERT INTO products (name, code, unit, measure_value, unit_price, category, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['name'],
            $_POST['code'],
            $_POST['unit'],
            $_POST['measure_value'],
            $_POST['unit_price'],
            $_POST['category'],
            $imagePath
        ]);
        echo 'Saved';
    } else {
        echo 'Invalid image';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Example</title>
</head>
<body>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="text" name="code" placeholder="Code" required><br>
    <input type="text" name="unit" placeholder="Unit" required><br>
    <input type="number" step="0.001" name="measure_value" placeholder="Measure" required><br>
    <input type="number" step="0.01" name="unit_price" placeholder="Price" required><br>
    <input type="text" name="category" placeholder="Category"><br>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required><br>
    <button type="submit">Upload</button>
</form>
</body>
</html>
