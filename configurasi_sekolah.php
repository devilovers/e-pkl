<?php
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_sekolah = $_POST['config_namasekolah'];

    // proses upload logo sekolah
    $logo = $config['config_logosekolah'] ?? '';
    if (isset($_FILES['config_logosekolah']) && $_FILES['config_logosekolah']['error'] === 0) {
        $ext = pathinfo($_FILES['config_logosekolah']['name'], PATHINFO_EXTENSION);
        $logo = 'logo_sekolah_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['config_logosekolah']['tmp_name'], 'upload/' . $logo);
    }

    // proses upload logo surat
    $logosurat = $config['config_logosurat'] ?? '';
    if (isset($_FILES['config_logosurat']) && $_FILES['config_logosurat']['error'] === 0) {
        $ext2 = pathinfo($_FILES['config_logosurat']['name'], PATHINFO_EXTENSION);
        $logosurat = 'logo_surat_' . time() . '.' . $ext2;
        move_uploaded_file($_FILES['config_logosurat']['tmp_name'], 'upload/' . $logosurat);
    }

    if ($config) {
        // update
        $stmt = $koneksi->prepare("UPDATE configurasi 
                                   SET config_namasekolah=?, config_logosekolah=?, config_logosurat=? 
                                   WHERE config_id=?");
        $stmt->bind_param("sssi", $nama_sekolah, $logo, $logosurat, $config['config_id']);
    } else {
        // insert
        $stmt = $koneksi->prepare("INSERT INTO configurasi (config_namasekolah, config_logosekolah, config_logosurat) 
                                   VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama_sekolah, $logo, $logosurat);
    }
    $stmt->execute();

    echo "<script>alert('Konfigurasi berhasil disimpan!'); window.location='dashboard_admin.php?page=configurasi_sekolah';</script>";
    exit;
}
?>

<h2><center>CONFIGURASI SEKOLAH</center></h2>
<form method="POST" enctype="multipart/form-data">
    <label>Nama Sekolah</label><br>
    <input type="text" name="config_namasekolah" 
           value="<?php echo $config['config_namasekolah'] ?? ''; ?>" required><br><br>

    <label>Logo Sekolah</label><br>
    <?php if (!empty($config['config_logosekolah'])): ?>
        <img src="upload/<?php echo $config['config_logosekolah']; ?>" width="100"><br>
    <?php endif; ?>
    <input type="file" name="config_logosekolah" accept="image/*"><br><br>

    <label>Logo Surat</label><br>
    <?php if (!empty($config['config_logosurat'])): ?>
        <img src="upload/<?php echo $config['config_logosurat']; ?>" width="100"><br>
    <?php endif; ?>
    <input type="file" name="config_logosurat" accept="image/*"><br><br>

    <button type="submit">Simpan</button>
</form>
