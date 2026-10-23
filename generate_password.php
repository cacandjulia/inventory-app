<?php
$password_kamu = 'cacabaikhati'; 

$hash = password_hash($password_kamu, PASSWORD_BCRYPT);

echo "Password asli kamu: <b>" . $password_kamu . "</b><br><br>";
echo "Copy kode hash di bawah ini untuk dimasukkan ke Database:<br>";
echo "<textarea rows='3' cols='60'>" . $hash . "</textarea>";
?>