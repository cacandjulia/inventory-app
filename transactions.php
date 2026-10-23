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

$error = '';
$success = '';

// Proses Transaksi (Barang Masuk / Keluar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $type       = $_POST['type']; // 'IN' atau 'OUT'
    $quantity   = (int)$_POST['quantity'];
    $note       = trim($_POST['note']);

    if (empty($product_id) || empty($type) || $quantity <= 0) {
        $error = "Semua field wajib diisi dan jumlah harus lebih dari 0!";
    } else {
        try {
            // Mulai Database Transaction (Sesuai syarat modul)
            $pdo->beginTransaction();

            // 1. Cek stok produk saat ini
            $stmtCheck = $pdo->prepare("SELECT stock FROM products WHERE id = :id FOR UPDATE");
            $stmtCheck->execute(['id' => $product_id]);
            $product = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Produk tidak ditemukan!");
            }

            $currentStock = $product['stock'];

            // 2. Validasi aturan bisnis: Barang keluar tidak boleh melebihi stok
            if ($type == 'OUT' && $quantity > $currentStock) {
                throw new Exception("Gagal! Stok tidak mencukupi. Stok saat ini: $currentStock");
            }

            // 3. Hitung stok baru
            $newStock = ($type == 'IN') ? ($currentStock + $quantity) : ($currentStock - $quantity);

            // 4. Update stok di tabel products
            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = :stock WHERE id = :id");
            $stmtUpdate->execute(['stock' => $newStock, 'id' => $product_id]);

            // 5. Simpan log/riwayat transaksi ke tabel (opsional, atau catat ke session/tabel khusus jika ada)
            // Karena tabel log belum wajib mutlak, kita fokus ke transaksi update stok dan commit.

            // Commit transaksi jika semua aman
            $pdo->commit();
            $success = "Transaksi berhasil! Stok produk telah diperbarui.";

        } catch (Exception $e) {
            // Rollback jika terjadi error
            $pdo->rollBack();
            $error = "Transaksi dibatalkan (Rollback): " . $e->getMessage();
        }
    }
}

// Ambil daftar produk untuk pilihan dropdown
$products = $pdo->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Stok - Inventory App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 700px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn { padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-green { background-color: #28a745; }
        .btn-blue { background-color: #007bff; }
        .error { color: red; margin-bottom: 15px; background: #ffe6e6; padding: 10px; border-radius: 4px; }
        .success { color: green; margin-bottom: 15px; background: #e6ffed; padding: 10px; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="container">
    <h2>🔄 Transaksi Stok (Barang Masuk / Keluar)</h2>
    <p><a href="dashboard.php" class="btn btn-blue">⬅ Kembali ke Dashboard</a></p>
    <p><a href="products.php" class="btn" style="background-color: #17a2b8;">📦 Kelola Produk</a></p>

    <hr style="margin: 20px 0;">

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Pilih Produk:</label>
            <select name="product_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['sku']) ?> - <?= htmlspecialchars($p['name']) ?> (Stok: <?= $p['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Jenis Transaksi:</label>
            <select name="type" required>
                <option value="IN">Barang Masuk (Stok Bertambah)</option>
                <option value="OUT">Barang Keluar (Stok Berkurang)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Jumlah (Quantity):</label>
            <input type="number" name="quantity" min="1" required placeholder="Masukkan jumlah barang">
        </div>

        <div class="form-group">
            <label>Keterangan / Catatan:</label>
            <textarea name="note" rows="3" placeholder="Contoh: Restock dari supplier / Barang terjual"></textarea>
        </div>

        <button type="submit" class="btn btn-green">Proses Transaksi</button>
    </form>
</div>

</body>
</html>