<?php
require "config.php";
$id_siswa = $_POST['id_siswa'] ?? 0;

$data = [];
if ($id_siswa) {
    $stmt = $koneksi->prepare("SELECT * FROM daftar_hadir WHERE id_siswa_had=? ORDER BY tanggal_had DESC");
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}
header("Content-Type: application/json");
echo json_encode($data);
