<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include "config.php";

function tanggalIndonesia($tanggal = null) {
    // Jika tidak ada tanggal dikirim, gunakan tanggal hari ini
    if ($tanggal === null) {
        $tanggal = date('Y-m-d');
    }

    // Daftar nama bulan dalam bahasa Indonesia
    $bulan = array(
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    );

    // Ubah format tanggal ke komponen
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));

    // Gabungkan hasilnya
    return $tgl . ' ' . $bln . ' ' . $thn;
}


$nis_nip = $_GET['nis_nip'] ?? '';
if (!$nis_nip) die("NIS/NIP tidak ditemukan.");

// Ambil data siswa & dunia usaha
$sql = "SELECT * 
        FROM data_du 
        LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
        LEFT JOIN tempat_pkl ON data_du.pkl_id=tempat_pkl.pkl_id 
        LEFT JOIN users ON data_du.du_siswa_id=users.id 
        LEFT JOIN jurusan ON data_awal.datawal_jurusan=jurusan.jur_id 
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
$nis_nip=$_SESSION['nis_nip'];
$nama=$_SESSION['nama'];
// Template HTML
$html = '
<center>'.($logoSurat ? '<img src="'.$logoSurat.'" style="width:100%;max-height:150px;">' : '').'</center>
<h2 style="text-align:center;">PERNYATAAN SISWA</h2>
<br>
<table border="0" cellpadding="6" cellspacing="0" width="100%">
  <tr>
    <td>1.</td>
    <td colspan="4" style="width:20px;">Identitas Siswa</td>
  </tr>
  <tr>
    <td></td>
    <td>a.</td>
    <td>NIS</td>
    <td style="width:5px;">:</td>
   <td style="text-align: left;">'.$nis_nip.'</td>
  </tr>
  <tr>
    <td></td>
    <td>b.</td>
    <td>Nama Lengkap</td>
    <td>:</td>
    <td>'.$nama.'</td>
  </tr>
  <tr>
    <td></td>
    <td>c.</td>
    <td>Kelas</td>
    <td>:</td>
    <td>'.$data['datawal_kelas'].'</td>
  </tr>
  <tr>
    <td></td>
    <td>d.</td>
    <td>Kons. Keahlian</td>
    <td>:</td>
    <td>'.$data['jur_nama'].'</td>
  </tr>
  <tr>
    <td></td>
    <td>e.</td>
    <td>Alamat Lengkap</td>
    <td>:</td>
    <td>'.$data['datawal_alamat'].'</td>
  </tr>
  <tr>
    <td></td>
    <td>f.</td>
    <td>No. Tlp</td>
    <td>:</td>
    <td>'.$data['datawal_no_telp'].'</td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>2.</td>
    <td colspan="4">Menyatakan dengan sebenarnya:</td>
  </tr>
  <tr>
    <td></td>
    <td>a.</td>
    <td colspan="3">Akan melaksanakan Praktik Kerja Lapangan (PKL) dengan sungguh-sungguh dengan penuh tanggung jawab.</td>
  </tr>
  <tr>
    <td></td>
    <td>b.</td>
    <td colspan="3">Bersedia menaati segala peraturan yang berlaku di tempat Praktik Kerja Lapangan (PKL) dan peraturan di Sekolah.</td>
  </tr>
  <tr>
    <td></td>
    <td>c.</td>
    <td colspan="3">Bersedia menyelesaikan tugas-tugas mata pelajaran yang diberikan oleh semua guru di sekolah sebelum batas waktu yang di tentukan oleh guru yang bersangkutan.</td>
  </tr>
  <tr>
    <td></td>
    <td>d.</td>
    <td colspan="3">Bersedia menerima sanksi yang sesuai jika melanggar Tata Tertib.</td>
  </tr>
</table>
<br><br><br>
<table border="0" width="100%">
  <tr>
    <td width="50%" align="center">
      <b></b><br><br><br>
    </td>
    <td width="50%" align="center">
      Banjarmasin, </b>'.tanggalIndonesia().'<br>Siswa,<br><br>
      '.(!empty($data['datawal_ttd']) ? '<img src="'.$data['datawal_ttd'].'" width="150"><br>' : '<br><br><br>').'
      '.$nama.'
    </td>
  </tr>
</table>
';

// Cetak PDF dengan margin rapi
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_top' => 5,
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_bottom' => 20
]);
$mpdf->WriteHTML($html);
$mpdf->Output("Identitas_DU_".$data['datawal_nis_nip'].".pdf", "I");