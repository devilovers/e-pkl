<?php
// koneksi database

$id = $_SESSION['id'] ?? null;
if (!$id) {
    die("Anda belum login.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordBaru = $_POST['katasandi'];

    if (!empty($passwordBaru)) {
        // hash password agar lebih aman
        $passwordHash = password_hash($passwordBaru, PASSWORD_DEFAULT);

        $stmt = $koneksi->prepare("UPDATE users SET katasandi=? WHERE id=?");
        $stmt->bind_param("si", $passwordHash, $id);
        $stmt->execute();

        echo "<script>alert('Password berhasil diganti!'); window.location='dashboard_admin.php';</script>";
        exit;
    } else {
        echo "<script>alert('Password baru tidak boleh kosong!');</script>";
    }
}
?>

<h2><center>GANTI PASSWORD</center></h2>
<form method="POST">
    <label>Password Baru</label><br>
    <input type="password" name="katasandi" required><br><br>

    <button type="submit">Ganti Password</button>
</form>