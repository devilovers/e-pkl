<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php'; 
include "config.php";

$start = $_GET['start_date'] ?? '';
$end   = $_GET['end_date'] ?? '';
$id    = $_SESSION['id'] ?? 0;
$nis_nip = $_SESSION['nis_nip'] ?? '';
$siswa = $_SESSION['nama'] ?? '';

if(!$start || !$end){
    die("Tanggal tidak valid");
}

$stmt = $koneksi->prepare("SELECT * FROM catatan_kegiatan 
    WHERE siswa_id=? AND tanggal BETWEEN ? AND ?
    ORDER BY tanggal ASC");
$stmt->bind_param("iss", $id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$sql = "SELECT * 
        FROM data_du 
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
        LEFT JOIN tempat_pkl ON data_du.pkl_id=tempat_pkl.pkl_id 
        LEFT JOIN users ON data_du.du_penilai=users.id 
        WHERE data_awal.datawal_nis_nip=? LIMIT 1";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $nis_nip);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$sql2 = "SELECT * 
        FROM data_du 
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
        LEFT JOIN tempat_pkl ON data_du.pkl_id=tempat_pkl.pkl_id 
        LEFT JOIN users ON data_du.du_pembimbing=users.id 
        WHERE data_awal.datawal_nis_nip=? LIMIT 1";
$stmt = $koneksi->prepare($sql2);
$stmt->bind_param("s", $nis_nip);
$stmt->execute();
$data2 = $stmt->get_result()->fetch_assoc();

// Ambil konfigurasi sekolah
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();
$logoSurat = !empty($config['config_logosurat']) ? __DIR__."/upload/".$config['config_logosurat'] : "";

// Tambahkan CSS untuk tabel rapi
$style = '
<style>
    body { font-family: sans-serif; font-size: 11pt; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
    table, td, th { border: 1px solid black; }
    td, th { padding: 6px; vertical-align: top; }
    h2 { margin: 10px 0; }
    .center { text-align: center; }
    .no-border, .no-border td {
        border: none !important;
    }
        .top td {
    border: none !important;
    padding: 2px 4px;   /* atas-bawah 2px, kiri-kanan 4px */
    line-height: 1.2;   /* biar jarak antar baris rapat */
}
</style>
';

// Bagian header & biodata siswa
$headerContent = '
<center>'.($logoSurat ? '<img src="'.$logoSurat.'" style="max-width:100%;max-height:150px;">' : '').'</center>
<h3 style="text-align:center;">CATATAN KEGIATAN PKL</h3>
<table class="no-border top">
  <tr>
    <td style="width:30%;">Nama Peserta Didik</td>
    <td style="width:5%;">:</td>
    <td>'.$siswa.'</td>
  </tr>
  <tr>
    <td>Dunia Kerja Tempat PKL</td>
    <td>:</td>
    <td>'.$data['pkl_nama'].'</td>
  </tr>
  <tr>
    <td>Nama Instruktur</td>
    <td>:</td>
    <td>'.$data['nama'].'</td>
  </tr>
  <tr>
    <td>Nama Guru Pembimbing</td>
    <td>:</td>
    <td>'.$data2['nama'].'</td>
  </tr>
</table>
';

$mpdf = new \Mpdf\Mpdf();
$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 2,
    'margin_bottom' => 2,
    'margin_left'   => 10, // default aman
    'margin_right'  => 10,  // default aman
    'tempDir' => __DIR__ . '/tmp', // <- arahkan ke folder tmp buatanmu
]);
$html = $style . $headerContent;

$counter = 0; // hitung tabel isi per halaman

while($row = $res->fetch_assoc()){
    // Ambil timestamp
    $timestamp = strtotime($row['tanggal']);
    $hariIndo = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];
    $bulanIndo = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agt',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];

    $hariTanggal = $hariIndo[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulanIndo[date('m', $timestamp)] . ' ' . date('Y', $timestamp);
    $bulan       = $bulanIndo[date("m", $timestamp)];

$html .= '
    <table name="isi" style="table-layout:fixed; width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:20%; border-right:none;"><b>Hari/Tanggal:</b></td>
        <td style="width:25%; border-left:none; border-right:none;">'.$hariTanggal.'</td>
        <td style="width:10%; border-left:none; border-right:none;"><b>Bulan:</b></td>
        <td style="width:25%; border-left:none; border-right:none;">'.$bulan.'</td>
        <td style="width:20%; text-align:center; border-left:1px solid black;">Paraf Pembimbing</td>
      </tr>
      <tr>
        <td style="border-right:none;">Unit Pekerjaan:</td>
        <td colspan="3" style="border-left:none;">'.($row['unit_pekerjaan'] ?? '-').'</td>
        <td rowspan="7" style="width:20%; text-align:center; vertical-align:top;">
            '.(!empty($row['paraf_pembimbing']) 
                ? '<img src="'.$row['paraf_pembimbing'].'" style="max-width:80px;max-height:120px; margin-top:10px;">' 
                : '').'
        </td>
      </tr>
      <tr><td colspan="4"><b>Perencanaan Kegiatan</b></td></tr>
      <tr>
        <td colspan="4" style="word-wrap:break-word; word-break:break-word; white-space:pre-line; vertical-align:top;">
            '.($row['perencanaan'] ?? '-').'
        </td>
      </tr>
      <tr><td colspan="4"><b>Pelaksanaan Kegiatan / Uraian Pekerjaan</b></td></tr>
      <tr>
        <td colspan="4" style="word-wrap:break-word; word-break:break-word; white-space:pre-line; vertical-align:top;">
            '.($row['pelaksanaan'] ?? '-').'
        </td>
      </tr>
      <tr>
        <td colspan="4" style="word-wrap:break-word; word-break:break-word; white-space:pre-line; vertical-align:top;">
            <b>Catatan Instruktur</b><br>'.($row['catatan_instruktur'] ?? '-').'
        </td>
      </tr>
    </table>
';



    $counter++;

    // Jika sudah 3 tabel, cetak halaman & reset
    if($counter >= 3){
        $mpdf->WriteHTML($html);
        $mpdf->AddPage();
        $html = $style . $headerContent; // reset dengan header lagi
        $counter = 0;
    }
}

// Cetak sisa tabel kalau ada
if($counter > 0){
    $mpdf->WriteHTML($html);
}

$mpdf->Output("Laporan_PKL.pdf","I");
