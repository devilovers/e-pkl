<?php
session_start();

// Validasi login & role
if (empty($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "Pembimbing") {
    header("Location: login.php");
    exit;
}

include "config.php"; // koneksi database

$page    = $_GET['page'] ?? 'home';
$nis_nip = $_SESSION['nis_nip'] ?? '';

// Ambil data siswa jika sudah ada
$data_siswa = null;
if ($nis_nip) {
    $stmt = $koneksi->prepare("SELECT * FROM data_awal WHERE datawal_nis_nip=?");
    $stmt->bind_param("s", $nis_nip);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $data_siswa = $res->fetch_assoc();
    }
    $stmt->close();
}

// Ambil daftar jurusan
$jurusan_list = [];
$jur_query = $koneksi->query("SELECT jur_id, jur_nama FROM jurusan");
while ($row = $jur_query->fetch_assoc()) {
    $jurusan_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Pembimbing - Jurnal PKL</title>
<style>
/* --- Dashboard Pembimbing Scope --- */
.dashboard-pembimbing {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f9f6f2;
    color: #4a3c31;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* NAVBAR */
.dashboard-pembimbing .navbar {
    background: #d2b48c;
    padding: 15px 20px;
    color: white;
    font-size: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}
.dashboard-pembimbing .hamburger {
    display: flex;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
}
.dashboard-pembimbing .hamburger span {
    width: 25px;
    height: 3px;
    background: white;
    border-radius: 2px;
}
.dashboard-pembimbing .navbar .menu {
    display: none;
    flex-direction: column;
    width: 100%;
    margin-top: 10px;
    background: #c19a6b;
    border-radius: 6px;
    overflow: hidden;
}
.dashboard-pembimbing .navbar .menu a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.3);
    font-size: 15px;
}
.dashboard-pembimbing .navbar .menu a:hover {
    background: #b4936f;
}
.dashboard-pembimbing .navbar .menu.show { display: flex; }

/* USER INFO */
.dashboard-pembimbing .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.dashboard-pembimbing .user-info .nama {
    font-weight: bold;
    font-size: 14px;
}
.dashboard-pembimbing .logout-btn {
    background: #b4936f;
    color: white;
    padding: 7px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}
.dashboard-pembimbing .logout-btn:hover {
    background: #9c7b58;
}

/* HEADER SEKOLAH */
.dashboard-pembimbing .header-sekolah {
    background: #fff;
    border-bottom: 2px solid #ccc;
    padding: 15px 20px;
    text-align: center;
}
.dashboard-pembimbing .header-sekolah img {
    max-height: 80px;
    display: block;
    margin: 0 auto 15px;
}
.dashboard-pembimbing .header-sekolah h2, 
.dashboard-pembimbing .header-sekolah h3, 
.dashboard-pembimbing .header-sekolah p {
    margin: 2px 0;
    font-size: 14px;
}

/* CONTENT */
.dashboard-pembimbing .content {
    flex: 1;
    width: 100%;
    max-width: 900px;
    margin: 20px auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    gap: 15px;
    box-sizing: border-box;
}
.dashboard-pembimbing .form-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    text-transform: uppercase;
}

/* FOOTER */
.dashboard-pembimbing .footer {
    background: #d2b48c;
    color: white;
    display: flex;
    justify-content: space-between;
    padding: 20px;
    gap: 15px;
    font-size: 14px;
}
.dashboard-pembimbing .footer .left { flex: 1; text-align: left; }
.dashboard-pembimbing .footer .right { flex: 1; text-align: right; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .dashboard-pembimbing .navbar {
        flex-direction: column;
        align-items: flex-start;
    }
    .dashboard-pembimbing .user-info {
        align-self: flex-end;
        margin-top: 10px;
    }
    .dashboard-pembimbing .content { padding: 15px; margin: 15px; }
    .dashboard-pembimbing .footer { flex-direction: column; text-align: center; }
    .dashboard-pembimbing .footer .left, 
    .dashboard-pembimbing .footer .right { text-align: center; }
}
</style>
</head>
<body>

<div class="dashboard-pembimbing">

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="hamburger" onclick="toggleMenu()">
            <span></span><span></span><span></span>
        </div>
        <div class="user-info">
            <span class="nama"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <div class="menu" id="menu">
            <a href="dashboard_pembimbing.php?page=home">Home</a>
            <a href="dashboard_pembimbing.php?page=identitas_siswa_pembimbing">Identitas Siswa</a>
            <a href="dashboard_pembimbing.php?page=identitas_perusahaan_pembimbing">Identitas Dunia Usaha / Dunia Industri</a>
            <a href="dashboard_pembimbing.php?page=catatan_kegiatan">Catatan Kegiatan</a>
            <a href="dashboard_pembimbing.php?page=lembar_observasi">Lembar Observasi</a>
            <a href="dashboard_pembimbing.php?page=daftar_hadir">Daftar Hadir</a>
            <a href="dashboard_pembimbing.php?page=ganti_pass">Ganti Password</a>
        </div>
    </div>

    <!-- HEADER SEKOLAH -->
    <div class="header-sekolah">
        <img src="logosmkn4bjm.png" alt="Logo Sekolah">
    </div>

    <!-- CONTENT -->
    <div class="content">
    <?php
    switch ($page) {
        case 'home': include 'home.php'; break;
        case 'identitas_siswa_pembimbing': include 'identitas_siswa_pembimbing.php'; break;
        case 'identitas_perusahaan_pembimbing': include 'identitas_perusahaan_pembimbing.php'; break;
        case 'catatan_kegiatan': include 'catatan_kegiatan_pembimbing.php'; break;
        case 'lembar_observasi': include 'lembar_observasi_pembimbing.php'; break;
        case 'daftar_hadir': include 'daftar_hadir_pembimbing.php'; break;
        case 'ganti_pass': include 'ganpas.php'; break;
        default: include 'home.php'; break;
    }
    ?>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="left">
            <strong>SMK NEGERI 4 BANJARMASIN</strong><br>
            Jl. Brigjend H. Hasan Basri No.07 Banjarmasin, Kalimantan Selatan, Indonesia<br>
            Telp. 0511-5209999 | Email: info@smkn4bjm.sch.id | Website: smkn4bjm.sch.id
        </div>
        <div class="right">
            e-PKL versi 1.0<br>
            Development by <br>
            Nayla Putri (14230) - Nur Islami Sabila (14232)
        </div>
    </div>

</div>

<script>
function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}
</script>
</body>
</html>