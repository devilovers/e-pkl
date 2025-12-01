<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php'; // pastikan mPDF sudah di-install: composer require mpdf/mpdf

$nis   = $_GET['nis']   ?? '';
$pesan = $_GET['pesan'] ?? '';
$kesan = $_GET['kesan'] ?? '';

// ambil nama siswa dari database berdasarkan NIS (opsional)
include "config.php";
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();
$logoSurat = !empty($config['config_logosurat']) ? __DIR__."/upload/".$config['config_logosurat'] : "";
$sql = "SELECT * 
        FROM data_du 
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
        LEFT JOIN tempat_pkl ON data_du.pkl_id=tempat_pkl.pkl_id 
        LEFT JOIN users ON data_du.du_penilai=users.id 
        WHERE data_awal.datawal_nis_nip=? LIMIT 1";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $nis);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$nama = "";
$stmt = $koneksi->prepare("SELECT * FROM catatan_penting WHERE nis_nip_siswa_cat = ?");
$stmt->bind_param("s", $nis);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $pesan = $row['pesan_penilai_cat'];
    $kesan = $row['kesan_siswa_cat'];
}
$stmt->close();

$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_top' => 5,
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_bottom' => 10,
     'tempDir' => __DIR__ . '/tmp' // <- arahkan ke folder tmp buatanmu
]);

// isi konten PDF
$html = '
<center>'.($logoSurat ? '<img src="'.$logoSurat.'" style="width:100%;max-height:150px;">' : '').'</center>
<h3 style="text-align:center;">CATATAN PENTING</h3><br>
<p><b>Pesan dari Dunia Industri / Dunia Usaha :</b><br><strong>('.$data['nama'].')</strong><br><br><p style="font-style: italic; border-bottom: 2px dotted black; padding-bottom: 2px;">'.$pesan.'<p></p>
<p><b>Kesan dari Siswa:</b><br><strong>('.$_SESSION['nama'].')</strong><br><br><p style="font-style: italic; border-bottom: 2px dotted black; padding-bottom: 2px;">'.$kesan.'<p></p>
';

$mpdf->WriteHTML($html);
$mpdf->Output("catatan_$nis.pdf", "I"); // "I" = langsung tampil di browser, "D" = download
