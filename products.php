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

// Tambah Produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $sku         = trim($_POST['sku']);
    $name        = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $stock       = (int)$_POST['stock'];
    $price       = (float)$_POST['price'];

    if (empty($sku) || empty($name) || empty($category_id)) {
        $error = "SKU, Nama Produk, dan Kategori wajib diisi!";
    } elseif ($stock < 0 || $price < 0) {
        $error = "Stok dan Harga tidak boleh negatif!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (sku, name, category_id, stock, price) VALUES (:sku, :name, :category_id, :stock, :price)");
            $stmt->execute([
                'sku'         => $sku,
                'name'        => $name,
                'category_id' => $category_id,
                'stock'       => $stock,
                'price'       => $price
            ]);
            $success = "Produk berhasil ditambahkan!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Error: SKU '$sku' sudah terdaftar/digunakan!";
            } else {
                $error = "Gagal menyimpan: " . $e->getMessage();
            }
        }
    }
}

// Ambil Keyword Pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil Kategori untuk Dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Ambil Daftar Produk dengan Fitur Search (Prepared Statement agar aman dari SQL Injection)
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.name LIKE :search OR p.sku LIKE :search 
                           ORDER BY p.id DESC");
    $stmt->execute(['search' => "%$search%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $query = "SELECT p.*, c.name AS category_name 
              FROM products p 
              JOIN categories c ON p.category_id = c.id 
              ORDER BY p.id DESC";
    $products = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Produk - Inventory App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 950px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-green { background-color: #28a745; }
        .btn-blue { background-color: #007bff; }
        .btn-search { background-color: #17a2b8; }
        .error { color: red; margin-bottom: 10px; background: #ffe6e6; padding: 10px; border-radius: 4px; }
        .success { color: green; margin-bottom: 10px; background: #e6ffed; padding: 10px; border-radius: 4px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; }
        .search-box { margin-top: 20px; margin-bottom: 15px; display: flex; gap: 10px; }
        .search-box input { flex: 1; padding: 8px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📦 Kelola Data Produk</h2>
    <p><a href="dashboard.php" class="btn btn-blue">⬅ Kembali ke Dashboard</a></p>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <h3>Tambah Produk Baru</h3>
    <form method="POST">
        <div class="form-group">
            <label>SKU (Kode Unik Produk):</label>
            <input type="text" name="sku" required placeholder="Contoh: PRD-001">
        </div>
        <div class="form-group">
            <label>Nama Produk:</label>
            <input type="text" name="name" required placeholder="Contoh: Paracetamol 500mg">
        </div>
        <div class="form-group">
            <label>Kategori:</label>
            <select name="category_id" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Stok Awal:</label>
            <input type="number" name="stock" value="0" min="0" required>
        </div>
        <div class="form-group">
            <label>Harga (Rp):</label>
            <input type="number" step="0.01" name="price" value="0" min="0" required>
        </div>
        <button type="submit" name="add_product" class="btn btn-green">Simpan Produk</button>
    </form>

    <hr style="margin-top: 30px;">

    <h3>Daftar Produk</h3>

    <!-- Form Search -->
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Cari berdasarkan Nama Produk atau SKU..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-search">Cari</button>
        <?php if ($search !== ''): ?>
            <a href="products.php" class="btn" style="background-color: #6c757d; text-decoration: none; display:inline-block; padding: 8px 12px;">Reset</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Harga</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['sku']) ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category_name']) ?></td>
                        <td><?= htmlspecialchars($p['stock']) ?></td>
                        <td>Rp <?= number_format($p['price'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Tidak ada data produk yang ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>