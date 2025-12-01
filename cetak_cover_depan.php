<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
include "config.php";
// Fungsi konversi angka ke Romawi
function toRoman($number) {
    $map = [
        'M'  => 1000,
        'CM' => 900,
        'D'  => 500,
        'CD' => 400,
        'C'  => 100,
        'XC' => 90,
        'L'  => 50,
        'XL' => 40,
        'X'  => 10,
        'IX' => 9,
        'V'  => 5,
        'IV' => 4,
        'I'  => 1
    ];
    $returnValue = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if($number >= $int) {
                $number -= $int;
                $returnValue .= $roman;
                break;
            }
        }
    }
    return $returnValue;
}
$nis_nip = $_GET['nis_nip'] ?? '';
if (!$nis_nip) die("NIS/NIP tidak ditemukan.");

// Ambil data siswa + jurusan + pkl
$stmt = $koneksi->prepare("SELECT da.*, j.jur_nama, tp.pkl_nama, u.nama as nama_user, u.nis_nip as nisnip_user
                           FROM data_awal da
                           LEFT JOIN jurusan j ON da.datawal_jurusan=j.jur_id 
                           LEFT JOIN tempat_pkl tp ON da.datawal_id_siswa=tp.pkl_id 
                           LEFT JOIN users u ON da.datawal_nis_nip=u.nis_nip
                           WHERE da.datawal_nis_nip=? LIMIT 1");
$stmt->bind_param("s", $nis_nip);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
///ambil data lainnya 
// Ambil data siswa & dunia usaha
$sqlx = "SELECT * 
        FROM data_du 
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
        LEFT JOIN tempat_pkl ON data_du.pkl_id=tempat_pkl.pkl_id 
        LEFT JOIN users ON data_du.du_pembimbing=users.id 
        WHERE data_awal.datawal_nis_nip=? LIMIT 1";
$stmt = $koneksi->prepare($sqlx);
$stmt->bind_param("s", $nis_nip);
$stmt->execute();
$datax = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data siswa tidak ditemukan.");

// Ambil konfigurasi sekolah
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();
$namaSekolah = $config['config_namasekolah'] ?? 'SMK Negeri 4 Banjarmasin';

// Path logo sekolah (static atau dari db)
$logoSekolah = __DIR__ . "/logosmkn4bjm.png";
$startYear = date('Y', strtotime($datax['du_mulai'])); 
$endYear   = $startYear + 1;
$tahunAjar = $startYear . "-" . $endYear;

// Buat HTML Header
$html = '
<div style="text-align:center; font-family: Arial, sans-serif;">
    <h1 style="margin:0;">JURNAL KEGIATAN SISWA</h1>
    <h1 style="margin:0;">PRAKTIK KERJA LAPANGAN (PKL)</h1>
    <h1 style="margin:0;">TAHUN PELAJARAN<br>'.$tahunAjar.'</h1>
    <br>
    <img src="'.$logoSekolah.'" alt="Logo Sekolah" style="width:250px; height:auto;">
</div>

<br><br>
<br>
<table border="0" cellpadding="6" cellspacing="0" width="100%" style="font-size:14pt; font-family: Arial, sans-serif;">
  <tr><td width="35%">NAMA</td><td>: '.$data['nama_user'].'</td></tr>
  <tr><td>KELAS</td><td>: '.toRoman($data['datawal_kelas']).'</td></tr>
  <tr><td>NIS</td><td>: '.$data['nisnip_user'].'</td></tr>
  <tr><td>KONS. KEAHLIAN</td><td>: '.$data['jur_nama'].'</td></tr>
  <tr><td>DUNIA INDUSTRI</td><td>: '.$datax['pkl_nama'].'</td></tr>
  <tr><td>PEMBIMBING</td><td>: '.$datax['nama'].'</td></tr>
</table>

<br><br><br><br>
<div style="text-align:center; font-family: Arial, sans-serif;">
    <h2 style="margin:0;">PEMERINTAH PROVINSI KALIMANTAN SELATAN</h2>
    <h2 style="margin:0;">DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
    <h2 style="margin:0;">SMK NEGERI 4 BANJARMASIN</h2>
    <br>
</div>
    <br><br>
    <div style="text-align:center; font-family: Arial, sans-serif; font-size:11pt;">
    Jl. Brigjend H. Hasan Basri No.07 Banjarmasin, Kalimantan Selatan, Indonesia<br>
    Telp. 0511-5209999<br>Email:<i><u>info@smkn4bjm.sch.id</u></i><br>Website: smkn4bjm.sch.id
</div>
';

// Buat PDF
try {
    $tmpDir = __DIR__ . '/tmp';
    if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
        throw new \Mpdf\MpdfException("Folder 'tmp/' tidak ditemukan atau tidak dapat ditulis.");
    }

    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_top' => 10,
        'margin_bottom' => 15,
        'margin_left' => 15,
        'margin_right' => 15,
        'tempDir' => $tmpDir
    ]);
    $mpdf->WriteHTML($html);
    $mpdf->Output("Jurnal_PKL_".$data['datawal_nis_nip'].".pdf", "I");

} catch (\Mpdf\MpdfException $e) {
    echo "<script>alert('Terjadi kesalahan saat membuat PDF: " . addslashes($e->getMessage()) . "');</script>";
}