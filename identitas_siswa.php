<?php
// koneksi database
$nis_nip = $_SESSION['nis_nip'] ?? '';
$data_siswa = null;

// --- Ambil data siswa jika ada ---
if ($nis_nip) {
    $result = $koneksi->prepare("SELECT * FROM data_awal WHERE datawal_nis_nip=? LIMIT 1");
    $result->bind_param("s", $nis_nip);
    $result->execute();
    $data_siswa = $result->get_result()->fetch_assoc();
}

// --- Proses simpan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datawal_ttl = $_POST['datawal_ttl'];
    $datawal_jenis_kelamin = $_POST['datawal_jenis_kelamin'];
    $datawal_kelas = $_POST['datawal_kelas'];
    $datawal_jurusan = $_POST['datawal_jurusan'];
    $datawal_alamat = $_POST['datawal_alamat'];
    $datawal_no_telp = $_POST['datawal_no_telp'];
    $datawal_agama = $_POST['datawal_agama'];
    $datawal_catatan_kesehatan = $_POST['datawal_catatan_kesehatan'];
    $datawal_signature = $_POST['datawal_signature'] ?? '';
    $datawal_id_siswa = $_SESSION['id'];
    $datawal_nama = $_SESSION['nama'] ?? '';

    // Proses upload foto
    $datawal_photo = $data_siswa['datawal_photo'] ?? '';
    if (isset($_FILES['datawal_photo_file']) && $_FILES['datawal_photo_file']['error'] === 0) {
        $ext = pathinfo($_FILES['datawal_photo_file']['name'], PATHINFO_EXTENSION);
        $datawal_photo = 'photo_' . $nis_nip . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['datawal_photo_file']['tmp_name'], 'upload/' . $datawal_photo);
    }

    // Jika sudah ada data → update, jika belum → insert
    if ($data_siswa) {
        $stmt = $koneksi->prepare("UPDATE data_awal SET 
            datawal_id_siswa=?, datawal_ttl=?, datawal_jenis_kelamin=?, datawal_kelas=?, 
            datawal_jurusan=?, datawal_alamat=?, datawal_no_telp=?, datawal_agama=?, 
            datawal_catatan_kesehatan=?, datawal_photo=?, datawal_ttd=? 
            WHERE datawal_nis_nip=?");

        $stmt->bind_param(
            "isssssssssss",
            $datawal_id_siswa,
            $datawal_ttl,
            $datawal_jenis_kelamin,
            $datawal_kelas,
            $datawal_jurusan,
            $datawal_alamat,
            $datawal_no_telp,
            $datawal_agama,
            $datawal_catatan_kesehatan,
            $datawal_photo,
            $datawal_signature,
            $nis_nip
        );
        $stmt->execute();
    } else {
        $stmt = $koneksi->prepare("INSERT INTO data_awal 
            (datawal_id_siswa, datawal_nis_nip, datawal_nama, datawal_ttl, datawal_jenis_kelamin, 
            datawal_kelas, datawal_jurusan, datawal_alamat, datawal_no_telp, 
            datawal_agama, datawal_catatan_kesehatan, datawal_photo, datawal_ttd) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param(
            "issssssssssss",
            $datawal_id_siswa,
            $nis_nip,
            $datawal_nama,
            $datawal_ttl,
            $datawal_jenis_kelamin,
            $datawal_kelas,
            $datawal_jurusan,
            $datawal_alamat,
            $datawal_no_telp,
            $datawal_agama,
            $datawal_catatan_kesehatan,
            $datawal_photo,
            $datawal_signature
        );
        $stmt->execute();
    }

    // ✅ Kirim status sukses ke JavaScript
    echo "<script>window.saveSuccess = true;</script>";
}

// --- Data jurusan (ambil dari database jurusan) ---
$jurusan_list = [];
$qjur = $koneksi->query("SELECT * FROM jurusan ORDER BY jur_nama");
while ($rjur = $qjur->fetch_assoc()) {
    $jurusan_list[] = $rjur;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Identitas Siswa</title>

<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; color: #333; }
.form-title { font-weight: bold; margin-bottom: 20px; font-size: 20px; text-align: center; text-transform: uppercase; }
label { display: block; margin-top: 10px; font-size: 14px; font-weight: bold; }
input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
textarea { resize: vertical; }
.submit-btn { margin-top: 20px; padding: 10px 16px; background: #007bff; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; transition: background 0.3s; }
.submit-btn:hover { background: #0056b3; }
.bottom-sides { display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
.photo-container, .signature-container { flex: 1; min-width: 250px; }
.photo-preview { width: 100%; max-width: 180px; height: auto; border: 1px solid #ccc; border-radius: 6px; display: block; margin-bottom: 10px; }
.print-buttons { margin-top: 20px; text-align: center; }
.print-buttons button { padding: 8px 14px; margin: 5px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
.print-buttons button:hover { background: #218838; }
#signature-pad { border: 1px solid #ccc; border-radius: 6px; background: #fff; width: 100%; height: 150px; touch-action: none; }

/* ✅ Font normal untuk SweetAlert */
.swal-normal-font {
    font-family: Arial, sans-serif !important;
    font-weight: normal !important;
    text-transform: none !important;
}

@media (max-width: 768px) { .bottom-sides { flex-direction: column; } .photo-preview { max-width: 100%; } }
</style>
</head>
<body>

<div class="form-title">IDENTITAS SISWA</div>
<form action="" method="POST" enctype="multipart/form-data" onsubmit="prepareSignature()">

    <label>NIS</label>
    <input type="text" name="datawal_nis_nip" value="<?php echo $_SESSION['nis_nip'] ?? ''; ?>" readonly>

    <label>Nama Lengkap</label>
    <input type="text" name="datawal_nama" value="<?php echo $_SESSION['nama'] ?? ''; ?>" readonly>

    <label>Tempat / Tanggal Lahir</label>
    <input type="text" name="datawal_ttl" value="<?php echo $data_siswa['datawal_ttl'] ?? ''; ?>" required>

    <label>Jenis Kelamin</label>
    <select name="datawal_jenis_kelamin" required>
        <option value="">Pilih</option>
        <option value="L" <?php echo ($data_siswa['datawal_jenis_kelamin'] ?? '')=='L'?'selected':''; ?>>Laki-laki</option>
        <option value="P" <?php echo ($data_siswa['datawal_jenis_kelamin'] ?? '')=='P'?'selected':''; ?>>Perempuan</option>
    </select>

    <label>Sekolah</label>
    <input type="text" value="SMKN 4 Banjarmasin" readonly>

    <label>Kelas</label>
    <select name="datawal_kelas" required>
        <option value="">Pilih</option>
        <option value="11" <?php echo ($data_siswa['datawal_kelas'] ?? '')=='11'?'selected':''; ?>>Kelas XI</option>
        <option value="12" <?php echo ($data_siswa['datawal_kelas'] ?? '')=='12'?'selected':''; ?>>Kelas XII</option>
    </select>

    <label>Program Studi Keahlian</label>
    <select name="datawal_jurusan" required>
        <option value="">Pilih</option>
        <?php foreach($jurusan_list as $jur): ?>
            <option value="<?php echo $jur['jur_id']; ?>" <?php echo ($data_siswa['datawal_jurusan'] ?? '')==$jur['jur_id']?'selected':''; ?>>
                <?php echo $jur['jur_nama']; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Alamat</label>
    <textarea name="datawal_alamat" rows="3" required><?php echo $data_siswa['datawal_alamat'] ?? ''; ?></textarea>

    <label>No Telp / HP</label>
    <input type="text" name="datawal_no_telp" value="<?php echo $data_siswa['datawal_no_telp'] ?? ''; ?>" required>

    <label>Agama</label>
    <select name="datawal_agama" required>
        <option value="">Pilih</option>
        <?php $agama_list = ['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'];
        foreach($agama_list as $ag): ?>
            <option <?php echo ($data_siswa['datawal_agama'] ?? '')==$ag?'selected':''; ?>><?php echo $ag; ?></option>
        <?php endforeach; ?>
    </select>

    <label>Catatan Kesehatan</label>
    <textarea name="datawal_catatan_kesehatan" rows="3"><?php echo $data_siswa['datawal_catatan_kesehatan'] ?? ''; ?></textarea>

    <div class="bottom-sides">
        <div class="photo-container">
            <img id="photo-preview" class="photo-preview" src="<?php echo !empty($data_siswa['datawal_photo']) ? 'upload/'.$data_siswa['datawal_photo'] : ''; ?>" alt="Preview Foto">
            <input type="file" id="photo-input" name="datawal_photo_file" accept="image/*">
        </div>

        <div class="signature-container">
            <canvas id="signature-pad"></canvas>
            <div class="signature-name" id="student-name"><?php echo !empty($data_siswa['datawal_ttd']) ? $_SESSION['nama'] : ''; ?></div>
            <div class="signature-buttons" style="justify-content: flex-end;">
                <button type="button" onclick="finishSignature()">Selesai</button>
                <button type="button" onclick="clearCanvas()">Hapus</button>
            </div>
            <input type="hidden" name="datawal_signature" id="signature-data" value="<?php echo $data_siswa['datawal_ttd'] ?? ''; ?>">
        </div>
    </div>

    <button type="submit" class="submit-btn">Simpan</button>
</form>

<div class="print-buttons">
    <a href="cetak_identitas_siswa.php?nis_nip=<?php echo urlencode($_SESSION['nis_nip']); ?>" target="_blank">
        <button type="button">🖨️ Print Identitas Siswa</button>
    </a>
    <a href="cetak_identitas_du.php?nis_nip=<?php echo urlencode($_SESSION['nis_nip']); ?>" target="_blank">
        <button type="button">🖨️ Print Identitas Dunia Usaha</button>
    </a>
    <a href="cetak_cover_depan.php?nis_nip=<?php echo urlencode($_SESSION['nis_nip']); ?>" target="_blank">
        <button type="button">🖨️ Print Cover Depan</button>
    </a>
    <a href="cetak_pernyataan_siswa.php?nis_nip=<?php echo urlencode($_SESSION['nis_nip']); ?>" target="_blank">
        <button type="button">🖨️ Print Pernyataan Siswa</button>
    </a>
</div>

<script>
// === Signature Pad ===
let canvas = document.getElementById("signature-pad");
let ctx = canvas.getContext("2d");
let drawing = false;
canvas.width = canvas.offsetWidth;
canvas.height = 150;

function getPos(e) {
    let rect = canvas.getBoundingClientRect();
    if (e.touches) return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
    else return { x: e.offsetX, y: e.offsetY };
}

canvas.addEventListener("mousedown", e => { drawing = true; ctx.beginPath(); let pos = getPos(e); ctx.moveTo(pos.x, pos.y); });
canvas.addEventListener("mousemove", e => { if (drawing) { let pos = getPos(e); ctx.lineTo(pos.x, pos.y); ctx.stroke(); } });
canvas.addEventListener("mouseup", () => drawing = false);
canvas.addEventListener("mouseleave", () => drawing = false);
canvas.addEventListener("touchstart", e => { e.preventDefault(); drawing = true; ctx.beginPath(); let pos = getPos(e); ctx.moveTo(pos.x, pos.y); }, {passive:false});
canvas.addEventListener("touchmove", e => { e.preventDefault(); if (drawing) { let pos = getPos(e); ctx.lineTo(pos.x, pos.y); ctx.stroke(); } }, {passive:false});
canvas.addEventListener("touchend", e => { e.preventDefault(); drawing = false; });

function finishSignature() {
    document.getElementById("signature-data").value = canvas.toDataURL();
    document.getElementById("student-name").textContent = "<?php echo $_SESSION['nama'] ?? ''; ?>";
}
function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById("signature-data").value = "";
    document.getElementById("student-name").textContent = "";
}
function prepareSignature() { finishSignature(); }

window.addEventListener("load", function() {
    let oldData = document.getElementById("signature-data").value;
    if (oldData) {
        let img = new Image();
        img.onload = function() { ctx.drawImage(img, 0, 0, canvas.width, canvas.height); };
        img.src = oldData;
    }

    // ✅ SweetAlert tanpa animasi dan font normal
    if (window.saveSuccess) {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Data identitas siswa berhasil disimpan.',
            icon: 'success',
            confirmButtonColor: '#007bff',
            confirmButtonText: 'OK',
            showClass: { popup: '' },
            hideClass: { popup: '' },
            customClass: {
                popup: 'swal-normal-font',
                title: 'swal-normal-font',
                htmlContainer: 'swal-normal-font',
                confirmButton: 'swal-normal-font'
            }
        }).then(() => {
            window.location = 'dashboard_siswa.php';
        });
    }
});
</script>

</body>
</html>