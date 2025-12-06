<?php
session_start();
require_once 'config.php'; 

header('Content-Type: application/json');

try {
    if (!isset($_POST['pkl_id']) || empty($_POST['pkl_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID tempat PKL tidak ditemukan']);
        exit;
    }

    $pkl_id = intval($_POST['pkl_id']);

    $stmt = $koneksi->prepare("
        SELECT 
           users.id AS datawal_id_siswa,
           users.nama AS datawal_nama
        FROM data_du LEFT JOIN users ON data_du.du_siswa_id=users.id WHERE data_du.pkl_id=? 
    ");
    $stmt->bind_param("i", $pkl_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada siswa di tempat PKL ini']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Data siswa berhasil diambil', 'data' => $data]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: '.$e->getMessage()]);
    exit;
}
