<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php'; 
include "config.php";

$id_siswa = $_SESSION['id'] ?? 0;   // ambil dari GET
$start    = $_GET['start_date'] ?? '';
$end      = $_GET['end_date'] ?? '';
$nis_nip = $_SESSION['nis_nip'] ?? 0;

if(!$id_siswa || !$start || !$end){
    die("Parameter tidak valid");
}

function formatTanggalIndo($tanggalDb) {
    if(!$tanggalDb || $tanggalDb == "0000-00-00") return "-";

    $bulanIndo = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agt',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];

    $tglObj = strtotime($tanggalDb);
    return date("d", $tglObj) . " " . $bulanIndo[date("m", $tglObj)] . " " . date("Y", $tglObj);
}

// --- Ambil data siswa, DU, pembimbing, penilai ---
$sql = "SELECT data_awal.datawal_nama, data_awal.datawal_nis_nip, 
               tempat_pkl.pkl_nama, 
               u1.nama AS nama_penilai, 
               u2.nama AS nama_pembimbing
        FROM data_du
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa
        LEFT JOIN tempat_pkl ON data_du.pkl_id = tempat_pkl.pkl_id
        LEFT JOIN users u1 ON data_du.du_penilai = u1.id
        LEFT JOIN users u2 ON data_du.du_pembimbing = u2.id
        WHERE data_du.du_siswa_id=? LIMIT 1";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// --- Ambil konfigurasi sekolah ---
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();
$logoSurat = !empty($config['config_logosurat']) ? __DIR__."/upload/".$config['config_logosurat'] : "";

// --- Ambil data daftar hadir ---
$stmt = $koneksi->prepare("SELECT * FROM daftar_hadir 
                           WHERE id_siswa_had=? AND tanggal_had BETWEEN ? AND ?
                           ORDER BY tanggal_had ASC");
$stmt->bind_param("sss", $nis_nip, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

// --- CSS ---
$style = '
<style>
    body { font-family: sans-serif; font-size: 10pt; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
    table, td, th { border: 1px solid black; }
    td, th { padding: 6px; vertical-align: top; font-size:9pt; }
    h3 { margin: 10px 0; }
    .no-border td { border: none !important; }
</style>
';

// --- Header ---
$headerContent = '
<center>'.($logoSurat ? '<img src="'.$logoSurat.'" style="max-width:100%;max-height:120px;">' : '').'</center>
<h3 style="text-align:center;">DAFTAR HADIR PKL</h3>
<table class="no-border">
  <tr>
    <td style="width:30%;">Nama Peserta Didik</td>
    <td style="width:5%;">:</td>
    <td>'.$data['datawal_nama'].'</td>
  </tr>
  <tr>
    <td>NIS/NIP</td>
    <td>:</td>
    <td>'.$data['datawal_nis_nip'].'</td>
  </tr>
  <tr>
    <td>Dunia Kerja Tempat PKL</td>
    <td>:</td>
    <td>'.$data['pkl_nama'].'</td>
  </tr>
  <tr>
    <td>Nama Instruktur</td>
    <td>:</td>
    <td>'.$data['nama_penilai'].'</td>
  </tr>
  <tr>
    <td>Nama Guru Pembimbing</td>
    <td>:</td>
    <td>'.$data['nama_pembimbing'].'</td>
  </tr>
</table>
';

// --- Isi tabel daftar hadir ---
$html = $style . $headerContent;
$html .= '
<table>
<thead>
<tr>
  <th width="5%">No</th>
  <th width="15%">Tanggal</th>
  <th width="15%">Jam Masuk</th>
  <th width="15%">Jam Keluar</th>
  <th width="40%">Keterangan</th>
  <th width="10%">Status</th>
</tr>
</thead>
<tbody>
';

$no = 1;
while($row = $res->fetch_assoc()){
    $html .= '
    <tr>
      <td>'.$no++.'</td>
      <td>'.formatTanggalIndo($row['tanggal_had']).'</td>
      <td>'.$row['jam_masuk_had'].'</td>
      <td>'.$row['jam_keluar_had'].'</td>
      <td>'.$row['keterangan_had'].'</td>
      <td>'.$row['status_had'].'</td>
    </tr>';
}

$html .= '</tbody></table>';

// --- Cetak PDF ---
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_top' => 5,
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_bottom' => 10
]);
$mpdf->WriteHTML($html);
$mpdf->Output("Daftar_Hadir_PKL.pdf","I");
