<?php
session_start();
include 'config.php';

$nis = $_SESSION['nis_nip'] ?? '';
$kesan = $_POST['kesan'] ?? '';
$id_penilai = $_SESSION['id'] ?? null;

$response = ["success" => false, "message" => "Gagal menyimpan"];

if ($nis && $id_penilai) {
    // cek apakah sudah ada
    $stmt = $koneksi->prepare("SELECT 1 FROM catatan_penting WHERE nis_nip_siswa_cat=?");
    $stmt->bind_param("s", $nis);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;

    if ($exists) {
        $stmt = $koneksi->prepare("UPDATE catatan_penting 
                                   SET kesan_siswa_cat=? 
                                   WHERE nis_nip_siswa_cat=?");
        $stmt->bind_param("ss", $kesan, $nis);
    } else {
        $stmt = $koneksi->prepare("INSERT INTO catatan_penting 
                                   (nis_nip_siswa_cat, kesan_siswa_cat) 
                                   VALUES (?, ?)");
        $stmt->bind_param("ss",$nis, $kesan);
    }

    if ($stmt->execute()) {
        $response = ["success" => true, "message" => "Catatan berhasil disimpan"];
    }
}

echo json_encode($response);
