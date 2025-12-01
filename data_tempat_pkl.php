<?php
ob_start();
require_once __DIR__ . '/vendor/autoload.php'; // mPDF

// --- Tambah / Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pkl'])) {
    $id      = $_POST['pkl_id'] ?? '';
    $nama    = trim($_POST['pkl_nama']);
    $alamat  = trim($_POST['pkl_alamat']);
    $notelp  = trim($_POST['pkl_notelp']);

    // --- Cek duplikasi ---
    if ($id) {
        $cek = $koneksi->prepare("SELECT pkl_id FROM tempat_pkl 
                                  WHERE pkl_nama=? AND pkl_id<>? AND pkl_flag='T' LIMIT 1");
        $cek->bind_param("si", $nama, $id);
    } else {
        $cek = $koneksi->prepare("SELECT pkl_id FROM tempat_pkl 
                                  WHERE pkl_nama=? AND pkl_flag='T' LIMIT 1");
        $cek->bind_param("s", $nama);
    }
    $cek->execute();
    $res = $cek->get_result();
    if ($res->num_rows > 0) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        Swal.fire({
            icon: 'warning',
            title: 'Duplikasi!',
            text: 'Nama tempat PKL sudah ada!',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location='dashboard_admin.php?page=data_tempat_pkl';
        });
        </script>";
        exit;
    }

    // --- Simpan / Update ---
    if ($id) {
        $stmt = $koneksi->prepare("UPDATE tempat_pkl 
                                   SET pkl_nama=?, pkl_alamat=?, pkl_notelp=? 
                                   WHERE pkl_id=?");
        $stmt->bind_param("sssi", $nama, $alamat, $notelp, $id);
        $aksi = "diubah";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO tempat_pkl (pkl_nama, pkl_alamat, pkl_notelp, pkl_flag) 
                                   VALUES (?, ?, ?, 'T')");
        $stmt->bind_param("sss", $nama, $alamat, $notelp);
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
        window.location='dashboard_admin.php?page=data_tempat_pkl';
    });
    </script>";
    exit;
}

// --- Hapus Data (soft delete) ---
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $koneksi->query("UPDATE tempat_pkl SET pkl_flag='F' WHERE pkl_id=$id");

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Dihapus!',
        text: 'Data berhasil dihapus.',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location='dashboard_admin.php?page=data_tempat_pkl';
    });
    </script>";
    exit;
}

// --- Cetak PDF ---
if (isset($_GET['cetak'])) {
    $result = $koneksi->query("SELECT * FROM tempat_pkl WHERE pkl_flag='T' ORDER BY pkl_id ASC");

    $html = '<h2 style="text-align:center;">Daftar Tempat PKL</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr style="background:#f2f2f2;">
                        <th>No</th>
                        <th>Nama Tempat</th>
                        <th>Alamat</th>
                        <th>No. Telp</th>
                    </tr>
                </thead>
                <tbody>';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>'.$no++.'</td>
                    <td>'.$row['pkl_nama'].'</td>
                    <td>'.$row['pkl_alamat'].'</td>
                    <td>'.$row['pkl_notelp'].'</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output("data_tempat_pkl.pdf", "I");
    exit;
}

// --- Ambil Data untuk List ---
$tempat_pkl = $koneksi->query("SELECT * FROM tempat_pkl WHERE pkl_flag='T' ORDER BY pkl_id DESC");
ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Tempat PKL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
</head>
<body class="p-4">

<h2 class="text-center">MANAJEMEN TEMPAT PKL</h2>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#pklModal" onclick="openModal()">+ Tambah Tempat PKL</button>

<table id="pklTable" class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th></th>
            <th>No</th>
            <th>Nama Tempat</th>
            <th>Alamat</th>
            <th>No. Telp</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; while ($row = $tempat_pkl->fetch_assoc()): ?>
        <tr>
            <td></td>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['pkl_nama']); ?></td>
            <td><?= htmlspecialchars($row['pkl_alamat']); ?></td>
            <td><?= htmlspecialchars($row['pkl_notelp']); ?></td>
            <td>
                <button class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#pklModal"
                        onclick='openModal(<?= json_encode($row); ?>)'>✏ Edit</button>
                <a href="javascript:void(0)" 
                   class="btn btn-sm btn-danger"
                   onclick="hapusData(<?= $row['pkl_id']; ?>)">🗑 Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="pklModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Form Tempat PKL</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="pkl_id" id="pkl_id">
            <input type="hidden" name="simpan_pkl" value="1">
            <div class="mb-3">
                <label>Nama Tempat</label>
                <textarea class="form-control" name="pkl_nama" id="pkl_nama" required></textarea>
            </div>
            <div class="mb-3">
                <label>Alamat</label>
                <textarea class="form-control" name="pkl_alamat" id="pkl_alamat" required></textarea>
            </div>
            <div class="mb-3">
                <label>No. Telp</label>
                <input type="number" class="form-control" name="pkl_notelp" id="pkl_notelp" required>
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
    $('#pklTable').DataTable({
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

// buka modal tambah/edit
function openModal(data = null) {
    document.getElementById('pkl_id').value = data ? data.pkl_id : '';
    document.getElementById('pkl_nama').value = data ? data.pkl_nama : '';
    document.getElementById('pkl_alamat').value = data ? data.pkl_alamat : '';
    document.getElementById('pkl_notelp').value = data ? data.pkl_notelp : '';
}

// hapus data dengan konfirmasi SweetAlert
function hapusData(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: "Data tempat PKL akan dihapus.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'dashboard_admin.php?page=data_tempat_pkl&hapus=' + id;
        }
    });
}
</script>

</body>
</html>