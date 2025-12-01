<?php
session_start();
require "config.php";

$penilai_id = $_SESSION['id'] ?? null;
if (!$penilai_id) {
    http_response_code(400);
    exit("Sesi penilai tidak ditemukan");
}

$id_siswa = $_POST['id_siswa'] ?? null;
$capai = $_POST['capai'] ?? [];
$deskripsi = $_POST['deskripsi'] ?? [];
$ttd = $_POST['ttd'] ?? null;

if (!$id_siswa) {
    http_response_code(400);
    exit("ID siswa tidak valid");
}

try {
    // --- Simpan observasi per indikator ---
    foreach ($capai as $id_obs => $val) {
        $desc = $deskripsi[$id_obs] ?? '';

        // cek apakah sudah ada datanya
        $cek = $koneksi->prepare("SELECT id_oss FROM observasi_siswa WHERE id_siswa_oss=? AND id_observasi_detail=?");
        $cek->bind_param("ii", $id_siswa, $id_obs);
        $cek->execute();
        $res = $cek->get_result();

        if ($res->num_rows > 0) {
            // update
            $upd = $koneksi->prepare("UPDATE observasi_siswa 
                                      SET ketercapaian_oss=?, deskripsi_oss=?, flag_oss='T' 
                                      WHERE id_siswa_oss=? AND id_observasi_detail=?");
            $upd->bind_param("ssii", $val, $desc, $id_siswa, $id_obs);
            $upd->execute();
            $upd->close();
        } else {
            // insert
            $ins = $koneksi->prepare("INSERT INTO observasi_siswa (id_siswa_oss, id_observasi_detail, ketercapaian_oss, deskripsi_oss, flag_oss) 
                                      VALUES (?,?,?,?, 'T')");
            $ins->bind_param("iiss", $id_siswa, $id_obs, $val, $desc);
            $ins->execute();
            $ins->close();
        }
        $cek->close();
    }

    // --- Simpan tanda tangan ---
    if ($ttd) {
        // cek apakah sudah ada ttd untuk siswa ini
        $cekTtd = $koneksi->prepare("SELECT id_siswa_ttd_oss FROM observasi_ttd WHERE id_siswa_ttd_oss=?");
        $cekTtd->bind_param("i", $id_siswa);
        $cekTtd->execute();
        $resTtd = $cekTtd->get_result();

        if ($resTtd->num_rows > 0) {
            $updTtd = $koneksi->prepare("UPDATE observasi_ttd SET ttd_pembimbing_oss=? WHERE id_siswa_ttd_oss=?");
            $updTtd->bind_param("si", $ttd, $id_siswa);
            $updTtd->execute();
            $updTtd->close();
        } else {
            $insTtd = $koneksi->prepare("INSERT INTO observasi_ttd (id_siswa_ttd_oss, ttd_pembimbing_oss) VALUES (?,?)");
            $insTtd->bind_param("is", $id_siswa, $ttd);
            $insTtd->execute();
            $insTtd->close();
        }
        $cekTtd->close();
    }

    echo "Data observasi berhasil disimpan";
} catch (Exception $e) {
    http_response_code(500);
    echo "Terjadi kesalahan: " . $e->getMessage();
}
