<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h1 class="display-4 mb-4">Hoş geldin
                    <?php echo isset($users['first_name']) ? htmlspecialchars($users['first_name']) : "Kullanıcı!" ?>
                </h1>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>