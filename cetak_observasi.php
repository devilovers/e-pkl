<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php'; // mPDF
require_once "config.php";

use Mpdf\Mpdf;

$id_jur   = isset($_GET['id_jur']) ? (int)$_GET['id_jur'] : 0;
$id_siswa = isset($_GET['id_siswa']) ? (int)$_GET['id_siswa'] : 0;

if ($id_jur <= 0 || $id_siswa <= 0) {
    die("Parameter tidak valid");
}

$config = $koneksi->query("SELECT * FROM configurasi LIMIT 1")->fetch_assoc();
$logoSurat = !empty($config['config_logosurat']) ? __DIR__."/upload/".$config['config_logosurat'] : "";

// --- Ambil data tanda tangan---
$sql3 = "SELECT * FROM observasi_ttd WHERE id_siswa_ttd_oss= ? 
LIMIT 1;
";
$stmt = $koneksi->prepare($sql3);
$stmt->bind_param("s", $_SESSION['id']);
$stmt->execute();
$data3 = $stmt->get_result()->fetch_assoc();

// --- Ambil data siswa & PKL ---
$sql = "SELECT data_du.*, 
       data_awal.*, 
       tempat_pkl.*, 
       u_penilai.nama     AS nama_penilai,
       u_pembimbing.nama  AS nama_pembimbing
FROM data_du
LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa
LEFT JOIN tempat_pkl ON data_du.pkl_id = tempat_pkl.pkl_id
LEFT JOIN users u_penilai ON data_du.du_penilai = u_penilai.id
LEFT JOIN users u_pembimbing ON data_du.du_pembimbing = u_pembimbing.id
WHERE data_awal.datawal_nis_nip = ?
LIMIT 1;
";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $_SESSION['nis_nip']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$sqlSiswa = "SELECT du.*, 
        da.datawal_nama, da.datawal_nis_nip,
        du_pembimbing, du_penilai,
        du_mulai, du_selesai,
        du_alamat 
    FROM data_du du
    JOIN data_awal da ON du.du_siswa_id = da.datawal_id_siswa
    WHERE du.du_siswa_id=?";
$stmt = $koneksi->prepare($sqlSiswa);
$stmt->bind_param("i", $id_siswa,);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Ambil header grup ---
$sql = "SELECT DISTINCT hg.id_hg, hg.nama_hg
        FROM header_grup hg
        JOIN observasi_detail od ON od.grup_obs = hg.id_hg
        WHERE od.id_jur_obs = ?
        ORDER BY hg.id_hg";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $id_jur);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $id_hg = (int)$row['id_hg'];

    // Ambil indikator
    $sql2 = "SELECT od.id_obs, od.indikator_obs
             FROM observasi_detail od
             WHERE od.grup_obs = ? AND od.id_jur_obs = ? AND od.flag_obs='T' 
             ORDER BY od.id_obs";
    $stmt2 = $koneksi->prepare($sql2);
    $stmt2->bind_param("ii", $id_hg, $id_jur);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    $indikator = [];
    while ($r2 = $res2->fetch_assoc()) {
        $r2['capai']     = null;
        $r2['deskripsi'] = null;

        // isi nilai observasi_siswa jika ada
        $sql3 = "SELECT ketercapaian_oss AS capai, deskripsi_oss AS deskripsi
                 FROM observasi_siswa
                 WHERE id_siswa_oss = ? AND id_observasi_detail = ?
                 LIMIT 1";
        $stmt3 = $koneksi->prepare($sql3);
        $stmt3->bind_param("ii", $id_siswa, $r2['id_obs']);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        if ($old = $res3->fetch_assoc()) {
            $r2['capai']     = $old['capai'];
            $r2['deskripsi'] = $old['deskripsi'];
        }
        $stmt3->close();

        $indikator[] = $r2;
    }
    $stmt2->close();

    $row['indikator'] = $indikator;
    $rows[] = $row;
}
$stmt->close();

// --- Buat HTML ---
ob_start();
?>
<center><img src="<?php echo $logoSurat; ?>" style="width:100%;max-height:150px;"></center>
<h3 style="text-align:center;">Lembar Observasi PKL</h3>
<table width="100%" border="0" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
    <tr>
        <td width="30%" style="padding-top:2px; padding-bottom:2px;">Nama Peserta Didik</td>
        <td style="padding-top:2px; padding-bottom:2px;">: <?= htmlspecialchars($siswa['datawal_nama']) ?></td>
    </tr>
    <tr>
        <td style="padding-top:2px; padding-bottom:2px;">Dunia Kerja Tempat PKL</td>
        <td style="padding-top:2px; padding-bottom:2px;">: <?= htmlspecialchars($data['pkl_nama'] ?? '-') ?></td>
    </tr>
    <tr>
        <td style="padding-top:2px; padding-bottom:2px;">Nama Instruktur</td>
        <td style="padding-top:2px; padding-bottom:2px;">: <?= htmlspecialchars($data['nama_penilai'] ?? '-') ?></td>
    </tr>
    <tr>
        <td style="padding-top:2px; padding-bottom:2px;">Nama Guru Pembimbing</td>
        <td style="padding-top:2px; padding-bottom:2px;">: <?= htmlspecialchars($data['nama_pembimbing'] ?? '-') ?></td>
    </tr>
</table>
<br>

<table border="1" width="100%" cellspacing="0" cellpadding="4" style="font-size:9pt; border-collapse: collapse;">
    <thead>
        <tr style="background:#eee; text-align:center;">
            <th width="5%">No</th>
           <th width="7%" style="border-right:none;"></th>
           <th width="38%" style="border-left:none; text-align:left;">Tujuan Pembelajaran / Indikator</th>
            <th width="15%">Ketercapaian<br>(Ya/Tidak)</th>
            <th width="35%">Deskripsi</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no = 1;
    foreach($rows as $grup){
        // Header grup (baris pertama)
        echo "<tr>
                <td style='text-align:center; vertical-align:top;'>{$no}</td>
                <td colspan='2'><b>".htmlspecialchars($grup['nama_hg'])."</b></td>
                <td></td>
                <td></td>
              </tr>";

        // Indikator di bawahnya
        $sub = 1;
        foreach($grup['indikator'] as $ind){
            $subNomor = $no . "." . $sub;

            echo "<tr>
                    <td></td>
                    <td style='text-align:center; vertical-align:top;border-right:none;'>{$subNomor}</td>
                    <td style='text-align:justify;border-left:none;'>".htmlspecialchars($ind['indikator_obs'])."</td>
                    <td align='center'>".($ind['capai']=='Y'?'Ya':($ind['capai']=='N'?'Tidak':'-'))."</td>
                    <td>".nl2br(htmlspecialchars($ind['deskripsi'] ?? ''))."</td>
                  </tr>";
            $sub++;
        }
        $no++;
    }
    ?>
    </tbody>
</table>

<br><br>

<table width="100%">
    <tr>
        <td align="center" width="50%"><br><br>
            <b>Pembimbing</b><br><br>
            <?php if(!empty($data3['ttd_pembimbing_oss'])): ?>
                <img src="<?= $data3['ttd_pembimbing_oss'] ?>" width="150"><br>
            <?php else: ?><br><br><br><?php endif; ?>
            (<?= htmlspecialchars($data['nama_pembimbing'] ?? '-') ?>)
        </td>
        <td align="center" width="50%">
            <?= "" . date("d F Y", strtotime($data3['tanggal_ttd_oss'])) ?><br><br>
 <!-- ✅ Tambahan tanggal di atas -->
            <b>Penilai</b><br><br>
            <?php if(!empty($data3['ttd_penilai_oss'])): ?>
                <img src="<?= $data3['ttd_penilai_oss'] ?>" width="150"><br>
            <?php else: ?><br><br><br><?php endif; ?>
            (<?= htmlspecialchars($data['nama_penilai'] ?? '-') ?>)
        </td>
    </tr>
</table>
<?php
$html = ob_get_clean();

// --- Cetak pakai mPDF ---
$mpdf = new Mpdf([
    'format' => 'A4',
    'margin_top' => 5,    // jarak atas (mm)
    'margin_bottom' => 5, // jarak bawah (mm)
     'tempDir' => __DIR__ . '/tmp', // <- arahkan ke folder tmp buatanmu
]);
$mpdf->WriteHTML($html);
$mpdf->Output("observasi_pkl.pdf", "I"); // tampilkan di browser
