<?php
require "config.php";

$ids = $_POST['ids'] ?? [];
$pesan = $_POST['pesan'] ?? '';
$status = $_POST['status'] ?? '';

$response = ["status" => "error", "message" => "Tidak ada data yang diperbarui."];

if (!empty($ids) && is_array($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "UPDATE daftar_hadir SET status_had=?, keterangan_penilai_had=? WHERE id_had IN ($placeholders)";
    $stmt = $koneksi->prepare($sql);

    if ($stmt) {
        $params = array_merge([$status, $pesan], $ids);
        $stmt->bind_param("ss" . $types, ...$params);

        if ($stmt->execute()) {
            $response = ["status" => "success", "message" => "Catatan berhasil disimpan untuk data terpilih."];
        } else {
            $response = ["status" => "error", "message" => "Gagal memperbarui data: " . $stmt->error];
        }
    } else {
        $response = ["status" => "error", "message" => "Gagal menyiapkan query."];
    }
}

header("Content-Type: application/json");
echo json_encode($response);
