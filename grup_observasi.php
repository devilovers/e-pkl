<?php
require_once __DIR__ . '/vendor/autoload.php'; // untuk mPDF
// Pastikan koneksi $koneksi sudah ada

// --- Tambah / Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_hg'])) {
    $id     = $_POST['id_hg'] ?? '';
    $nama   = trim($_POST['nama_hg']);

    if ($id) {
        // UPDATE
        $stmt = $koneksi->prepare("UPDATE header_grup SET nama_hg=? WHERE id_hg=?");
        $stmt->bind_param("si", $nama, $id);
    } else {
        // INSERT
        $stmt = $koneksi->prepare("INSERT INTO header_grup (nama_hg, flag_hg) VALUES (?, 'T')");
        $stmt->bind_param("s", $nama);
    }
    $stmt->execute();
}

// --- Hapus Data (soft delete) ---
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $koneksi->query("UPDATE header_grup SET flag_hg='F' WHERE id_hg=$id");
    echo "<script>
        alert('Data berhasil dihapus');
        window.location='dashboard_admin.php?page=grup_observasi';
    </script>";
    exit;
}

// --- Cetak PDF ---
if (isset($_GET['cetak'])) {
    $result = $koneksi->query("SELECT * FROM header_grup WHERE flag_hg='T' ORDER BY id_hg ASC");

    $html = '<h2 style="text-align:center;">Daftar Grup Observasi</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr style="background:#f2f2f2;">
                        <th>No</th>
                        <th>Nama Grup</th>
                    </tr>
                </thead>
                <tbody>';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>'.$no++.'</td>
                    <td>'.$row['nama_hg'].'</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output("data_grup_observasi.pdf", "I");
    exit;
}

// --- Ambil Data untuk List ---
$grup_observasi = $koneksi->query("SELECT * FROM header_grup WHERE flag_hg='T' ORDER BY id_hg DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Grup Observasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body class="p-4">

    <h2><center>MANAJEMEN GRUP OBSERVASI</center></h2>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#hgModal" onclick="openModal()">+ Tambah Grup</button>

<table id="hgTable" class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th>No</th>
            <th>Nama Grup</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; while ($row = $grup_observasi->fetch_assoc()): ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nama_hg']); ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#hgModal"
                            onclick='openModal(<?= json_encode($row); ?>)'>✏ Edit</button>
                    <a href="dashboard_admin.php?page=grup_observasi&hapus=<?= $row['id_hg']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Hapus data ini?')">🗑 Hapus</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="hgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Form Grup Observasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id_hg" id="id_hg">
            <input type="hidden" name="simpan_hg" value="1">

            <div class="mb-3">
                <label>Nama Grup</label>
                <textarea class="form-control" name="nama_hg" id="nama_hg" required></textarea>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#hgTable').DataTable({
        "language": {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "paginate": {
                "first": "Awal",
                "last": "Akhir",
                "next": "→",
                "previous": "←"
            }
        }
    });
});

function openModal(data = null) {
    document.getElementById('id_hg').value = data ? data.id_hg : '';
    document.getElementById('nama_hg').value = data ? data.nama_hg : '';
}
</script>

</body>
</html>