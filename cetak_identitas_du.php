<?php
require_once __DIR__ . '/vendor/autoload.php';
include "config.php";

$nis_nip = $_GET['nis_nip'] ?? '';
if (!$nis_nip) die("NIS/NIP tidak ditemukan.");

// Ambil data siswa & dunia usaha
$sql = "SELECT * 
        FROM data_du 
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
        LEFT JOIN tempat_pkl ON data_du.pkl_id=tempat_pkl.pkl_id 
        LEFT JOIN users ON data_du.du_pembimbing=users.id 
        WHERE data_awal.datawal_nis_nip=? LIMIT 1";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $nis_nip);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data dunia usaha tidak ditemukan.");
$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();

$tanggal = date("d-m-Y");
$namaSekolah = $config['config_namasekolah'] ?? 'Nama Sekolah';
$logoSurat   = !empty($config['config_logosurat']) ? __DIR__."/upload/".$config['config_logosurat'] : "";

// Template HTML
$html = '
<center>'.($logoSurat ? '<img src="'.$logoSurat.'" style="width:100%;max-height:150px;">' : '').'</center>
<h2 style="text-align:center;">IDENTITAS DUNIA USAHA</h2>
<br>
<h3 style="text-align:center;">Dunia Usaha / Dunia Industri / Instansi / Lembaga</h3>
<table border="0" cellpadding="6" cellspacing="0" width="100%">
  <tr><td>1.</td><td>Nama Perusahaan</td><td>:</td><td>'.$data['pkl_nama'].'</td></tr>
  <tr><td>2.</td><td>Alamat</td><td>:</td><td>'.$data['du_alamat'].'</td></tr>
  <tr><td>3.</td><td>No Telp./Fax</td><td>:</td><td>'.$data['du_telp'].'</td></tr>
  <tr><td>4.</td><td>Nama Pimpinan</td><td>:</td><td>'.$data['du_pimpinan'].'</td></tr>
  <tr><td>5.</td><td>Nama Pembimbing</td><td>:</td><td>'.$data['nama'].'</td></tr>
  <tr><td>6.</td><td>Tgl. Mulai Praktik</td><td>:</td><td>'.$data['du_mulai'].'</td></tr>
  <tr><td>7.</td><td>Tgl. Selesai Praktik</td><td>:</td><td>'.$data['du_selesai'].'</td></tr>
</table>

<br><br><br>
<table border="0" width="100%">
  <tr>
    <td width="50%" align="center">
      <b>Pembimbing</b><br><br><br>
      '.(!empty($data['ttd_pembimbing']) ? '<img src="'.$data['ttd_pembimbing'].'" width="150"><br>' : '<br><br><br>').'
      '.$data['nama'].'
    </td>
    <td width="50%" align="center">
      <b>Pimpinan DU/DI/Instansi/Lembaga</b><br><br><br>
      '.(!empty($data['ttd_pimpinan']) ? '<img src="'.$data['ttd_pimpinan'].'" width="150"><br>' : '<br><br><br>').'
      '.$data['du_pimpinan'].'
    </td>
  </tr>
</table>
';

// Cetak PDF dengan margin rapi
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_top' => 15,
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_bottom' => 20
]);
$mpdf->WriteHTML($html);
$mpdf->Output("Identitas_DU_".$data['datawal_nis_nip'].".pdf", "I");
