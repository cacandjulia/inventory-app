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
    die("Koneksi Database Gagal: " . $e->getMessage());
}

$message = '';
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $id   = isset($_POST['id']) ? $_POST['id'] : '';

    if (!empty($name)) {
        if (!empty($id)) {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
            $stmt->execute(['name' => $name, 'id' => $id]);
            $message = "Kategori berhasil diperbarui!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute(['name' => $name]);
            $message = "Kategori baru berhasil ditambahkan!";
        }
    } else {
        $message = "Nama kategori tidak boleh kosong!";
    }
}

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute(['id' => $edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kategori - Inventory App</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; }
        .container { max-width: 800px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: 0 auto; }
        h2 { color: #333; margin-top: 0; }
        .nav { margin-bottom: 20px; }
        .nav a { color: #007bff; text-decoration: none; font-weight: bold; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .form-inline { display: flex; gap: 10px; margin-bottom: 20px; }
        .form-inline input[type="text"] { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 8px 16px; background-color: #007bff; border: none; color: white; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-danger { background-color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <a href="dashboard.php">← Kembali ke Dashboard</a>
    </div>

    <h2>Kelola Kategori Barang</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form action="categories.php" method="POST" class="form-inline">
        <input type="hidden" name="id" value="<?= $edit_data ? $edit_data['id'] : ''; ?>">
        <input type="text" name="name" placeholder="Nama Kategori (Contoh: Elektronik, Makanan)" value="<?= $edit_data ? htmlspecialchars($edit_data['name']) : ''; ?>" required>
        <button type="submit" class="btn"><?= $edit_data ? 'Update' : 'Tambah'; ?></button>
        <?php if ($edit_data): ?>
            <a href="categories.php" class="btn btn-warning">Batal</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Nama Kategori</th>
                <th style="width: 150px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($categories) > 0): ?>
                <?php $no = 1; foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($cat['name']); ?></td>
                        <td>
                            <a href="categories.php?edit=<?= $cat['id']; ?>" class="btn btn-warning" style="padding: 4px 8px; font-size: 12px;">Edit</a>
                            <a href="category_delete.php?id=<?= $cat['id']; ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #777;">Belum ada data kategori.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>