<?php
$penilai_id = $_SESSION['id'] ?? null;
$data_du_list = [];

if ($penilai_id) {
    $sql = "SELECT data_du.*, data_awal.datawal_nama, data_awal.datawal_nis_nip, 
                   data_awal.datawal_photo, data_awal.datawal_ttd
            FROM data_du 
            LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa
            WHERE data_du.du_penilai = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $penilai_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data_du_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Siswa</title>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 40px;
        background: #f9fafb;
        color: #333;
    }
    h2 {
        margin-bottom: 30px;
        text-transform: uppercase;
        text-align: center;
        letter-spacing: 1.2px;
        font-size: 22px;
        color: #222;
    }
    .table-container {
        display: flex;
        justify-content: center;
    }
    table.dataTable {
        border-collapse: collapse;
        width: 85%;
        max-width: 900px;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }
    th, td {
        border-bottom: 1px solid #e6e6e6;
        padding: 14px 18px;
        font-size: 14px;
        text-align: left;
    }
    th {
        background: #f3f4f6;
        font-weight: 600;
        color: #444;
    }
    tr:hover {
        background: #f0f7ff;
        transition: 0.2s ease-in-out;
        cursor: pointer;
    }
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        justify-content: center; align-items: center;
        padding: 20px;
    }
    .modal-content {
        background: white;
        padding: 28px 25px;
        border-radius: 10px;
        max-width: 580px;
        width: 95%;
        box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        position: relative;
        animation: fadeIn 0.25s ease;
    }
    .close-btn {
        position: absolute;
        right: 18px;
        top: 12px;
        font-size: 28px;
        color: #666;
        cursor: pointer;
    }
    .modal-content h3 {
        text-align: center;
        margin-bottom: 18px;
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    #modalBody p {
        margin: 8px 0;
        font-size: 14px;
        line-height: 1.5;
    }
    .img-row {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .img-box {
        flex: 1;
        text-align: center;
        min-width: 140px;
        max-width: 180px;
    }
    .img-box img {
        max-width: 150px;
        height: auto;
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 6px;
        background: #fdfdfd;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }
</style>
</head>
<body>

<h2>DAFTAR SISWA</h2>

<?php if (empty($data_du_list)): ?>
    <p style="text-align:center; color:#777;">Tidak ada data siswa untuk penilai ini.</p>
<?php else: ?>
    <div class="table-container">
        <table id="siswaTable" class="display">
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>NIS</th>
                    <th>Periode</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_du_list as $row): ?>
                    <tr onclick="openModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">
                        <td><?php echo htmlspecialchars($row['datawal_nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['datawal_nis_nip']); ?></td>
                        <td><?php echo htmlspecialchars($row['du_mulai']); ?> s/d <?php echo htmlspecialchars($row['du_selesai']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modal -->
<div class="modal" id="dataModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Detail PKL</h3>
        <div id="modalBody"></div>
    </div>
</div>

<!-- jQuery & DataTables -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $('#siswaTable').DataTable({
        "paging": true,
        "pageLength": 5,
        "lengthMenu": [5, 10, 20, 50],
        "ordering": true,
        "info": true,
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

function openModal(data) {
    let modal = document.getElementById('dataModal');
    let body = document.getElementById('modalBody');

    let photoHtml = data.datawal_photo 
        ? `<div class="img-box"><b>Foto:</b><br><img src="upload/${data.datawal_photo}" alt="Foto Siswa"></div>` 
        : '<div class="img-box"><b>Foto:</b><br><i>Tidak ada</i></div>';

    let ttdHtml = data.datawal_ttd 
        ? `<div class="img-box"><b>Tanda Tangan:</b><br><img src="${data.datawal_ttd}" alt="Tanda Tangan"></div>` 
        : '<div class="img-box"><b>Tanda Tangan:</b><br><i>Belum ada</i></div>';

    body.innerHTML = `
        <p><b>Nama Siswa:</b> ${data.datawal_nama}</p>
        <p><b>NIS:</b> ${data.datawal_nis_nip}</p>
        <p><b>Tempat PKL:</b> ${data.du_tempat ?? '-'}</p>
        <p><b>Alamat:</b> ${data.du_alamat ?? '-'}</p>
        <p><b>Pembimbing:</b> ${data.du_pembimbing ?? '-'}</p>
        <p><b>Periode:</b> ${data.du_mulai} s/d ${data.du_selesai}</p>
        <p><b>Keterangan:</b> ${data.du_keterangan ?? '-'}</p>
        <div class="img-row">
            ${photoHtml}
            ${ttdHtml}
        </div>
    `;

    modal.style.display = 'flex';
}
function closeModal() {
    document.getElementById('dataModal').style.display = 'none';
}
</script>

</body>
</html>
