<?php
session_start();
include 'config.php';

$nis = $_POST['nis'] ?? '';
$id_penilai = $_SESSION['id'] ?? null;

$response = ["pesan" => ""];

if ($nis && $id_penilai) {
    $stmt = $koneksi->prepare("SELECT pesan_penilai_cat FROM catatan_penting 
                               WHERE nis_nip_siswa_cat=? AND id_penilai_cat=? LIMIT 1");
    $stmt->bind_param("si", $nis, $id_penilai);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response["pesan"] = $row['pesan_penilai_cat'];
    }
}
echo json_encode($response);
