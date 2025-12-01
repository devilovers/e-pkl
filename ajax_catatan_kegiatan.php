<?php
ob_start();
session_start();
include "config.php";

header("Content-Type: application/json");
$response = ["status"=>"error", "message"=>"Terjadi kesalahan", "data"=>[]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Ambil daftar siswa berdasarkan DU/DI ---
    if ($action === "get_siswa") {
        $pkl_id = $_POST['pkl_id'] ?? 0;
        $pembimbing = $_SESSION['id'] ?? 0;

        $stmt = $koneksi->prepare("
            SELECT u.id AS siswa_id, u.nama
            FROM data_du du
            INNER JOIN users u ON du.du_siswa_id = u.id
            WHERE du.pkl_id = ? AND du.du_pembimbing = ?
        ");
        $stmt->bind_param("ii", $pkl_id, $pembimbing);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        $response = ["status"=>"success", "message"=>"Data siswa berhasil diambil", "data"=>$data];
    }

    // --- Ambil catatan kegiatan siswa ---
    elseif ($action === "get_catatan") {
        $siswa_id = $_POST['siswa_id'] ?? 0;

        $stmt = $koneksi->prepare("
            SELECT 
                ck.tanggal,
                ck.perencanaan,
                ck.pelaksanaan,
                ck.catatan_instruktur,
                ck.paraf_pembimbing 
            FROM catatan_kegiatan ck
            WHERE ck.siswa_id = ?
            ORDER BY ck.tanggal DESC
        ");
        $stmt->bind_param("i", $siswa_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        $response = ["status"=>"success", "message"=>"Catatan berhasil diambil", "data"=>$data];
    }
}

// Bersihkan output buffer agar tidak ada HTML yang keluar
while (ob_get_level()) ob_end_clean();
echo json_encode($response);
exit;
?>
