<?php
session_start();

$penilai_id = $_SESSION['id'] ?? null;
if (!$penilai_id) die("Sesi tidak ditemukan");

// ====================== TEMPAT PKL ======================
$stmt = $koneksi->prepare("
    SELECT tp.pkl_id, tp.pkl_nama
    FROM data_du du
    LEFT JOIN tempat_pkl tp ON du.pkl_id = tp.pkl_id
    WHERE du.du_pembimbing = ?
    GROUP BY tp.pkl_id
");
$stmt->bind_param("i", $penilai_id);
$stmt->execute();
$res = $stmt->get_result();
$tempat_pkl_list = $res->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Catatan Kegiatan PKL</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body{font-family:'Segoe UI',Arial;margin:0;padding:20px;background:#f5f6f8;}
h2{text-align:center;text-transform:uppercase;margin-bottom:20px;}
.card{background:#fff;padding:15px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,0.1);margin-bottom:20px;}
.hidden{display:none;}
canvas{border:1px solid #ccc;width:100%;border-radius:6px;background:#fafafa;}
button{padding:8px 14px;border:none;border-radius:5px;cursor:pointer;}
.btn-save{background:#007bff;color:#fff;}
.btn-cancel{background:gray;color:white;}
</style>
</head>
<body>

<h2>Daftar Catatan Kegiatan PKL</h2>

<div class="card">
  <h3>Tempat PKL</h3>
  <table id="tblTempat" class="display" width="100%">
    <thead><tr><th>Nama Tempat</th></tr></thead>
    <tbody>
      <?php foreach($tempat_pkl_list as $t): ?>
        <tr data-id="<?= $t['pkl_id'] ?>"><td><?= htmlspecialchars($t['pkl_nama']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card hidden" id="siswaCard">
  <h3>Daftar Siswa</h3>
  <table id="tblSiswa" class="display" width="100%">
    <thead><tr><th>Nama Siswa</th></tr></thead>
    <tbody></tbody>
  </table>
</div>

<div class="card hidden" id="catatanCard">
  <h3>Catatan Kegiatan</h3>
  <table id="tblCatatan" class="display" width="100%">
    <thead>
      <tr>
        <th>Tanggal</th>
        <th>Perencanaan</th>
        <th>Pelaksanaan</th>
        <th>Catatan Penilaian</th>
        <th>Paraf</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Penilaian -->
<div class="modal hidden" id="modalEdit">
  <div class="card" style="max-width:600px;margin:auto;">
    <h3>Edit Catatan</h3>
    <form id="updateForm">
      <input type="hidden" name="id" id="id">
      <label>Catatan Penilaian</label>
      <textarea name="catatan_instruktur" id="catatan_instruktur" rows="4" style="width:100%;"></textarea>
      <label>Tanda Tangan Pembimbing</label>
      <canvas id="signaturePad"></canvas>
      <button type="button" onclick="clearCanvas()">Hapus TTD</button><br>
      <button type="button" class="btn-save" onclick="saveData()">Simpan</button>
      <button type="button" class="btn-cancel" onclick="$('#modalEdit').addClass('hidden')">Batal</button>
    </form>
  </div>
</div>

<script>
let tTempat = $('#tblTempat').DataTable();
let tSiswa = $('#tblSiswa').DataTable();
let tCatatan = $('#tblCatatan').DataTable();
let ctx;

// ======================= EVENT PILIH TEMPAT =======================
$('#tblTempat tbody').on('click','tr',function(){
    let pkl_id = $(this).data('id');
    $.post('ajax_get_siswa.php',{pkl_id},function(res){
        if(res.status==='success'){
            $('#siswaCard').removeClass('hidden');
            tSiswa.clear().rows.add(res.data.map(r=>[r.datawal_nama])).draw();
            $('#tblSiswa tbody tr').each(function(i){ $(this).data('id',res.data[i].datawal_id_siswa); });
        }else Swal.fire('Info',res.message,'info');
    },'json');
});

// ======================= EVENT PILIH SISWA =======================
$('#tblSiswa tbody').on('click','tr',function(){
    let siswa_id = $(this).data('id');
    $.post('ajax_get_catatan.php',{siswa_id},function(res){
        if(res.status==='success'){
            $('#catatanCard').removeClass('hidden');
            tCatatan.clear();
            res.data.forEach(r=>{
                let img = r.paraf_pembimbing ? `<img src='${r.paraf_pembimbing}' height='40'>`:'(Kosong)';
                tCatatan.row.add([r.tanggal,r.perencanaan,r.pelaksanaan,r.catatan_instruktur,img]);
            });
            tCatatan.draw();
        }else Swal.fire('Gagal',res.message,'error');
    },'json');
});

// === Signature pad ===
function initCanvas(){
  let canvas=document.getElementById("signaturePad");
  ctx=canvas.getContext("2d"); ctx.lineWidth=2; ctx.strokeStyle="#000";
  let draw=false;
  canvas.onmousedown=e=>{draw=true;ctx.beginPath();ctx.moveTo(e.offsetX,e.offsetY);};
  canvas.onmousemove=e=>{if(draw){ctx.lineTo(e.offsetX,e.offsetY);ctx.stroke();}};
  canvas.onmouseup=()=>draw=false;
  canvas.onmouseleave=()=>draw=false;
}
initCanvas();
function clearCanvas(){ctx.clearRect(0,0,signaturePad.width,signaturePad.height);}

function saveData(){
  let fd=new FormData($('#updateForm')[0]);
  fd.append('action','update');
  fd.append('paraf_pembimbing',signaturePad.toDataURL());
  $.ajax({
    url:'ajax_update_catatan.php',
    type:'POST',
    data:fd,
    processData:false,
    contentType:false,
    success:function(r){
      Swal.fire('Berhasil','Data disimpan','success');
      $('#modalEdit').addClass('hidden');
    }
  });
}
</script>
</body>
</html>
