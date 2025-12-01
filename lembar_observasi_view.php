<?php
$penilai_id = $_SESSION['id'] ?? null;
include "config.php";

$data_du_list = [];

if ($penilai_id) {
    $sql = "SELECT data_du.*, data_awal.datawal_id_siswa, data_awal.datawal_nama, data_awal.datawal_nis_nip, 
                   data_awal.datawal_photo, data_awal.datawal_ttd, data_awal.datawal_jurusan as id_jur, ttd_penilai_oss, ttd_pembimbing_oss
            FROM data_du 
            LEFT JOIN data_awal ON data_du.du_siswa_id = data_awal.datawal_id_siswa 
            LEFT JOIN observasi_ttd on data_du.du_siswa_id = observasi_ttd.id_siswa_ttd_oss  
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Daftar Siswa</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- ✅ Tambahkan SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Modal overlay */
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
    border-radius: 10px;
    max-width: 900px;
    width: 95%;
    max-height: 95vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
    position: relative;
}
.close-btn {
    position: absolute;
    right: 18px; top: 12px;
    font-size: 28px; color: #666;
    cursor: pointer;
}
#modalBody {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    max-height: 60vh;
}
.modal-footer {
    border-top: 1px solid #ddd;
    padding: 10px;
    background: #fafafa;
    text-align: center;
    position: sticky;
    bottom: 0;
}
.obs-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.obs-table th, .obs-table td { border: 1px solid #ddd; padding: 8px; }
.obs-table th { background: #f3f4f6; position: sticky; top: 0; z-index: 2; }
canvas {
    border:1px solid #ccc;
    border-radius:6px;
    background:#fafafa;
    touch-action: none;
}
.btn {
    padding: 8px 14px; border: none; border-radius: 6px;
    cursor: pointer; margin: 4px;
}
.btn-primary { background: #007bff; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
</style>
</head>
<body>

<h2><center>DAFTAR SISWA</center></h2>

<?php if (empty($data_du_list)): ?>
    <p style="text-align:center; color:#777;">Tidak ada data siswa untuk penilai ini.</p>
<?php else: ?>
    <table id="siswaTable" class="display">
        <thead>
            <tr>
                <th></th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th>Periode</th>
                <th>TTD Penilai</th>
                <th>TTD Pembimbing</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data_du_list as $row): ?>
                <tr onclick="openModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">
                    <td></td>
                    <td><?php echo htmlspecialchars($row['datawal_nama']); ?></td>
                    <td><?php echo htmlspecialchars($row['datawal_nis_nip']); ?></td>
                    <td><?php echo htmlspecialchars($row['du_mulai']); ?> s/d <?php echo htmlspecialchars($row['du_selesai']); ?></td>
                    <td>
                        <?php if (!empty($row['ttd_penilai_oss'])): ?>
                            <img src="<?php echo htmlspecialchars($row['ttd_penilai_oss']); ?>" 
                                alt="Tanda Tangan Penilai" 
                                style="max-width:120px; max-height:80px;">
                        <?php else: ?>- <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($row['ttd_pembimbing_oss'])): ?>
                            <img src="<?php echo htmlspecialchars($row['ttd_pembimbing_oss']); ?>" 
                                alt="Tanda Tangan Pembimbing" 
                                style="max-width:120px; max-height:80px;">
                        <?php else: ?>- <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Modal -->
<div class="modal" id="dataModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 style="margin:15px;">Detail PKL</h3>
        <form id="observasiForm" style="flex:1; display:flex; flex-direction:column;">
            <div id="modalBody"></div>

            <div class="modal-footer">
                <p><b>Tanda Tangan Penilai</b></p>
                <canvas id="signaturePad"></canvas><br>
                <button type="button" class="btn btn-danger" onclick="clearSignature()">Clear</button>
                <button type="submit" class="btn btn-primary">Simpan / Update</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $('#siswaTable').DataTable({
        responsive: true,
        columnDefs: [{ className: 'dtr-control', orderable: false, targets: 0 }],
        order: [1, 'asc'],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Awal", last: "Akhir", next: "→", previous: "←"
            }
        }
    });
});

let currentSiswaId = null;

function openModal(data) {
    currentSiswaId = data.datawal_id_siswa;
    let modal = document.getElementById('dataModal');
    let body = document.getElementById('modalBody');

    let siswaHtml = `
        <p><b>ID Siswa:</b> ${data.du_siswa_id}</p>
        <p><b>Nama Siswa:</b> ${data.datawal_nama}</p>
        <p><b>NIS:</b> ${data.datawal_nis_nip}</p>
        <p><b>Tempat PKL:</b> ${data.du_tempat ?? '-'}</p>
        <p><b>Alamat:</b> ${data.du_alamat ?? '-'}</p>
        <p><b>Pembimbing:</b> ${data.du_pembimbing ?? '-'}</p>
        <p><b>Penilai:</b> ${data.du_penilai ?? '-'}</p>
        <p><b>Periode:</b> ${data.du_mulai} s/d ${data.du_selesai}</p>
        <p><b>Keterangan:</b> ${data.du_keterangan ?? '-'}</p><hr>
    `;

    fetch(`get_observasi.php?id_jur=${data.id_jur}&id_siswa=${data.datawal_id_siswa}`)
        .then(res => res.json())
        .then(rows => {
            let tableHtml = `<table class="obs-table"><thead><tr>
                <th>NO</th><th>TUJUAN / INDIKATOR</th><th>KETERCAPAIAN</th><th>DESKRIPSI</th>
            </tr></thead><tbody>`;
            rows.forEach((grup, i) => {
                tableHtml += `<tr><td rowspan="${(grup.indikator?.length||0)+1}">${i+1}</td>
                    <td><b>${grup.nama_hg}</b></td><td></td><td></td></tr>`;
                (grup.indikator || []).forEach(ind => {
                    tableHtml += `<tr>
                        <td>${ind.indikator_obs}</td>
                        <td>
                            <select name="capai[${ind.id_obs}]">
                                <option value="" ${ind.capai==""?"selected":""}>-</option>
                                <option value="Y" ${ind.capai=="Y"?"selected":""}>YES</option>
                                <option value="N" ${ind.capai=="N"?"selected":""}>NO</option>
                            </select>
                        </td>
                        <td><textarea name="deskripsi[${ind.id_obs}]" rows="2" style="width:100%;">${ind.deskripsi||""}</textarea></td>
                    </tr>`;
                });
            });
            tableHtml += `</tbody></table>`;
            body.innerHTML = siswaHtml + tableHtml;
            modal.style.display = 'flex';
            initSignature(data.ttd_penilai_oss || null);
        })
        .catch(err => {
            body.innerHTML = siswaHtml + `<div style="color:red;">Gagal load observasi</div>`;
            modal.style.display = 'flex';
            initSignature(data.ttd_penilai_oss || null);
        });
}

function closeModal() {
    document.getElementById('dataModal').style.display = 'none';
}

function initSignature(existingTtd = null) {
    let canvas = document.getElementById("signaturePad");
    let ctx = canvas.getContext("2d");
    canvas.width = canvas.offsetWidth;
    canvas.height = 150;
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.fillStyle = "#fff";
    ctx.fillRect(0,0,canvas.width,canvas.height);

    if (existingTtd) {
        let img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        img.src = existingTtd;
    }

    let drawing = false;
    canvas.onmousedown = e => {drawing = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY)};
    canvas.onmousemove = e => {if(drawing){ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke();}};
    canvas.onmouseup = () => drawing = false;
    canvas.onmouseleave = () => drawing = false;
}

function clearSignature() {
    let canvas = document.getElementById("signaturePad");
    let ctx = canvas.getContext("2d");
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.fillStyle = "#fff";
    ctx.fillRect(0,0,canvas.width,canvas.height);
}

/* ✅ Ganti alert() jadi SweetAlert2 */
$("#observasiForm").submit(function(e){
    e.preventDefault();
    let formData = new FormData(this);
    formData.append("id_siswa", currentSiswaId);
    let canvas = document.getElementById("signaturePad");
    formData.append("ttd", canvas.toDataURL("image/png"));

    Swal.fire({
        title: 'Menyimpan...',
        text: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: "simpan_observasi_penilai.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: res,
                confirmButtonText: 'OK'
            }).then(() => {
                closeModal();
                window.location = 'dashboard_penilai.php?page=lembar_observasi';
            });
        },
        error: function() {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Gagal menyimpan data. Silakan coba lagi.',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>

<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
</body>
</html>