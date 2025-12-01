<?php
$penilai_id = $_SESSION['id'] ?? null;
$data_du_list = [];

if ($penilai_id) {
    $sql = "SELECT data_du.*, data_awal.datawal_nama, data_awal.datawal_nis_nip, 
                   data_awal.datawal_photo, data_awal.datawal_ttd
            FROM data_du 
            LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa
            WHERE data_du.du_siswa_id = ?";
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; background: #f9fafb; }
    h2 { text-align: center; margin-bottom: 30px; }
    table.dataTable { width: 85%; margin: auto; background: #fff; border-radius: 10px; overflow: hidden; }
    th, td { padding: 14px 18px; font-size: 14px; }
    th { background: #f3f4f6; }
    tr:hover { background: #f0f7ff; cursor: pointer; }

    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
             background: rgba(0,0,0,0.4); justify-content: center; align-items: center; padding: 20px; }
    .modal-content { background: white; padding: 28px 25px; border-radius: 10px; max-width: 600px; width: 95%; position: relative; }
    .close-btn { position: absolute; right: 18px; top: 12px; font-size: 28px; cursor: pointer; }
    .modal-content h3 { text-align: center; margin-bottom: 18px; }
    #modalBody p { margin: 8px 0; }
    textarea { width: 100%; height: 100px; margin-top: 10px; padding: 10px; font-size: 14px; }
    .btn-simpan { margin-top: 15px; padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
    .btn-simpan:hover { background: #1d4ed8; }
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
                    <tr onclick='openModal(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
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
        <h3>CATATAN PENTING</h3>
        <div id="modalBody"></div>

        <div style="margin-top:20px;">
            <label><b>Kesan dan Pesan dari Dunia Usaha/Industri :</b></label>
            <textarea id="pesanInput" placeholder="Tulis pesan di sini..."></textarea>
            
            <label style="margin-top:15px; display:block;"><b>Kesan dan Pesan :</b></label>
            <textarea id="kesanInput" placeholder="Tulis kesan di sini..."></textarea>

            <div style="margin-top:15px; text-align:right;">
                <button class="btn-simpan" onclick="simpanCatatan()">Simpan</button>
                <button class="btn-simpan" onclick="cetakCatatan()">Cetak</button>
                <button class="btn-simpan" style="background:#6b7280;" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentNIS = null;

$(document).ready(function() {
    $('#siswaTable').DataTable({
        "pageLength": 5,
        "language": {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "paginate": {"first": "Awal", "last": "Akhir", "next": "→", "previous": "←"}
        }
    });
});

function openModal(data) {
    let modal = document.getElementById('dataModal');
    let body = document.getElementById('modalBody');
    currentNIS = data.datawal_nis_nip;
    body.innerHTML = `
        <p><b>Nama Siswa :</b> ${data.datawal_nama}</p>
        <p><b>NIS :</b> ${data.datawal_nis_nip}</p>
    `;

    $.post("ambil_catatan_siswa.php", { nis: currentNIS }, function(res) {
        $("#pesanInput").val(res.pesan || "");
        $("#kesanInput").val(res.kesan || "");
    }, "json");

    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('dataModal').style.display = 'none';
}

// ✅ SweetAlert disamakan dan pesan menyesuaikan hasil response PHP
function simpanCatatan() {
    let pesan = $("#pesanInput").val();
    let kesan = $("#kesanInput").val();

    if (!currentNIS) return;

    $.post("simpan_catatan_siswa.php", 
        { nis: currentNIS, pesan: pesan, kesan: kesan }, 
        function(res) {
            // console.log(res); // untuk debugging kalau perlu
            if (res.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message || "Data berhasil disimpan.",
                    confirmButtonColor: "#007BFF"
                });
            } else if (res.status === "updated") {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Terjadi kesalahan saat menyimpan data.",
                    confirmButtonColor: "#007BFF"
                });
            } else {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message || "Terjadi kesalahan saat menyimpan dataData berhasil diperbarui.",
                    confirmButtonColor: "#007BFF"
                });
            }
        }, "json"
    ).fail(function() {
        Swal.fire({
            icon: "error",
            title: "Gagal Terhubung!",
            text: "Tidak dapat terhubung ke server.",
            confirmButtonColor: "#007BFF"
        });
    });
}

function cetakCatatan() {
    let pesan = $("#pesanInput").val();
    let kesan = $("#kesanInput").val();
    if (!currentNIS) return;

    let url = `cetak_catatan_penting.php?nis=${encodeURIComponent(currentNIS)}&pesan=${encodeURIComponent(pesan)}&kesan=${encodeURIComponent(kesan)}`;
    window.open(url, "_blank");
}
</script>
</body>
</html>