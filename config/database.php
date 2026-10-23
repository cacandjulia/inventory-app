<?php
$host = 'localhost';
$db   = 'inventory_db';
$user = 'root';
$pass = ''; // Default XAMPP password-nya kosong

try {
    // Membuat koneksi menggunakan PDO agar aman dari SQL Injection
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // Mengatur mode error PDO menjadi exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // echo "Koneksi database berhasil!";
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>