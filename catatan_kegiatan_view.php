<?php 
$penilai_id = $_SESSION['id'] ?? null;
$data_du_list = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']=='update') {

    $id   = $_POST['id'] ?? null;
    $catatan_instruktur = $_POST['catatan_instruktur'] ?? '';
    $paraf_pembimbing   = $_POST['paraf_pembimbing'] ?? '';

    if ($id) {
        $stmt = $koneksi->prepare("UPDATE catatan_kegiatan 
                                   SET catatan_instruktur=?, paraf_pembimbing=? 
                                   WHERE id=?");
        $stmt->bind_param("ssi", $catatan_instruktur, $paraf_pembimbing, $id);

        if ($stmt->execute()) {
        } else {
        }
    } else {
    }
}

    $sql = "SELECT * 
            FROM catatan_kegiatan ck
            LEFT JOIN data_du du ON ck.siswa_id = du.du_siswa_id
            LEFT JOIN data_awal da ON du.du_siswa_id = da.datawal_id_siswa
            WHERE du.du_penilai = ?
            ORDER BY ck.tanggal DESC";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $penilai_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data_du_list[] = $row;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Catatan Kegiatan PKL</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0; padding: 30px;
    background: #f9fafb;
    color: #333;
}
h2 {
    text-align: center;
    text-transform: uppercase;
    margin-bottom: 25px;
    font-size: 22px;
}
table.dataTable {
    width: 90%; margin: auto;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}
th, td {
    padding: 12px 16px;
    font-size: 14px;
}
th {
    background: #f3f4f6;
    font-weight: 600;
}
tr:hover { background: #f0f7ff; cursor: pointer; }
.modal {
    display:none; position:fixed; z-index:1000;
    left:0; top:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center; align-items:center;
}
.modal-content {
    background:white;
    padding:10px;
    border-radius:10px;
    max-width:650px;
    width:95%;
    box-shadow:0 8px 20px rgba(0,0,0,0.2);
    position:relative;
}
.close-btn {
    position:absolute; right:15px; top:10px;
    font-size:22px; cursor:pointer; color:#666;
}
.modal-content h3 { text-align:center; margin-bottom:15px; }
form label { display:block; margin-top:10px; font-weight:600; }
form input, form textarea {
    width:100%; padding:8px; margin-top:5px;
    border:1px solid #ccc; border-radius:5px;
}
canvas {
    border:1px solid #ccc;
    border-radius:6px;
    background:#fafafa;
}
button {
    padding:8px 14px; border:none;
    border-radius:5px; cursor:pointer;
    margin:5px;
}
.btn-save { background:#007bff; color:white; }
.btn-cancel { background:gray; color:white; }
</style>
</head>
<body>

<h2>DAFTAR CATATAN KEGIATAN</h2>

<table id="siswaTable" class="display">
    <thead>
        <tr>
            <th>Nama</th>
            <th>NIS</th>
            <th>Tanggal</th>
            <th>Unit Pekerjaan</th>
             <th>Catatan Penilaian</th>
            <th>Paraf</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data_du_list as $row): 
            $paraf_pembimbing = empty($row['paraf_pembimbing']) ? "" : "<img src='".$row['paraf_pembimbing']."' height='50px'>";
        ?>
            <tr onclick='openModal(<?= json_encode($row, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                <td><?= htmlspecialchars($row['datawal_nama']) ?></td>
                <td><?= htmlspecialchars($row['datawal_nis_nip']) ?></td>
                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                <td><?= htmlspecialchars($row['unit_pekerjaan']) ?></td>
                        <td><?= htmlspecialchars($row['catatan_instruktur']) ?></td>
                <td><?= $paraf_pembimbing ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal" id="dataModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Detail Catatan Kegiatan</h3>
        <form id="updateForm">
            <input type="hidden" name="id" id="id">
            <input type="hidden" name="action" value="update">

            <label>Tanggal</label>
            <input type="text" id="tanggal" readonly>

            <label>Unit Pekerjaan</label>
            <input type="text" id="unit_pekerjaan" readonly>

            <label>Perencanaan</label>
            <textarea id="perencanaan" readonly></textarea>

            <label>Pelaksanaan</label>
            <textarea id="pelaksanaan" readonly></textarea>

            <label>Catatan Penilaian</label>
            <textarea name="catatan_instruktur" id="catatan_instruktur"></textarea>

            <label>Paraf Pembimbing</label>
            <canvas id="signaturePad"></canvas>
            <button type="button" onclick="clearCanvas()">Hapus TTD</button>

            <div style="margin-top:15px; text-align:right;">
                <button type="button" class="btn-save" onclick="saveData()">Simpan</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let dataTable, ctx;

$(document).ready(function() {
    dataTable = $('#siswaTable').DataTable({
        pageLength:5, lengthMenu:[5,10,20],
        language: {
            search:"Cari:", lengthMenu:"Tampilkan _MENU_ data",
            info:"Menampilkan _START_ - _END_ dari _TOTAL_ data"
        }
    });
    initCanvas();
});

function openModal(data){
    $("#id").val(data.id);
    $("#tanggal").val(data.tanggal);
    $("#unit_pekerjaan").val(data.unit_pekerjaan);
    $("#perencanaan").val(data.perencanaan);
    $("#pelaksanaan").val(data.pelaksanaan);
    $("#catatan_instruktur").val(data.catatan_instruktur || "");
    clearCanvas();
    $("#dataModal").css("display","flex");
}

function closeModal(){
    $("#dataModal").hide();
}

function initCanvas(){
    let canvas = document.getElementById("signaturePad");
    ctx = canvas.getContext("2d");
    let drawing = false;

    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
    ctx.strokeStyle = "#000";

    function getPos(e){
    let rect = canvas.getBoundingClientRect();
    if(e.touches && e.touches.length > 0){
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top
        };
    } else {
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }
}

    function startDraw(e){
        drawing = true;
        ctx.beginPath();
        let pos = getPos(e);
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
    }

    function draw(e){
        if(!drawing) return;
        let pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        e.preventDefault();
    }

    function endDraw(){
        drawing = false;
        ctx.closePath();
    }

    canvas.addEventListener("mousedown", startDraw);
    canvas.addEventListener("mousemove", draw);
    canvas.addEventListener("mouseup", endDraw);
    canvas.addEventListener("mouseleave", endDraw);

    canvas.addEventListener("touchstart", startDraw);
    canvas.addEventListener("touchmove", draw);
    canvas.addEventListener("touchend", endDraw);
    canvas.addEventListener("touchcancel", endDraw);
}

function clearCanvas(){
    let canvas = document.getElementById("signaturePad");
    ctx.clearRect(0,0,canvas.width,canvas.height);
}

function saveData(){
    let formData = new FormData(document.getElementById("updateForm"));
    let canvas = document.getElementById("signaturePad");
    formData.set("paraf_pembimbing", canvas.toDataURL("image/png"));

    $.ajax({
        url: window.location.pathname + window.location.search,
        type: "POST",
        data: formData,
        processData:false,
        contentType:false,
        success: function(res){
            if(res.trim() === "OK"){
                Swal.fire("Berhasil", "Data berhasil diperbarui", "success");
                closeModal();
                setTimeout(()=>location.reload(), 800);
                 location.reload(); 
            } else {
               closeModal();
                location.reload(); 
            }
        },
        error: function(xhr){
            console.log("RAW RESPONSE:", xhr.responseText);
            Swal.fire("Error", "Terjadi error saat menyimpan", "error");
        }
    });
}
</script>

</body>
</html>
