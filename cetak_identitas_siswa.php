<?php
// Aktifkan error reporting (agar bisa dilihat di browser)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
include "config.php";

$nis_nip = $_GET['nis_nip'] ?? '';
if (!$nis_nip) die("NIS/NIP tidak ditemukan.");

// Ambil data siswa + jurusan
$stmt = $koneksi->prepare("SELECT * FROM data_awal 
                           LEFT JOIN jurusan ON data_awal.datawal_jurusan=jurusan.jur_id 
                           WHERE datawal_nis_nip=? LIMIT 1");
$stmt->bind_param("s", $nis_nip);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data siswa tidak ditemukan.");

// Ambil konfigurasi sekolah
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();

$tanggal = date("d-m-Y");
$namaSekolah = $config['config_namasekolah'] ?? 'Nama Sekolah';
$logoSurat   = !empty($config['config_logosurat']) ? __DIR__."/upload/".$config['config_logosurat'] : "";

// Pastikan foto & ttd ada
$foto = !empty($data['datawal_photo']) ? __DIR__."/upload/".$data['datawal_photo'] : "";
$ttd  = $data['datawal_ttd']; // base64 atau path file

// Buat HTML
$html = '
<center>'.($logoSurat ? '<img src="'.$logoSurat.'" style="width:100%;max-height:150px;">' : '').'</center>
<h2 style="text-align:center;">IDENTITAS SISWA</h2>
<table border="0" cellpadding="6" cellspacing="0" width="100%">
  <tr><td>A.</td><td>NIS</td><td>:</td><td>'.$data['datawal_nis_nip'].'</td></tr>
  <tr><td>B.</td><td>Nama Lengkap</td><td>:</td><td>'.$data['datawal_nama'].'</td></tr>
  <tr><td>C.</td><td>Tempat/Tanggal Lahir</td><td>:</td><td>'.$data['datawal_ttl'].'</td></tr>
  <tr><td>D.</td><td>Jenis Kelamin</td><td>:</td><td>'.$data['datawal_jenis_kelamin'].'</td></tr>
  <tr><td>E.</td><td>Sekolah</td><td>:</td><td>'.$namaSekolah.'</td></tr>
  <tr><td>F.</td><td>Kelas</td><td>:</td><td>'.$data['datawal_kelas'].'</td></tr>
  <tr><td>G.</td><td>Program Studi Keahlian</td><td>:</td><td>'.$data['jur_nama'].'</td></tr>
  <tr><td>H.</td><td>Alamat</td><td>:</td><td>'.$data['datawal_alamat'].'</td></tr>
  <tr><td>I.</td><td>No Telp / HP</td><td>:</td><td>'.$data['datawal_no_telp'].'</td></tr>
  <tr><td>J.</td><td>Agama</td><td>:</td><td>'.$data['datawal_agama'].'</td></tr>
  <tr><td>K.</td><td>Catatan Kesehatan</td><td>:</td><td>'.$data['datawal_catatan_kesehatan'].'</td></tr>
</table>

<br><br>
<table width="100%">
  <tr>
    <td width="50%" align="center">
      '.($foto ? '<img src="'.$foto.'" width="120">' : '').'
    </td>
    <td width="50%" align="center">
      '.$tanggal.'<br>
      Siswa,<br><br>
      '.($ttd ? '<img src="'.$ttd.'" width="200">' : '').'<br>
      <b>'.$data['datawal_nama'].'</b>
    </td>
  </tr>
</table>
';

// Tangani error dengan try-catch
try {
    // Cek apakah folder tmp bisa ditulis
    $tmpDir = __DIR__ . '/tmp';
    if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
        throw new \Mpdf\MpdfException("Folder 'tmp/' tidak ditemukan atau tidak dapat ditulis. Harap pastikan folder tersebut ada dan memiliki izin 0777.");
    }

    // Inisialisasi mPDF
    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_top' => 5,
        'margin_bottom' => 15,
        'margin_left' => 15,
        'margin_right' => 15,
        'tempDir' => __DIR__ . '/tmp'
    ]);

    // (Opsional) Tampilkan error gambar jika ada
    $mpdf->showImageErrors = true;

    $mpdf->WriteHTML($html);
    $mpdf->Output("Identitas_Siswa_".$data['datawal_nis_nip'].".pdf", "I"); // langsung download

} catch (\Mpdf\MpdfException $e) {
    // Tampilkan pesan error sebagai alert
    echo "<script>alert('Terjadi kesalahan saat membuat PDF: " . addslashes($e->getMessage()) . "');</script>";
}
