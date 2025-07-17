<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageData = null;
    $imageType = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed, true)) {
            $imageData = file_get_contents($_FILES['image']['tmp_name']);
            $imageType = mime_content_type($_FILES['image']['tmp_name']);
        }
    }
    if ($imageData) {
        $stmt = $pdo->prepare('INSERT INTO products (name, code, unit, measure_value, unit_price, category, image_data, image_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bindParam(1, $_POST['name']);
        $stmt->bindParam(2, $_POST['code']);
        $stmt->bindParam(3, $_POST['unit']);
        $stmt->bindParam(4, $_POST['measure_value']);
        $stmt->bindParam(5, $_POST['unit_price']);
        $stmt->bindParam(6, $_POST['category']);
        $stmt->bindParam(7, $imageData, PDO::PARAM_LOB);
        $stmt->bindParam(8, $imageType);
        $stmt->execute();
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
