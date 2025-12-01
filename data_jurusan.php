<?php
ob_start();
require_once __DIR__ . '/vendor/autoload.php'; // mPDF

// --- Tambah / Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jurusan'])) {
    $jur_id   = $_POST['jur_id'] ?? '';
    $jur_nama = trim($_POST['jur_nama']);

    // --- Cek duplikasi ---
    if ($jur_id) {
        $cek = $koneksi->prepare("SELECT jur_id FROM jurusan 
                                  WHERE jur_nama=? AND jur_id<>? AND jur_flag='T' LIMIT 1");
        $cek->bind_param("si", $jur_nama, $jur_id);
    } else {
        $cek = $koneksi->prepare("SELECT jur_id FROM jurusan 
                                  WHERE jur_nama=? AND jur_flag='T' LIMIT 1");
        $cek->bind_param("s", $jur_nama);
    }
    $cek->execute();
    $res = $cek->get_result();
    if ($res->num_rows > 0) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        Swal.fire({
            icon: 'warning',
            title: 'Duplikasi!',
            text: 'Nama Jurusan sudah ada!',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location='dashboard_admin.php?page=data_jurusan';
        });
        </script>";
        exit;
    }

    // --- Simpan / Update ---
    if ($jur_id) {
        $stmt = $koneksi->prepare("UPDATE jurusan SET jur_nama=? WHERE jur_id=?");
        $stmt->bind_param("si", $jur_nama, $jur_id);
        $aksi = "diubah";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO jurusan (jur_nama, jur_flag) VALUES (?, 'T')");
        $stmt->bind_param("s", $jur_nama);
        $aksi = "disimpan";
    }
    $stmt->execute();

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data berhasil $aksi!',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location='dashboard_admin.php?page=data_jurusan';
    });
    </script>";
    exit;
}

// --- Hapus Data (soft delete) ---
if (isset($_GET['hapus'])) {
    $jur_id = (int) $_GET['hapus'];
    $koneksi->query("UPDATE jurusan SET jur_flag='F' WHERE jur_id=$jur_id");
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Dihapus!',
        text: 'Data berhasil dihapus.',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location='dashboard_admin.php?page=data_jurusan';
    });
    </script>";
    exit;
}

// --- Cetak PDF ---
if (isset($_GET['cetak'])) {
    $result = $koneksi->query("SELECT * FROM jurusan WHERE jur_flag='T' ORDER BY jur_id ASC");

    $html = '<h2 style="text-align:center;">Daftar Jurusan</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr style="background:#f2f2f2;">
                        <th width="10%">No</th>
                        <th>Nama Jurusan</th>
                    </tr>
                </thead>
                <tbody>';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>'.$no++.'</td>
                    <td>'.$row['jur_nama'].'</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output("data_jurusan.pdf", "I");
    exit;
}

// --- Ambil Data untuk List ---
$jurusan = $koneksi->query("SELECT * FROM jurusan WHERE jur_flag='T' ORDER BY jur_id DESC");
ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Jurusan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
</head>
<body class="p-4">

<h2 class="text-center">MANAJEMEN JURUSAN</h2>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#jurusanModal" onclick="openModal()">+ Tambah Jurusan</button>

<table id="jurusanTable" class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th></th>
            <th width="10%">No</th>
            <th>Nama Jurusan</th>
            <th width="20%">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; while ($row = $jurusan->fetch_assoc()): ?>
        <tr>
            <td></td>
            <td><?= $no++; ?></td>
            <td><?= $row['jur_nama']; ?></td>
            <td>
                <button class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#jurusanModal"
                        onclick='openModal(<?= json_encode($row); ?>)'>✏ Edit</button>
                <a href="javascript:void(0)" 
                   class="btn btn-sm btn-danger"
                   onclick="hapusData(<?= $row['jur_id']; ?>)">🗑 Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="jurusanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Form Jurusan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="jur_id" id="jur_id">
            <input type="hidden" name="simpan_jurusan" value="1">
            <div class="mb-3">
                <label>Nama Jurusan</label>
                <input type="text" class="form-control" name="jur_nama" id="jur_nama" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#jurusanTable').DataTable({
        responsive: { details: { type: 'column', target: 'tr' } },
        columnDefs: [{ className: 'dtr-control', orderable: false, targets: 0 }],
        order: [1, 'asc'],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Awal", last: "Akhir", next: "→", previous: "←" }
        }
    });
});

// fungsi buka modal edit/tambah
function openModal(data = null) {
    document.getElementById('jur_id').value = data ? data.jur_id : '';
    document.getElementById('jur_nama').value = data ? data.jur_nama : '';
}

// fungsi hapus dengan SweetAlert konfirmasi
function hapusData(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: "Data akan dihapus dari daftar jurusan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'dashboard_admin.php?page=data_jurusan&hapus=' + id;
        }
    });
}
</script>

</body>
</html>
