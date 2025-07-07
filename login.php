<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Oturum Aç</h3>
                        <form>
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı veya E-posta</label>
                                <input type="text" class="form-control" id="username" placeholder="Kullanıcı adı veya e-posta">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Parola</label>
                                <input type="password" class="form-control" id="password" placeholder="Parola">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Oturum Aç</button>
                                <a href="register.php" class="btn btn-link">Hesabın yok mu? Hemen kayıt ol</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>