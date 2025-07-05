<?php
// XAMPP ortamı için temel veritabanı ayarları
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'teklif';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Bağlantı hatası: ' . $conn->connect_error);
}
?>