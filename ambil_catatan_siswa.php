<?php
session_start();
include 'config.php';

$nis = $_POST['nis'] ?? '';
$nis = $_SESSION['nis_nip'] ?? '';
$id_penilai = $_SESSION['id'] ?? null;

$response = ["pesan" => "", "kesan" => ""];

if ($nis && $id_penilai) {
    $stmt = $koneksi->prepare("SELECT pesan_penilai_cat, kesan_siswa_cat 
                               FROM catatan_penting 
                               WHERE nis_nip_siswa_cat=? LIMIT 1");
    $stmt->bind_param("s", $nis,);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response["pesan"] = $row['pesan_penilai_cat'];
        $response["kesan"] = $row['kesan_siswa_cat'];
    }
}
echo json_encode($response);
