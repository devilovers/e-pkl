<?php
$penilai_id = $_SESSION['id'] ?? null;
$data_du_list = [];

if ($penilai_id) {
    $sql = "SELECT data_du.*, data_awal.datawal_nama, data_awal.datawal_nis_nip, 
                   data_awal.datawal_photo, data_awal.datawal_ttd
            FROM data_du 
            LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa
            WHERE data_du.du_pembimbing = ?";
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Daftar Siswa</title>
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
    table {
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
    tr:last-child td { border-bottom: none; }
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
        max-width: 700px;
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
        transition: 0.2s;
    }
    .close-btn:hover { color: #000; }

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

    /* Foto & TTD */
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
    .img-box b {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        color: #555;
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

    canvas {
        border: 1px solid #ccc;
        border-radius: 6px;
        background: #fff;
        cursor: crosshair;
        margin-top: 15px;
        touch-action: none; /* wajib untuk HP */
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body>

<h2>DAFTAR SISWA</h2>

<?php if (empty($data_du_list)): ?>
    <p style="text-align:center; color:#777;">Tidak ada data siswa untuk penilai ini.</p>
<?php else: ?>
    <div class="table-container">
        <table id="siswaTable">
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>NIS</th>
                    <th>Periode</th>
                    <th>Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_du_list as $row): ?>
                    <tr onclick="openModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">
                        <td><?php echo htmlspecialchars($row['datawal_nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['datawal_nis_nip']); ?></td>
                        <td><?php echo htmlspecialchars($row['du_mulai']); ?> s/d <?php echo htmlspecialchars($row['du_selesai']); ?></td>
                        <td>
    <?php if (!empty($row['ttd_pembimbing'])): ?>
        <img src="<?php echo $row['ttd_pembimbing']; ?>" alt="TTD" style="max-width:80px; max-height:50px;">
    <?php else: ?>
        <i>Belum ada</i>
    <?php endif; ?>
</td>
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

        <hr>
        <h3>Tanda Tangan Pembimbing</h3>
        <form method="POST" action="simpan_ttd.php" onsubmit="return saveTtd();">
            <input type="hidden" name="du_id" id="du_id">
            <input type="hidden" name="ttd_pembimbing" id="ttd_pembimbing">
            <canvas id="ttdCanvas" width="500" height="200"></canvas><br>
            <button type="button" onclick="clearTtd()">Hapus</button>
            <button type="submit">Simpan</button>
        </form>
    </div>
</div>

<!-- jQuery + DataTables -->
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

// Modal data siswa
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

    document.getElementById("du_id").value = data.du_id;
    modal.style.display = 'flex';
}
function closeModal() {
    document.getElementById('dataModal').style.display = 'none';
}

// Canvas tanda tangan (PC + Smartphone)
let canvas = document.getElementById("ttdCanvas");
let ctx = canvas.getContext("2d");
let drawing = false;

// --- Mouse (PC) ---
canvas.addEventListener("mousedown", e => {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
});
canvas.addEventListener("mousemove", e => {
    if (drawing) {
        ctx.lineTo(e.offsetX, e.offsetY);
        ctx.stroke();
    }
});
canvas.addEventListener("mouseup", () => drawing = false);
canvas.addEventListener("mouseout", () => drawing = false);

// --- Touch (Smartphone) ---
canvas.addEventListener("touchstart", e => {
    e.preventDefault();
    if (e.touches.length > 0) {
        let rect = canvas.getBoundingClientRect();
        let x = e.touches[0].clientX - rect.left;
        let y = e.touches[0].clientY - rect.top;
        drawing = true;
        ctx.beginPath();
        ctx.moveTo(x, y);
    }
}, { passive: false });

canvas.addEventListener("touchmove", e => {
    e.preventDefault();
    if (drawing && e.touches.length > 0) {
        let rect = canvas.getBoundingClientRect();
        let x = e.touches[0].clientX - rect.left;
        let y = e.touches[0].clientY - rect.top;
        ctx.lineTo(x, y);
        ctx.stroke();
    }
}, { passive: false });

canvas.addEventListener("touchend", e => {
    e.preventDefault();
    drawing = false;
});

function clearTtd() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function saveTtd() {
    let dataUrl = canvas.toDataURL("image/png");
    document.getElementById("ttd_pembimbing").value = dataUrl;
    return true; // biar form tetap submit
}
</script>

</body>
</html>
