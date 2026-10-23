<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host = 'localhost';
$db   = 'inventory_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Ambil Statistik / Laporan Agregat sesuai panduan slide dosen
$totalProducts = $pdo->query("SELECT COUNT(id) FROM products")->fetchColumn();
$totalInventoryValue = $pdo->query("SELECT SUM(stock * price) FROM products")->fetchColumn() ?: 0;
$lowStockCount = $pdo->query("SELECT COUNT(id) FROM products WHERE stock <= 5")->fetchColumn(); // Anggap stok minimum <= 5
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Inventory App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 900px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn { display: inline-block; padding: 10px 15px; margin: 5px 5px 5px 0; color: white; background-color: #007bff; text-decoration: none; border-radius: 4px; }
        .btn-danger { background-color: #dc3545; }
        .btn-success { background-color: #28a745; }
        .btn-info { background-color: #17a2b8; }
        .stats-grid { display: flex; gap: 20px; margin-top: 20px; margin-bottom: 25px; }
        .stat-card { flex: 1; background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-card h4 { margin: 0 0 10px 0; color: #555; font-size: 16px; }
        .stat-card p { margin: 0; font-size: 22px; font-weight: bold; color: #333; }
    </style>
</head>
<body>

<div class="container">
    <h2>Selamat Datang di Dashboard, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</h2>
    <p>Sistem Informasi Inventory Management (Mini Project 3).</p>

    <hr style="margin: 20px 0;">

    <h3>📊 Ringkasan Statistik Inventory</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Total Jenis Produk</h4>
            <p><?= $totalProducts ?></p>
        </div>
        <div class="stat-card">
            <h4>Total Nilai Inventory</h4>
            <p>Rp <?= number_format($totalInventoryValue, 0, ',', '.') ?></p>
        </div>
        <div class="stat-card" style="border-color: <?= $lowStockCount > 0 ? '#dc3545' : '#dee2e6' ?>;">
            <h4 style="color: <?= $lowStockCount > 0 ? '#dc3545' : '#555' ?>;">Stok Menipis (≤ 5)</h4>
            <p style="color: <?= $lowStockCount > 0 ? '#dc3545' : '#333' ?>;"><?= $lowStockCount ?></p>
        </div>
    </div>

    <hr style="margin: 20px 0;">

    <h3>Menu Navigasi Utama</h3>
    <p>
        <a href="categories.php" class="btn btn-success">📁 Kelola Kategori</a>
        <a href="products.php" class="btn btn-info">📦 Kelola Produk</a>
        <a href="transactions.php" class="btn" style="background-color: #ffc107; color: #333;">🔄 Transaksi Stok</a>
    </p>

    <hr style="margin: 20px 0;">

    <p>
        <a href="logout.php" class="btn btn-danger">Keluar (Logout)</a>
    </p>
</div>

</body>
</html>