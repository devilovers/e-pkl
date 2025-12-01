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
/* Modal box */
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
/* Close button */
.close-btn {
    position: absolute;
    right: 18px; top: 12px;
    font-size: 28px; color: #666;
    cursor: pointer;
}
/* Scrollable body */
#modalBody {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    max-height: 60vh; /* biar bisa scroll */
}
/* Sticky footer */
.modal-footer {
    border-top: 1px solid #ddd;
    padding: 10px;
    background: #fafafa;
    text-align: center;
    position: sticky;
    bottom: 0;
}
/* Table */
.obs-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.obs-table th, .obs-table td { border: 1px solid #ddd; padding: 8px; }
.obs-table th { background: #f3f4f6; position: sticky; top: 0; z-index: 2; }

/* Canvas tanda tangan */
canvas {
    border: 1px solid #999;
    border-radius: 5px;
    height: 150px;
    background: #fff;
    touch-action: none;
    -ms-touch-action: none;
}

/* Tombol */
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
    <p style="text-align:center; color:#777;">Tidak ada data siswa untuk pembimbing ini.</p>
<?php else: ?>
    <table id="siswaTable" class="display">
        <thead>
            <tr>
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
                    <td><?php echo htmlspecialchars($row['datawal_nama']); ?></td>
                    <td><?php echo htmlspecialchars($row['datawal_nis_nip']); ?></td>
                    <td><?php echo htmlspecialchars($row['du_mulai']); ?> s/d <?php echo htmlspecialchars($row['du_selesai']); ?></td>
                    <td>
                        <?php if (!empty($row['ttd_penilai_oss'])): ?>
                            <img src="<?php echo htmlspecialchars($row['ttd_penilai_oss']); ?>" 
                                alt="Tanda Tangan Penilai" 
                                style="max-width:120px; max-height:80px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (!empty($row['ttd_pembimbing_oss'])): ?>
                            <img src="<?php echo htmlspecialchars($row['ttd_pembimbing_oss']); ?>" 
                                alt="Tanda Tangan Pembimbing" 
                                style="max-width:120px; max-height:80px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
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

            <!-- footer sticky -->
            <div class="modal-footer">
                <p><b>Tanda Tangan Penilai</b></p>
                <canvas id="signaturePad"></canvas><br>
                <button type="button" class="btn btn-danger" onclick="clearSignature()">Clear</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Signature Pad JS -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
$(document).ready(function() {
    $('#siswaTable').DataTable();
});

let currentSiswaId = null;
let signaturePad = null;

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
        <p><b>Keterangan:</b> ${data.du_keterangan ?? '-'}</p>
        <hr>
    `;

    fetch(`get_observasi.php?id_jur=${data.id_jur}&id_siswa=${data.datawal_id_siswa}`)
        .then(res => res.json())
        .then(rows => {
            let tableHtml = `
                <table class="obs-table">
                    <thead>
                        <tr>
                            <th>NO</th><th>TUJUAN / INDIKATOR</th><th>KETERCAPAIAN</th><th>DESKRIPSI</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            rows.forEach((grup, i) => {
                tableHtml += `
                    <tr>
                        <td rowspan="${(grup.indikator?.length||0)+1}">${i+1}</td>
                        <td><b>${grup.nama_hg}</b></td>
                        <td></td><td></td>
                    </tr>
                `;
                (grup.indikator || []).forEach(ind => {
                    tableHtml += `
                        <tr>
                            <td>${ind.indikator_obs}</td>
                            <td>
                                <select name="capai[${ind.id_obs}]" disabled>
                                    <option value="" ${ind.capai==""?"selected":""}>-</option>
                                    <option value="Y" ${ind.capai=="Y"?"selected":""}>YES</option>
                                    <option value="N" ${ind.capai=="N"?"selected":""}>NO</option>
                                </select>
                            </td>
                            <td><textarea name="deskripsi[${ind.id_obs}]" rows="2" style="width:100%;" readonly >${ind.deskripsi||""}</textarea></td>
                        </tr>
                    `;
                });
            });
            tableHtml += `</tbody></table>`;
            body.innerHTML = siswaHtml + tableHtml;
            modal.style.display = 'flex';

            initSignature(data.ttd_pembimbing_oss || null);
        })
        .catch(err => {
            body.innerHTML = siswaHtml + `<div style="color:red;">Gagal load observasi</div>`;
            modal.style.display = 'flex';

            initSignature(data.ttd_pembimbing_oss || null);
        });
}

function closeModal() {
    document.getElementById('dataModal').style.display = 'none';
}

function initSignature(existingTtd = null) {
    let canvas = document.getElementById("signaturePad");
    resizeCanvas(canvas);

    if (signaturePad) {
        signaturePad.off();
        signaturePad = null;
    }

    signaturePad = new SignaturePad(canvas, {
        backgroundColor: "rgb(255,255,255)",
        penColor: "rgb(0,0,0)",
    });

    // FIX: agar touch di smartphone tidak dianggap scroll
    ["touchstart","touchmove","touchend"].forEach(evt => {
        canvas.addEventListener(evt, function(e) {
            e.preventDefault();
        }, { passive:false });
    });

    // tampilkan tanda tangan lama kalau ada
    if (existingTtd) {
        let img = new Image();
        img.onload = () => {
            let ctx = canvas.getContext("2d");
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        };
        img.src = existingTtd;
    }
}

function clearSignature() {
    if (signaturePad) signaturePad.clear();
}

function resizeCanvas(canvas) {
    let ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = 150 * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
}

$("#observasiForm").submit(function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    formData.append("id_siswa", currentSiswaId);

    let ttdData = (signaturePad && !signaturePad.isEmpty()) ? signaturePad.toDataURL("image/png") : "";
    formData.append("ttd", ttdData);

    $.ajax({
        url: "simpan_observasi_pembimbing.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            alert(res);
            closeModal();

            // refresh atau redirect ke halaman lain
            window.location = 'dashboard_pembimbing.php?page=lembar_observasi';
        },
        error: function() {
            alert("Gagal menyimpan data");
        }
    });
});
</script>
</body>
</html>
