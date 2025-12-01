<?php
$host = "sql106.infinityfree.com";
$user = "if0_40049627";
$pass = "Gax0M27zRe";
$dbname = "if0_40049627_db_laporanpkl";

$koneksi = new mysqli($host, $user, $pass, $dbname);

if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}
?>
