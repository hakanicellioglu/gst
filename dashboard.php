<?php include 'includes/header.php'; ?>
<div class="container py-5 h-min">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-4 mb-4 text-<?php echo get_color(); ?>">Hoş geldin,
                <?php echo isset($_SESSION['user']['first_name']) ? htmlspecialchars($_SESSION['user']['first_name']) : "Kullanıcı!"; ?>
            </h1>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>