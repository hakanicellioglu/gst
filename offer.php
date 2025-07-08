<?php
require_once 'config.php';
require_once 'helpers/theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
load_theme_settings($pdo);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teklif Oluştur</title>
    <link href="<?php echo theme_css(); ?>" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Giyotin</span>
                <button type="button" id="addRow" class="btn btn-success btn-sm">+</button>
            </div>
            <div class="card-body">
                <form>
                    <div id="rows"></div>
                </form>
                <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-<?php echo get_color(); ?>">Kaydet</button>
                    </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addRowBtn = document.getElementById('addRow');
            const rowsDiv = document.getElementById('rows');

            function createRow() {
                const row = document.createElement('div');
                row.className = 'border rounded p-3 mb-3';
                row.innerHTML = `
            <fieldset>
                <div class="my-3 d-flex justify-content-between align-items-center">
                    <legend class="fs-6 mb-0">Sistem</legend>
                    <button type="button" class="btn btn-danger btn-sm remove-row">Kaldır</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label">Genişlik</label>
                        <input type="number" name="width[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Yükseklik</label>
                        <input type="number" name="height[]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Adet</label>
                        <input type="number" name="qty[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Motor Sistemi</label>
                        <select name="motor[]" class="form-select">
                            <option value="somfy">Somfy</option>
                            <option value="bft">BFT</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Kumanda</label>
                        <input type="number" name="remote[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">RAL Kodu</label>
                        <input type="text" name="ral[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sistem Adedi</label>
                        <input type="number" name="system_qty[]" class="form-control">
                    </div>
                </div>
            </fieldset>
            <fieldset class="mt-3">
                <legend class="fs-6">Cam</legend>
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label">Cam Tipi</label>
                        <select name="glass_type[]" class="form-select">
                            <option value="isicam">Isıcam</option>
                            <option value="tekcam">Tek Cam</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cam Genişlik</label>
                        <input type="number" name="glass_width[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cam Yükseklik</label>
                        <input type="number" name="glass_height[]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Cam Adedi</label>
                        <input type="number" name="glass_qty[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cam Rengi</label>
                        <input type="text" name="glass_color[]" class="form-control">
                    </div>
                </div>
            </fieldset>`;
                row.querySelector('.remove-row').addEventListener('click', () => row.remove());
                rowsDiv.appendChild(row);
            }

            addRowBtn.addEventListener('click', createRow);
            createRow();
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>