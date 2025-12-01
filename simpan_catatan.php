<?php
session_start();
include 'config.php';

$nis = $_POST['nis'] ?? '';
$pesan = $_POST['pesan'] ?? '';
$id_penilai = $_SESSION['id'] ?? null;

$response = ["success" => false, "message" => "Gagal menyimpan"];

if ($nis && $id_penilai) {
    // cek apakah sudah ada
    $stmt = $koneksi->prepare("SELECT 1 FROM catatan_penting WHERE nis_nip_siswa_cat=? AND id_penilai_cat=?");
    $stmt->bind_param("si", $nis, $id_penilai);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;

    if ($exists) {
        $stmt = $koneksi->prepare("UPDATE catatan_penting SET pesan_penilai_cat=? 
                                   WHERE nis_nip_siswa_cat=? AND id_penilai_cat=?");
        $stmt->bind_param("ssi", $pesan, $nis, $id_penilai);
    } else {
        $stmt = $koneksi->prepare("INSERT INTO catatan_penting (id_penilai_cat, nis_nip_siswa_cat, pesan_penilai_cat)
                                   VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $id_penilai, $nis, $pesan);
    }

    if ($stmt->execute()) {
        $response = ["success" => true, "message" => "Catatan berhasil disimpan"];
    }
}

echo json_encode($response);
