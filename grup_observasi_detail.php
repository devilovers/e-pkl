<?php
require_once __DIR__ . '/vendor/autoload.php'; // mPDF
// Koneksi sudah ada di $koneksi

// --- Tambah / Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_obs'])) {
    $id         = $_POST['id_obs'] ?? '';
    $indikator  = trim($_POST['indikator_obs']);
    $jurusan    = (int)$_POST['id_jur_obs'];
    $grup       = (int)$_POST['grup_obs'];

    if ($id) {
        // UPDATE
        $stmt = $koneksi->prepare("UPDATE observasi_detail 
                                   SET indikator_obs=?, id_jur_obs=?, grup_obs=? 
                                   WHERE id_obs=?");
        $stmt->bind_param("siii", $indikator, $jurusan, $grup, $id);
    } else {
        // INSERT
        $stmt = $koneksi->prepare("INSERT INTO observasi_detail (indikator_obs, id_jur_obs, grup_obs, flag_obs) 
                                   VALUES (?, ?, ?, 'T')");
        $stmt->bind_param("sii", $indikator, $jurusan, $grup);
    }
    $stmt->execute();
    echo "<script>
        alert('Data berhasil disimpan/update');
        window.location='dashboard_admin.php?page=grup_observasi_detail';
    </script>";
    exit;
}


// --- Hapus Data (soft delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int) $_POST['hapus'];
    //echo "<script>alert('".$id."');</script>";
    if ($id > 0) {
        error_log('==== id obs  ==== : '.$id);
        $stmt = $koneksi->prepare("UPDATE observasi_detail SET flag_obs='F' WHERE id_obs=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    echo "<script>
        alert('Data berhasil dihapus');
        window.location='dashboard_admin.php?page=grup_observasi_detail';
    </script>";
    exit;
}

// --- Cetak PDF ---
if (isset($_GET['cetak'])) {
    $result = $koneksi->query("SELECT od.*, j.jur_nama, hg.nama_hg
                               FROM observasi_detail od
                               JOIN jurusan j ON od.id_jur_obs=j.jur_id
                               JOIN header_grup hg ON od.grup_obs=hg.id_hg
                               WHERE od.flag_obs='T' AND j.jur_flag='T' AND hg.flag_hg='T'
                               ORDER BY od.id_obs ASC");

    $html = '<h2 style="text-align:center;">Daftar Observasi</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr style="background:#f2f2f2;">
                        <th>No</th>
                        <th>Indikator</th>
                        <th>Jurusan</th>
                        <th>Grup</th>
                    </tr>
                </thead>
                <tbody>';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>'.$no++.'</td>
                    <td>'.$row['indikator_obs'].'</td>
                    <td>'.$row['jur_nama'].'</td>
                    <td>'.$row['nama_hg'].'</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output("data_observasi.pdf", "I");
    exit;
}

// --- Ambil Data untuk List ---
$observasi = $koneksi->query("SELECT od.*, j.jur_nama, hg.nama_hg
                              FROM observasi_detail od
                              JOIN jurusan j ON od.id_jur_obs=j.jur_id
                              JOIN header_grup hg ON od.grup_obs=hg.id_hg
                              WHERE od.flag_obs='T' AND j.jur_flag='T' AND hg.flag_hg='T'
                              ORDER BY od.id_obs DESC");

// --- Data untuk combobox ---
$jurusan = $koneksi->query("SELECT * FROM jurusan WHERE jur_flag='T' ORDER BY jur_nama ASC");
$grup    = $koneksi->query("SELECT * FROM header_grup WHERE flag_hg='T' ORDER BY nama_hg ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Observasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- DataTables Responsive CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
</head>
<body class="p-4">

    <h2><center>MANAJEMEN GRUP OBSERVASI DETAIL</center></h2>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#obsModal" onclick="openModal()">+ Tambah Observasi</button>

<table id="obsTable" class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th></th>
            <th>No</th>
            <th>Indikator</th>
            <th>Jurusan</th>
            <th>Grup</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; while ($row = $observasi->fetch_assoc()): ?>
            <tr>
                <td></td>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['indikator_obs']); ?></td>
                <td><?= htmlspecialchars($row['jur_nama']); ?></td>
                <td><?= htmlspecialchars($row['nama_hg']); ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#obsModal"
                            onclick='openModal(<?= json_encode($row); ?>)'>✏ Edit</button>
                    
                    <!-- Tombol Hapus via POST -->
                    <form method="POST" action="dashboard_admin.php?page=grup_observasi_detail" style="display:inline;" onsubmit="return confirm('Hapus data ini?')">
                        <input type="hidden" name="hapus" value="<?= $row['id_obs']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑 Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="obsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Form Observasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id_obs" id="id_obs">
            <input type="hidden" name="simpan_obs" value="1">

            <div class="mb-3">
                <label>Indikator Observasi</label>
                <textarea class="form-control" name="indikator_obs" id="indikator_obs" required></textarea>
            </div>

            <div class="mb-3">
                <label>Jurusan</label>
                <select class="form-control" name="id_jur_obs" id="id_jur_obs" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <?php 
                    $jurusan->data_seek(0);
                    while($j = $jurusan->fetch_assoc()): ?>
                        <option value="<?= $j['jur_id']; ?>"><?= htmlspecialchars($j['jur_nama']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Grup Header</label>
                <select class="form-control" name="grup_obs" id="grup_obs" required>
                    <option value="">-- Pilih Grup --</option>
                    <?php 
                    $grup->data_seek(0);
                    while($g = $grup->fetch_assoc()): ?>
                        <option value="<?= $g['id_hg']; ?>"><?= htmlspecialchars($g['nama_hg']); ?></option>
                    <?php endwhile; ?>
                </select>
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
    $('#obsTable').DataTable({
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

function openModal(data = null) {
    document.getElementById('id_obs').value = data ? data.id_obs : '';
    document.getElementById('indikator_obs').value = data ? data.indikator_obs : '';
    document.getElementById('id_jur_obs').value = data ? data.id_jur_obs : '';
    document.getElementById('grup_obs').value = data ? data.grup_obs : '';
}
</script>
<!-- DataTables Responsive JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
</body>
</html>