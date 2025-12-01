<?php
include "config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $du_id = $_POST['du_id'] ?? null;
    $ttd = $_POST['ttd_pembimbing'] ?? null;

    if ($du_id && $ttd) {
        // langsung simpan base64 ke field ttd_pembimbing (TEXT)
        $stmt = $koneksi->prepare("UPDATE data_du SET ttd_pembimbing=? WHERE du_id=?");
        $stmt->bind_param("si", $ttd, $du_id);
        if ($stmt->execute()) {
            echo "<script>alert('Tanda tangan berhasil disimpan!');window.location='".$_SERVER['HTTP_REFERER']."';</script>";
        } else {
            echo "Gagal menyimpan.";
        }
    } else {
        echo "Data tidak lengkap.";
    }
}
?>
