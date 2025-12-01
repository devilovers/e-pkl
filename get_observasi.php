<?php
header('Content-Type: application/json; charset=utf-8');
require_once "config.php";

$id_jur   = isset($_GET['id_jur']) ? (int)$_GET['id_jur'] : 0;
$id_siswa = isset($_GET['id_siswa']) ? (int)$_GET['id_siswa'] : 0;  // optional

if ($id_jur <= 0) {
    echo json_encode(["error" => "Parameter id_jur tidak ditemukan atau tidak valid"]);
    exit;
}

try {
    // Ambil semua grup berdasarkan jurusan
    $sql = "SELECT DISTINCT hg.id_hg, hg.nama_hg
            FROM header_grup hg
            JOIN observasi_detail od ON od.grup_obs = hg.id_hg
            WHERE od.id_jur_obs = ?
            ORDER BY hg.id_hg";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare gagal (grup): " . $koneksi->error);
    }
    $stmt->bind_param("i", $id_jur);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $id_hg = (int)$row['id_hg'];

        // Ambil semua indikator dari grup ini untuk jurusan tsb
        $sql2 = "SELECT od.id_obs, od.indikator_obs
                 FROM observasi_detail od
                 WHERE od.grup_obs = ? AND od.id_jur_obs = ?
                 ORDER BY od.id_obs";
        $stmt2 = $koneksi->prepare($sql2);
        if (!$stmt2) {
            throw new Exception("Prepare gagal (indikator): " . $koneksi->error);
        }
        $stmt2->bind_param("ii", $id_hg, $id_jur);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        $indikator = [];
        while ($row2 = $result2->fetch_assoc()) {
            // default nilai kosong
            $row2['capai']     = null;
            $row2['deskripsi'] = null;

            // jika ada id_siswa, cek apakah sudah ada isian sebelumnya di observasi_siswa
            if ($id_siswa > 0) {
                $sql3 = "SELECT ketercapaian_oss AS capai, deskripsi_oss AS deskripsi
                         FROM observasi_siswa
                         WHERE id_siswa_oss = ? AND id_observasi_detail = ?
                         LIMIT 1";
                $stmt3 = $koneksi->prepare($sql3);
                if ($stmt3) {
                    $stmt3->bind_param("ii", $id_siswa, $row2['id_obs']);
                    $stmt3->execute();
                    $result3 = $stmt3->get_result();
                    if ($old = $result3->fetch_assoc()) {
                        $row2['capai']     = $old['capai'];
                        $row2['deskripsi'] = $old['deskripsi'];
                    }
                    $stmt3->close();
                } else {
                    // jika prepare gagal, jangan hentikan seluruh proses — tapi catat untuk debugging
                    error_log("Prepare gagal (cek observasi_siswa): " . $koneksi->error);
                }
            }

            $indikator[] = $row2;
        }

        $stmt2->close();

        $row['indikator'] = $indikator;
        $data[] = $row;
    }

    $stmt->close();

    // Keluaran: **ARRAY** grup -> cocok untuk `rows.forEach(...)` di frontend
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error"  => "Terjadi kesalahan server",
        "detail" => $e->getMessage(),
        "db_error" => $koneksi->error // berguna saat debugging DB
    ]);
}
