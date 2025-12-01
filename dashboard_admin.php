<?php
session_start();

// Validasi login & role
if (empty($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit;
}

include "config.php"; // koneksi db

$page    = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin - Jurnal PKL</title>
<style>
/* --- Dashboard Admin Scope --- */
.dashboard-admin {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f9f6f2;
    color: #4a3c31;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* NAVBAR */
.dashboard-admin .navbar {
    background: #d2b48c;
    padding: 15px 20px;
    color: white;
    font-size: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}
.dashboard-admin .hamburger {
    display: flex;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
}
.dashboard-admin .hamburger span {
    width: 25px;
    height: 3px;
    background: white;
    border-radius: 2px;
}
.dashboard-admin .navbar .menu {
    display: none;
    flex-direction: column;
    width: 100%;
    margin-top: 10px;
    background: #c19a6b;
    border-radius: 6px;
    overflow: hidden;
}
.dashboard-admin .navbar .menu a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.3);
    font-size: 15px;
}
.dashboard-admin .navbar .menu a:hover {
    background: #b4936f;
}
.dashboard-admin .navbar .menu.show { display: flex; }

/* USER INFO */
.dashboard-admin .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.dashboard-admin .user-info .nama {
    font-weight: bold;
    font-size: 14px;
}
.dashboard-admin .logout-btn {
    background: #b4936f;
    color: white;
    padding: 7px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}
.dashboard-admin .logout-btn:hover {
    background: #9c7b58;
}

/* HEADER SEKOLAH */
.dashboard-admin .header-sekolah {
    background: #fff;
    border-bottom: 2px solid #ccc;
    padding: 15px 20px;
    text-align: center;
}
.dashboard-admin .header-sekolah img {
    max-height: 80px;
    display: block;
    margin: 0 auto 15px;
}
.dashboard-admin .header-sekolah h2, 
.dashboard-admin .header-sekolah h3, 
.dashboard-admin .header-sekolah p {
    margin: 2px 0;
    font-size: 14px;
}

/* CONTENT */
.dashboard-admin .content {
    flex: 1;
    width: 100%;
    max-width: 1000px;
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
.dashboard-admin .form-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    text-transform: uppercase;
}

/* FOOTER */
.dashboard-admin .footer {
    background: #d2b48c;
    color: white;
    display: flex;
    justify-content: space-between;
    padding: 20px;
    gap: 15px;
    font-size: 14px;
}
.dashboard-admin .footer .left { flex: 1; text-align: left; }
.dashboard-admin .footer .right { flex: 1; text-align: right; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .dashboard-admin .navbar {
        flex-direction: column;
        align-items: flex-start;
    }
    .dashboard-admin .user-info {
        align-self: flex-end;
        margin-top: 10px;
    }
    .dashboard-admin .content { padding: 15px; margin: 15px; }
    .dashboard-admin .footer { flex-direction: column; text-align: center; }
    .dashboard-admin .footer .left, 
    .dashboard-admin .footer .right { text-align: center; }
}
</style>
</head>
<body>

<div class="dashboard-admin">

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
            <a href="dashboard_admin.php?page=home">Home</a>
            <a href="dashboard_admin.php?page=data_user">Data User</a>
            <a href="dashboard_admin.php?page=data_jurusan">Data Jurusan</a>
            <a href="dashboard_admin.php?page=data_tempat_pkl">Data Tempat PKL</a>
            <a href="dashboard_admin.php?page=configurasi_sekolah">Configurasi</a>
            <a href="dashboard_admin.php?page=grup_observasi">Grup Observasi</a>
            <a href="dashboard_admin.php?page=grup_observasi_detail">Grup Observasi Detail</a>
            <a href="dashboard_admin.php?page=ganti_pass">Ganti Password</a>
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
        case 'data_user': include 'data_user.php'; break;
        case 'data_jurusan': include 'data_jurusan.php'; break;
        case 'data_tempat_pkl': include 'data_tempat_pkl.php'; break;
        case 'configurasi_sekolah': include 'configurasi_sekolah.php'; break;
        case 'grup_observasi': include 'grup_observasi.php'; break;
        case 'grup_observasi_detail': include 'grup_observasi_detail.php'; break;
        case 'ganti_pass': include 'ganpas.php'; break;
        default: include 'home.php'; break;
    }
    ?>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="left">
            <strong>SMK NEGERI 4 BANJARMASIN</strong><br>
            Jl. Brigjend H. Hasan Basri No.07 Banjarmasin<br>
            Telp. 0511-5209999 | Email: info@smkn4bjm.sch.id
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