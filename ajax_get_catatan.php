<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$penilai_id = $_SESSION['id'] ?? null;
$siswa_id = $_POST['siswa_id'] ?? null;

if (!$penilai_id || !$siswa_id) {
    echo json_encode(['status'=>'error','message'=>'Parameter tidak lengkap']);
    exit;
}

$stmt = $koneksi->prepare("
    SELECT ck.*, da.datawal_nama
    FROM catatan_kegiatan ck
    LEFT JOIN data_awal da ON ck.siswa_id = da.datawal_id_siswa
    LEFT JOIN data_du du ON ck.siswa_id = du.du_siswa_id
    WHERE du.du_pembimbing = ? AND ck.siswa_id = ?
    ORDER BY ck.tanggal DESC
");
$stmt->bind_param("ii", $penilai_id, $siswa_id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

if ($data) echo json_encode(['status'=>'success','data'=>$data]);
else echo json_encode(['status'=>'error','message'=>'Catatan tidak ditemukan']);
