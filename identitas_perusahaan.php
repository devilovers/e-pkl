<?php
include "config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- ambil data lama jika ada ---
$data_du = null;
$res = $koneksi->query("SELECT * FROM data_du WHERE du_siswa_id=" . $_SESSION['id'] . " LIMIT 1");
if ($res && $res->num_rows > 0) {
    $data_du = $res->fetch_assoc();
}

// variabel untuk feedback SweetAlert
$alert = null;

// --- proses simpan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pkl_id        = isset($_POST['pkl_id']) ? (int)$_POST['pkl_id'] : 0;
    $du_alamat     = trim($_POST['du_alamat'] ?? '');
    $du_telp       = trim($_POST['du_telp'] ?? '');
    $du_pimpinan   = trim($_POST['du_pimpinan'] ?? '');
    $du_pembimbing = isset($_POST['du_pembimbing']) ? (int)$_POST['du_pembimbing'] : 0;
    $du_penilai    = isset($_POST['du_penilai']) ? (int)$_POST['du_penilai'] : 0;
    $du_mulai      = $_POST['du_mulai'] ?? null;
    $du_selesai    = $_POST['du_selesai'] ?? null;
    $tgl_pengisian = $_POST['tgl_pengisian'] ?? null;
    $du_siswa_id   = $_SESSION['id'];

    if ($data_du) { // UPDATE
        $stmt = $koneksi->prepare("UPDATE data_du 
            SET pkl_id=?, du_alamat=?, du_telp=?, du_pimpinan=?, du_pembimbing=?, du_penilai=?, 
                du_mulai=?, du_selesai=?, tgl_pengisian=?, du_siswa_id=?  
            WHERE du_id=?");
        if ($stmt) {
            $stmt->bind_param(
                "isssiisssii",
                $pkl_id,
                $du_alamat,
                $du_telp,
                $du_pimpinan,
                $du_pembimbing,
                $du_penilai,
                $du_mulai,
                $du_selesai,
                $tgl_pengisian,
                $du_siswa_id,
                $data_du['du_id']
            );
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'text' => 'Data berhasil diperbarui!'];
            } else {
                $alert = ['type' => 'error', 'text' => 'Gagal memperbarui data: ' . $stmt->error];
            }
            $stmt->close();
        }
    } else { // INSERT
        $stmt = $koneksi->prepare("INSERT INTO data_du 
            (pkl_id, du_alamat, du_telp, du_pimpinan, du_pembimbing, du_penilai, du_mulai, du_selesai, tgl_pengisian, du_siswa_id) 
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param(
                "isssiisssi",
                $pkl_id,
                $du_alamat,
                $du_telp,
                $du_pimpinan,
                $du_pembimbing,
                $du_penilai,
                $du_mulai,
                $du_selesai,
                $tgl_pengisian,
                $du_siswa_id
            );
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'text' => 'Data berhasil disimpan!'];
            } else {
                $alert = ['type' => 'error', 'text' => 'Gagal menyimpan data: ' . $stmt->error];
            }
            $stmt->close();
        }
    }

    // reload data terbaru
    $res = $koneksi->query("SELECT * FROM data_du WHERE du_siswa_id=" . $_SESSION['id']);
    $data_du = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
}

// --- ambil data perusahaan ---
$perusahaan_list = [];
$res = $koneksi->query("SELECT pkl_id, pkl_nama, pkl_alamat FROM tempat_pkl");
while ($row = $res->fetch_assoc()) $perusahaan_list[] = $row;

// --- ambil data guru pembimbing (sekolah) ---
$pembimbing_sekolah = [];
$res = $koneksi->query("SELECT id, nama, nis_nip FROM users WHERE role='pembimbing' AND flag_users='T'");
while ($row = $res->fetch_assoc()) $pembimbing_sekolah[] = $row;

// --- ambil data penilai perusahaan ---
$penilai_perusahaan = [];
$res = $koneksi->query("SELECT id, nama, nis_nip FROM users WHERE role='penilai'");
while ($row = $res->fetch_assoc()) $penilai_perusahaan[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Identitas Dunia Usaha</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; color: #333; }
.form-title { font-weight: bold; margin-bottom: 20px; font-size: 20px; text-align: center; text-transform: uppercase; }
label { display: block; margin-top: 10px; font-size: 14px; font-weight: bold; }
input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
textarea { resize: vertical; }
.submit-btn { margin-top: 20px; padding: 10px 16px; background: #007bff; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; transition: background 0.3s; }
.submit-btn:hover { background: #0056b3; }
@media (max-width: 768px) {
    input, select, textarea { font-size: 13px; }
}
</style>
</head>
<body>

<div class="form-title">IDENTITAS DUNIA USAHA / INDUSTRI</div>
<form action="" method="POST">

    <label>Nama Perusahaan</label>
    <select name="pkl_id" id="pkl-select" required onchange="fillAlamat()">
        <option value="">Pilih</option>
        <?php foreach ($perusahaan_list as $p): ?>
            <option value="<?= (int)$p['pkl_id']; ?>"
                data-alamat="<?= htmlspecialchars($p['pkl_alamat']); ?>"
                <?= isset($data_du['pkl_id']) && $data_du['pkl_id'] == $p['pkl_id'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($p['pkl_nama']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Alamat Perusahaan</label>
    <textarea id="alamat-perusahaan" name="du_alamat" rows="3" required><?= htmlspecialchars($data_du['du_alamat'] ?? ''); ?></textarea>

    <label>No. Telp / Fax</label>
    <input type="text" name="du_telp" value="<?= htmlspecialchars($data_du['du_telp'] ?? ''); ?>" required>

    <label>Nama Pimpinan</label>
    <input type="text" name="du_pimpinan" value="<?= htmlspecialchars($data_du['du_pimpinan'] ?? ''); ?>" required>

    <label>Nama Pembimbing dari Sekolah</label>
    <select name="du_pembimbing" required>
        <option value="">Pilih</option>
        <?php foreach ($pembimbing_sekolah as $g): ?>
            <option value="<?= (int)$g['id']; ?>" <?= isset($data_du['du_pembimbing']) && $data_du['du_pembimbing'] == $g['id'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($g['nama'] . " (" . $g['nis_nip'] . ")"); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Nama Penilai dari Perusahaan</label>
    <select name="du_penilai" required>
        <option value="">Pilih</option>
        <?php foreach ($penilai_perusahaan as $pmb): ?>
            <option value="<?= (int)$pmb['id']; ?>" <?= isset($data_du['du_penilai']) && $data_du['du_penilai'] == $pmb['id'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($pmb['nama'] . " (" . $pmb['nis_nip'] . ")"); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Tanggal Mulai Praktik</label>
    <input type="date" name="du_mulai" value="<?= htmlspecialchars($data_du['du_mulai'] ?? ''); ?>" required>

    <label>Tanggal Selesai Praktik</label>
    <input type="date" name="du_selesai" value="<?= htmlspecialchars($data_du['du_selesai'] ?? ''); ?>" required>

    <label>Tanggal Pengisian Identitas</label>
    <input type="date" name="tgl_pengisian" value="<?= htmlspecialchars($data_du['tgl_pengisian'] ?? date('Y-m-d')); ?>" required>

    <button type="submit" class="submit-btn">Simpan</button>
</form>

<script>
function fillAlamat() {
    var select = document.getElementById("pkl-select");
    var alamat = select.options[select.selectedIndex]?.getAttribute("data-alamat") || '';
    document.getElementById("alamat-perusahaan").value = alamat;
}

<?php if ($alert): ?>
Swal.fire({
    icon: '<?= $alert['type']; ?>',
    title: '<?= $alert['type'] === 'success' ? 'Berhasil!' : 'Oops...'; ?>',
    text: '<?= $alert['text']; ?>',
    confirmButtonColor: '#007BFF'
});
<?php endif; ?>
</script>

</body>
</html>
