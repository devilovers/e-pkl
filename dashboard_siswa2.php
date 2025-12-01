<?php
session_start();
include "config.php"; // koneksi database
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$nis_nip = $_SESSION['nis_nip'] ?? '';

// Ambil data siswa jika sudah ada
$data_siswa = null;
if($nis_nip){
    $stmt = $koneksi->prepare("SELECT * FROM data_awal WHERE datawal_nis_nip=?");
    $stmt->bind_param("s", $nis_nip);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $data_siswa = $res->fetch_assoc();
    }
}

// Ambil daftar jurusan
$jurusan_list = [];
$jur_query = $koneksi->query("SELECT jur_id, jur_nama FROM jurusan");
while($row = $jur_query->fetch_assoc()){
    $jurusan_list[] = $row;
}

// Proses simpan form
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $datawal_ttl = $_POST['datawal_ttl'];
    $datawal_jenis_kelamin = $_POST['datawal_jenis_kelamin'];
    $datawal_kelas = $_POST['datawal_kelas'];
    $datawal_jurusan = $_POST['datawal_jurusan'];
    $datawal_alamat = $_POST['datawal_alamat'];
    $datawal_no_telp = $_POST['datawal_no_telp'];
    $datawal_agama = $_POST['datawal_agama'];
    $datawal_catatan_kesehatan = $_POST['datawal_catatan_kesehatan'];
    $datawal_signature = $_POST['datawal_signature'];

    // Proses upload foto
    $datawal_photo = $data_siswa['datawal_photo'] ?? '';
    if(isset($_FILES['datawal_photo_file']) && $_FILES['datawal_photo_file']['error'] === 0){
        $ext = pathinfo($_FILES['datawal_photo_file']['name'], PATHINFO_EXTENSION);
        $datawal_photo = 'photo_'.$nis_nip.'_'.time().'.'.$ext;
        move_uploaded_file($_FILES['datawal_photo_file']['tmp_name'], 'upload/'.$datawal_photo);
    }

    if($data_siswa){ // update
        $stmt = $koneksi->prepare("UPDATE data_awal SET datawal_ttl=?, datawal_jenis_kelamin=?, datawal_kelas=?, datawal_jurusan=?, datawal_alamat=?, datawal_no_telp=?, datawal_agama=?, datawal_catatan_kesehatan=?, datawal_photo=?, datawal_ttd=? WHERE datawal_nis_nip=?");
        $stmt->bind_param("sssssssssss", $datawal_ttl, $datawal_jenis_kelamin, $datawal_kelas, $datawal_jurusan, $datawal_alamat, $datawal_no_telp, $datawal_agama, $datawal_catatan_kesehatan, $datawal_photo, $datawal_signature, $nis_nip);
        $stmt->execute();
    } else { // insert
        $stmt = $koneksi->prepare("INSERT INTO data_awal (datawal_nis_nip, datawal_nama, datawal_ttl, datawal_jenis_kelamin, datawal_kelas, datawal_jurusan, datawal_alamat, datawal_no_telp, datawal_agama, datawal_catatan_kesehatan, datawal_photo, datawal_ttd) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $datawal_nama = $_SESSION['nama'] ?? '';
        $stmt->bind_param("ssssssssssss", $nis_nip, $datawal_nama, $datawal_ttl, $datawal_jenis_kelamin, $datawal_kelas, $datawal_jurusan, $datawal_alamat, $datawal_no_telp, $datawal_agama, $datawal_catatan_kesehatan, $datawal_photo, $datawal_signature);
        $stmt->execute();
    }

    header("Location: dashboard_siswa.php?page=data_awal");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Siswa - Jurnal PKL</title>
<style>
body { margin:0; font-family: Arial,sans-serif; background:#f9f6f2; color:#4a3c31; }
.navbar { background:#d2b48c; padding:15px 40px; color:white; font-size:18px; display:flex; flex-direction:column; align-items:center; text-align:center; position:relative; }
.navbar .menu { display:flex; flex-wrap:wrap; justify-content:center; gap:15px; margin-bottom:10px; }
.navbar .menu a { color:white; text-decoration:none; padding:8px 12px; border-radius:6px; transition:background 0.3s; }
.navbar .menu a:hover { background:#b4936f; }
.logout-btn { position:absolute; top:15px; right:40px; background:#d2b48c; color:white; padding:8px 14px; border-radius:6px; text-decoration:none; font-weight:bold; }
.logout-btn:hover { background:#b4936f; }
.header-sekolah { background:#fff; border-bottom:2px solid #ccc; padding:15px 20px; text-align:center; }
.header-sekolah img { max-height:80px; display:block; margin:0 auto 10px; }
.header-sekolah h2,h3,p { margin:2px 0; }
.content { max-width:800px; margin:20px auto; background:white; padding:30px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.1); display:flex; flex-direction:column; gap:15px; }
.form-title { text-align:center; font-size:22px; font-weight:bold; margin-bottom:20px; text-transform:uppercase; }
label { display:block; margin:8px 0 4px; font-weight:bold; text-align:left; }
input,select,textarea { width:100%; box-sizing:border-box; padding:8px; border:1px solid #ccc; border-radius:6px; margin-bottom:8px; }
button.submit-btn { margin-top:15px; padding:10px 15px; background:#d2b48c; border:none; border-radius:6px; color:white; cursor:pointer; align-self:flex-start; }
button.submit-btn:hover { background:#b4936f; }
.bottom-sides { display:flex; justify-content:space-between; margin-top:20px; }
.photo-container, .signature-container { display:flex; flex-direction:column; }
.photo-preview { width:150px; height:150px; border:1px solid #ccc; border-radius:6px; margin-bottom:5px; object-fit:cover; background:#f0f0f0; }
.photo-buttons, .signature-buttons { display:flex; gap:5px; margin-bottom:5px; }
.photo-buttons button, .signature-buttons button { padding:5px 10px; font-size:12px; background:#d2b48c; border:none; border-radius:6px; color:white; cursor:pointer; }
.photo-buttons button:hover, .signature-buttons button:hover { background:#b4936f; }
.signature-container canvas { border:1px solid #ccc; border-radius:6px; width:250px; height:150px; cursor:crosshair; background:#fff; margin-bottom:5px; }
.signature-name { font-weight:bold; font-size:14px; text-align:center; width:250px; margin-bottom:5px; }
.home-text { text-align:center; }
</style>
</head>
<body>

<div class="navbar">
    <div class="menu">
        <a href="dashboard_siswa.php?page=home">Home</a>
        <a href="dashboard_siswa.php?page=data_awal">Identitas Siswa</a>
        <a href="dashboard_siswa.php?page=data_perusahaan">Identitas Perusahaan</a>
        <a href="dashboard_siswa.php?page=catatan_kegiatan">Catatan Kegiatan</a>
        <a href="dashboard_siswa.php?page=lembar_observasi">Lembar Observasi</a>
        <a href="dashboard_siswa.php?page=catatan_penting">Catatan Penting</a>
        <a href="dashboard_siswa.php?page=daftar_hadir">Daftar Hadir</a>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="header-sekolah">
    <img src="logosmkn4bjm.png" alt="Logo Sekolah">
    <h2>PEMERINTAH PROVINSI KALIMANTAN SELATAN</h2>
    <h3>DINAS PENDIDIKAN DAN KEBUDAYAAN</h3>
    <h3>SMK NEGERI 4 BANJARMASIN</h3>
    <p>Jl. Brigjend H. Hasan Basri No.07 Banjarmasin, Kalimantan Selatan, Indonesia<br>
    Telp. 0511-5209999 | Email: info@smkn4bjm.sch.id | Website: smkn4bjm.sch.id</p>
</div>

<div class="content">
   <?php if($page==='home'): ?>
        <h1>Ini adalah halaman menu Home</h1>
        <p>Selamat datang, <?php echo $_SESSION['nama'] ?? 'Siswa'; ?>!</p>

    <?php elseif($page==='data_awal'): ?>
        <?php include('identitas_siswa.php'); ?>

    <?php elseif($page==='data_perusahaan'): ?>
        <h1>Ini adalah halaman menu Identitas Perusahaan</h1>

    <?php elseif($page==='catatan_kegiatan'): ?>
        <h1>Ini adalah halaman menu Catatan Kegiatan</h1>

    <?php elseif($page==='lembar_observasi'): ?>
        <h1>Ini adalah halaman menu Lembar Observasi</h1>

    <?php elseif($page==='catatan_penting'): ?>
        <h1>Ini adalah halaman menu Catatan Penting</h1>

    <?php elseif($page==='daftar_hadir'): ?>
        <h1>Ini adalah halaman menu Daftar Hadir</h1>

    <?php else: ?>
        <h1>Halaman tidak ditemukan!</h1>
    <?php endif; ?>

</div>

<script>
// Signature pad
const canvas=document.getElementById('signature-pad');
const ctx=canvas.getContext('2d');
let isDrawing=false, finishedSignature=false;
canvas.addEventListener('mousedown', e=>{ if(finishedSignature) return; isDrawing=true; ctx.beginPath(); ctx.moveTo(e.offsetX,e.offsetY); });
canvas.addEventListener('mousemove', e=>{ if(finishedSignature) return; if(isDrawing){ ctx.lineTo(e.offsetX,e.offsetY); ctx.stroke(); }} );
canvas.addEventListener('mouseup', ()=>isDrawing=false);
canvas.addEventListener('mouseout', ()=>isDrawing=false);
function clearCanvas(){ ctx.clearRect(0,0,canvas.width,canvas.height); finishedSignature=false; document.getElementById('student-name').textContent=''; canvas.style.pointerEvents='auto'; }
function finishSignature(){ finishedSignature=true; const name="<?php echo $_SESSION['nama'] ?? ''; ?>"; document.getElementById('student-name').textContent=name; canvas.style.pointerEvents='none'; }
function prepareSignature(){ document.getElementById('signature-data').value=canvas.toDataURL(); }

// Photo preview
document.getElementById('photo-input').addEventListener('change', function(e){
    const reader = new FileReader();
    reader.onload = function(){ document.getElementById('photo-preview').src = reader.result; };
    reader.readAsDataURL(e.target.files[0]);
});
</script>

</body>
</html>
