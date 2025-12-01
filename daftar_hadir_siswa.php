<?php
ob_start();
 // pastikan sudah ada koneksi

// --- Proses CRUD (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $status = "error"; 
    $message = "Terjadi kesalahan";

    if ($action === 'add') {
        $id = $_SESSION['nis_nip'];
        $stmt = $koneksi->prepare("INSERT INTO daftar_hadir 
            (id_siswa_had, tanggal_had, jam_masuk_had, jam_keluar_had, keterangan_had) 
            VALUES (?,?,?,?,?)");
        $stmt->bind_param(
            "sssss",
            $id,
            $_POST['tanggal_had'],
            $_POST['jam_masuk_had'],
            $_POST['jam_keluar_had'],
            $_POST['keterangan_had']
        );
        if ($stmt && $stmt->execute()) {
            $status = "success"; 
            $message = "Daftar hadir berhasil disimpan!";
        }
    } elseif ($action === 'edit') {
        $stmt = $koneksi->prepare("UPDATE daftar_hadir SET 
            tanggal_had=?, jam_masuk_had=?, jam_keluar_had=?, keterangan_had=? 
            WHERE id_had=? AND id_siswa_had=?");
        $stmt->bind_param(
            "ssssii",
            $_POST['tanggal_had'],
            $_POST['jam_masuk_had'],
            $_POST['jam_keluar_had'],
            $_POST['keterangan_had'],
            $_POST['id_had'],
            $_SESSION['nis_nip']
        );
        if ($stmt && $stmt->execute()) {
            $status = "success"; 
            $message = "Daftar hadir berhasil diperbarui!";
        }
    } elseif ($action === 'delete') {
        $stmt = $koneksi->prepare("DELETE FROM daftar_hadir WHERE id_had=? AND id_siswa_had=?");
        $stmt->bind_param("ii", $_POST['id_had'], $_SESSION['nis_nip']);
        if ($stmt && $stmt->execute()) {
            $status = "success"; 
            $message = "Daftar hadir berhasil dihapus!";
        }
    }

    // --- Kembalikan JSON ---
    while (ob_get_level()) { ob_end_clean(); }
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

// --- Ambil data untuk tampilan (GET) ---
$data = [];
$res = $koneksi->query("SELECT * FROM daftar_hadir WHERE id_siswa_had=".$_SESSION['nis_nip']." ORDER BY tanggal_had DESC");
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Hadir PKL</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- DataTables Responsive CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
h2 { text-align: center; text-transform: uppercase; margin-bottom: 20px; }
button { padding: 8px 14px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #0056b3; }
table.dataTable { background: white; border-radius: 6px; overflow: hidden; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index: 1000; }
.modal-content { background:white; padding:20px; border-radius:10px; width: 500px; max-width: 90%; }
.modal-content h3 { text-align: center; text-transform: uppercase; margin-bottom: 15px; }
form label { display:block; margin-top:10px; font-weight: bold; }
form input, form textarea { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:5px; }
</style>
</head>
<body>

<h2>DAFTAR HADIR</h2>
<div style="text-align: right; margin-bottom: 15px;">
    <button onclick="openModal('add')">+ Tambah Daftar Hadir</button>
</div>

<table id="catatanTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th></th>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Jam Keluar</th>
            <th>Keterangan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row): ?>
        <tr>
            <td></td>
            <td><?= htmlspecialchars($row['tanggal_had']) ?></td>
            <td><?= htmlspecialchars($row['jam_masuk_had']) ?></td>
            <td><?= htmlspecialchars($row['jam_keluar_had']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['keterangan_had'])) ?></td>
            <td><?= htmlspecialchars($row['status_had'] ?? '') ?></td>
            <td>
                <button onclick='openModal("edit", <?= json_encode($row) ?>)'>Edit</button>
                <button style="background:red;" onclick='deleteData(<?= (int)$row["id_had"] ?>)'>Hapus</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="text-align: right; margin-bottom: 15px;">
    <button style="background:green;" onclick="openCetakModal()">📄 Cetak PDF</button>
</div>

<!-- Modal Cetak -->
<div class="modal" id="cetakModal" aria-hidden="true">
  <div class="modal-content">
    <h3>Pilih Rentang Tanggal</h3>
    <form id="cetakForm" method="GET" action="cetak_daftar_hadir.php" target="_blank">
        <label>Dari Tanggal</label>
        <input type="date" name="start_date" required>
        <label>Sampai Tanggal</label>
        <input type="date" name="end_date" required>
        <div style="margin-top:15px; text-align:right;">
            <button type="submit">Cetak</button>
            <button type="button" style="background:gray;" onclick="closeCetakModal()">Batal</button>
        </div>
    </form>
  </div>
</div>

<script>
function openCetakModal(){
    $("#cetakModal").css("display","flex").attr("aria-hidden","false");
}
function closeCetakModal(){
    $("#cetakModal").hide().attr("aria-hidden","true");
}
</script>

<!-- Modal Form -->
<div class="modal" id="formModal" aria-hidden="true">
  <div class="modal-content">
    <h3 id="modalTitle">Tambah Daftar Hadir</h3>
    <form id="catatanForm" onsubmit="return false;">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id_had" id="id_had">

        <label>Tanggal</label>
        <input type="date" name="tanggal_had" id="tanggal_had" required>

        <label>Jam Masuk</label>
        <input type="time" name="jam_masuk_had" id="jam_masuk_had" required>

        <label>Jam Keluar</label>
        <input type="time" name="jam_keluar_had" id="jam_keluar_had" required>

        <label>Keterangan</label>
        <textarea name="keterangan_had" id="keterangan_had"></textarea>

        <div style="margin-top:15px; text-align:right;">
            <button type="button" onclick="saveData()">Simpan</button>
            <button type="button" style="background:gray;" onclick="closeModal()">Batal</button>
        </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let dataTable;
$(document).ready(function() {
    $('#catatanTable').DataTable({
        responsive: {
            details: {
                type: 'column',
                target: 'tr'  // klik baris untuk buka detail
            }
        },
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 } // tanda arrow di kolom No
        ],
        order: [1, 'asc'],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "→",
                previous: "←"
            }
        }
    });
});

function openModal(action, data = null){
    $("#formAction").val(action);
    $("#catatanForm")[0].reset();
    if(action === 'edit' && data){
        $("#modalTitle").text("Edit Daftar Hadir");
        $("#id_had").val(data.id_had);
        $("#tanggal_had").val(data.tanggal_had);
        $("#jam_masuk_had").val(data.jam_masuk_had);
        $("#jam_keluar_had").val(data.jam_keluar_had);
        $("#keterangan_had").val(data.keterangan_had);
    } else {
        $("#modalTitle").text("Tambah Daftar Hadir");
    }
    $("#formModal").css("display","flex").attr("aria-hidden","false");
}

function closeModal(){
    $("#formModal").hide().attr("aria-hidden","true");
}

// Util: kirim AJAX JSON
function postJSON(dataObj, onSuccess){
    $.ajax({
        url: window.location.href,
        type: "POST",
        data: dataObj,
        dataType: "json",
        success: function(res){
            onSuccess(res);
        },
        error: function(xhr, status, error){
            window.location.href = "dashboard_siswa.php?page=daftar_hadir";
           
        }
    });
}

// Simpan (add/edit)
function saveData(){
    const payload = $("#catatanForm").serialize();
    postJSON(payload, function(res){
        if(res.status === "success"){
            closeModal();
            Swal.fire("Berhasil", res.message, "success");
             window.location.href = "dashboard_siswa.php?page=daftar_hadir";
        } else {
             window.location.href = "dashboard_siswa.php?page=daftar_hadir";
        }
    });
}

// Hapus
function deleteData(id){
    Swal.fire({
        title: "Yakin ingin menghapus?",
        text: "Data akan hilang permanen",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal"
    }).then((result)=>{
        if(result.isConfirmed){
            postJSON({action:"delete", id_had:id}, function(res){
                if(res.status === "success"){
                    Swal.fire("Berhasil", res.message, "success");
                     window.location.href = "dashboard_siswa.php?page=daftar_hadir";
                } else {
                    window.location.href = "dashboard_siswa.php?page=daftar_hadir";
                }
            });
        }
    });
}

// Reload table
function reloadTable(){
    $.ajax({
        url: window.location.href,
        type: "GET",
        success: function(response){
            let newRows = $(response).find("#catatanTable tbody").html();
            if(newRows){
                if ($.fn.DataTable.isDataTable('#catatanTable')) {
                    dataTable.clear().destroy();
                }
                $("#catatanTable tbody").html(newRows);
                dataTable = $('#catatanTable').DataTable();
            }
        },
        error: function(){
            Swal.fire("Error", "Gagal memuat ulang tabel", "error");
        }
    });
}
</script>
<!-- DataTables Responsive JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
</body>
</html>
