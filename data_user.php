<?php
require_once __DIR__ . '/vendor/autoload.php'; // untuk mPDF

// --- Tambah / Edit Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_user'])) {
    $id        = $_POST['id'] ?? '';
    $username  = trim($_POST['username']);
    $password  = $_POST['katasandi'];
    $role      = $_POST['role'];
    $nama      = $_POST['nama'];
    $nis_nip   = trim($_POST['nis_nip']);

    if (!empty($password)) {
        $hashPassword = password_hash($password, PASSWORD_DEFAULT);
    }

    // --- Cek duplikasi username dan nis_nip ---
    if ($id) {
        // Mode UPDATE → pastikan username dan nis_nip tidak dipakai user lain
        $cek = $koneksi->prepare("SELECT id FROM users 
                                  WHERE (username=? OR nis_nip=?) AND id<>? AND flag_users='T' LIMIT 1");
        $cek->bind_param("ssi", $username, $nis_nip, $id);
    } else {
        // Mode INSERT → pastikan username dan nis_nip belum ada
        $cek = $koneksi->prepare("SELECT id FROM users 
                                  WHERE (username=? OR nis_nip=?) AND flag_users='T' LIMIT 1");
        $cek->bind_param("ss", $username, $nis_nip);
    }
    $cek->execute();
    $res = $cek->get_result();

    if ($res->num_rows > 0) {
        echo "<script>alert('Username atau NIS/NIP sudah terdaftar, gunakan yang lain!'); window.location='dashboard_admin.php?page=data_user';</script>";
        exit;
    }

    // --- Proses simpan data ---
    if ($id) {
        if (!empty($password)) {
            $stmt = $koneksi->prepare("UPDATE users 
                                       SET username=?, katasandi=?, role=?, nama=?, nis_nip=? 
                                       WHERE id=?");
            $stmt->bind_param("sssssi", $username, $hashPassword, $role, $nama, $nis_nip, $id);
        } else {
            $stmt = $koneksi->prepare("UPDATE users 
                                       SET username=?, role=?, nama=?, nis_nip=? 
                                       WHERE id=?");
            $stmt->bind_param("ssssi", $username, $role, $nama, $nis_nip, $id);
        }
    } else {
        $hashPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("INSERT INTO users (username, katasandi, role, nama, nis_nip, flag_users) 
                                   VALUES (?, ?, ?, ?, ?, 'T')");
        $stmt->bind_param("sssss", $username, $hashPassword, $role, $nama, $nis_nip);
    }
    $stmt->execute();
}

// --- Hapus Data ---
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $koneksi->query("UPDATE users set flag_users='F' WHERE id=$id");
    echo "<script>
        alert('Data berhasil dihapus');
        window.location='dashboard_admin.php?page=data_user';
    </script>";
    exit;
}

// --- Cetak PDF ---
if (isset($_GET['cetak'])) {
    $result = $koneksi->query("SELECT * FROM users ORDER BY id ASC");

    $html = '<h2 style="text-align:center;">Daftar Users</h2>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr style="background:#f2f2f2;">
                        <th>No</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Nama</th>
                        <th>NIS/NIP</th>
                    </tr>
                </thead>
                <tbody>';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>'.$no++.'</td>
                    <td>'.$row['username'].'</td>
                    <td>'.$row['role'].'</td>
                    <td>'.$row['nama'].'</td>
                    <td>'.$row['nis_nip'].'</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output("data_users.pdf", "I");
    exit;
}

// --- Ambil Data untuk List ---
$users = $koneksi->query("SELECT * FROM users WHERE flag_users='T' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- DataTables Responsive CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

</head>
<body class="p-4">

    <h2><center>MANAJEMEN USERS</center></h2>
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openModal()">+ Tambah User</button>
<table id="usersTable" class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th></th>
            <th>No</th>
            <th>Username</th>
            <th>Role</th>
            <th>Nama</th>
            <th>NIS/NIP</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td></td>
                <td><?= $no++; ?></td>
                <td><?= $row['username']; ?></td>
                <td><?= $row['role']; ?></td>
                <td><?= $row['nama']; ?></td>
                <td><?= $row['nis_nip']; ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#userModal"
                            onclick='openModal(<?= json_encode($row); ?>)'>✏ Edit</button>
                    <a href="dashboard_admin.php?page=data_user&hapus=<?= $row['id']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Hapus data ini?')">🗑 Hapus</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Form User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="id">
            <input type="hidden" name="simpan_user" value="1">

            <div class="mb-3">
                <label>Username</label>
                <input type="text" class="form-control" name="username" id="username" required>
            </div>

            <div class="mb-3">
                <label>Password <small class="text-muted">(kosongkan jika tidak diganti)</small></label>
                <input type="password" class="form-control" name="katasandi" id="katasandi">
            </div>

            <div class="mb-3">
                <label>Role</label>
                <select class="form-control" name="role" id="role" required>
                    <option value="">-- Pilih --</option>
                    <option value="Siswa">Siswa</option>
                    <option value="Pembimbing">Pembimbing</option>
                    <option value="Penilai">Penilai</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Nama</label>
                <input type="text" class="form-control" name="nama" id="nama" required>
            </div>

            <div class="mb-3">
                <label>NIS/NIP</label>
                <input type="text" class="form-control" name="nis_nip" id="nis_nip" required>
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

<!-- jQuery & Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Responsive JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 }
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
    document.getElementById('id').value = data ? data.id : '';
    document.getElementById('username').value = data ? data.username : '';
    document.getElementById('katasandi').value = '';
    document.getElementById('role').value = data ? data.role : '';
    document.getElementById('nama').value = data ? data.nama : '';
    document.getElementById('nis_nip').value = data ? data.nis_nip : '';
}
</script>
</body>
</html>