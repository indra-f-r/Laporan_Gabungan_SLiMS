<?php

if (!defined('INDEX_AUTH')) {
define('INDEX_AUTH', '1');
}

global $dbs,$sysconf;

require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';

$can_read = utility::havePrivilege('reporting','r');
if(!$can_read){
die('<div class="errorBox">Tidak memiliki hak akses</div>');
}

date_default_timezone_set('Asia/Jakarta');


/* ======================
FILTER TANGGAL
====================== */

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

$tahun_laporan = date('Y', strtotime($tanggal_mulai));

$bulan_mulai = date('n', strtotime($tanggal_mulai));
$bulan_akhir = date('n', strtotime($tanggal_akhir));

$selisih_bulan = $bulan_akhir - $bulan_mulai;

if($bulan_mulai == $bulan_akhir){

    $nama_periode = "Bulan ".bulan_local($tanggal_mulai);

}
elseif($selisih_bulan <= 2){

    $nama_periode = "Triwulan ".ceil($bulan_mulai/3)." ".$tahun_laporan;

}
elseif($selisih_bulan <= 5){

    $nama_periode = "Semester ".ceil($bulan_mulai/6)." ".$tahun_laporan;

}
else{

    $nama_periode = "Tahun ".$tahun_laporan;

}

/* ======================
PERIODE SEBELUMNYA
====================== */

$start = new DateTime($tanggal_mulai);
$end   = new DateTime($tanggal_akhir);

$interval = $start->diff($end)->days;

$prev_end = clone $start;
$prev_end->modify('-1 day');

$prev_start = clone $prev_end;
$prev_start->modify("-$interval days");

$prev_start_date = $prev_start->format('Y-m-d');
$prev_end_date   = $prev_end->format('Y-m-d');


/* ======================
TOTAL KUNJUNGAN
====================== */

$sql_visit="
SELECT COUNT(*) total
FROM visitor_count
WHERE checkin_date BETWEEN ? AND ?
";

$stmtV1=$dbs->prepare($sql_visit);
$stmtV1->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtV1->execute();
$resV1=$stmtV1->get_result();
$visit_now=$resV1->fetch_assoc()['total'];

$stmtV2=$dbs->prepare($sql_visit);
$stmtV2->bind_param("ss",$prev_start_date,$prev_end_date);
$stmtV2->execute();
$resV2=$stmtV2->get_result();
$visit_prev=$resV2->fetch_assoc()['total'];

$visit_diff=$visit_now-$visit_prev;
$visit_percent=$visit_prev>0 ? round(($visit_diff/$visit_prev)*100,2):100;



$sql_total="
SELECT COUNT(*) total
FROM loan_history
WHERE loan_date BETWEEN ? AND ?
";

$stmt=$dbs->prepare($sql_total);
$stmt->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmt->execute();
$res=$stmt->get_result();
$total_now=$res->fetch_assoc()['total'];

$stmt2=$dbs->prepare($sql_total);
$stmt2->bind_param("ss",$prev_start_date,$prev_end_date);
$stmt2->execute();
$res2=$stmt2->get_result();
$total_prev=$res2->fetch_assoc()['total'];

$diff=$total_now-$total_prev;
$percent=$total_prev>0 ? round(($diff/$total_prev)*100,2):100;

if($diff > 0){
$analisa_pinjam="Terjadi peningkatan peminjaman sebesar ".abs($percent)."% dibanding periode sebelumnya.";
}
elseif($diff < 0){
$analisa_pinjam="Terjadi penurunan peminjaman sebesar ".abs($percent)."% dibanding periode sebelumnya.";
}
else{
$analisa_pinjam="Jumlah peminjaman relatif stabil dibanding periode sebelumnya.";
}

if($visit_diff > 0){
$analisa_visit="Terjadi peningkatan kunjungan sebesar ".abs($visit_percent)."% dibanding periode sebelumnya.";
}
elseif($visit_diff < 0){
$analisa_visit="Terjadi penurunan kunjungan sebesar ".abs($visit_percent)."% dibanding periode sebelumnya.";
}
else{
$analisa_visit="Jumlah kunjungan relatif stabil dibanding periode sebelumnya.";
}

/* ======================
PEMINJAMAN PER HARI
====================== */

$q_pinjam="
SELECT DATE(loan_date) tgl,COUNT(*) total
FROM loan_history
WHERE DATE(loan_date) BETWEEN ? AND ?
GROUP BY DATE(loan_date)
";

$stmt3=$dbs->prepare($q_pinjam);
$stmt3->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmt3->execute();

$res_pinjam=$stmt3->get_result();

$data_pinjam=[];

while($r=$res_pinjam->fetch_assoc()){
$data_pinjam[$r['tgl']]=$r['total'];
}


/* ======================
KUNJUNGAN PER HARI
====================== */

$q_visit="
SELECT DATE(checkin_date) tgl,COUNT(*) total
FROM visitor_count
WHERE DATE(checkin_date) BETWEEN ? AND ?
GROUP BY DATE(checkin_date)
";

$stmt4=$dbs->prepare($q_visit);
$stmt4->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmt4->execute();

$res_visit=$stmt4->get_result();

$data_visit=[];

while($r=$res_visit->fetch_assoc()){
$data_visit[$r['tgl']]=$r['total'];
}

/* ======================
CHART KUNJUNGAN
====================== */

$q_chart_visit="
SELECT DATE(checkin_date) tgl,COUNT(*) total
FROM visitor_count
WHERE checkin_date BETWEEN ? AND ?
GROUP BY DATE(checkin_date)
ORDER BY tgl
";

$stmtV=$dbs->prepare($q_chart_visit);
$stmtV->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtV->execute();
$resV=$stmtV->get_result();

$label_visit=[];
$data_visit_chart=[];

while($r=$resV->fetch_assoc()){
$label_visit[]=$r['tgl'];
$data_visit_chart[]=(int)$r['total'];
}

/* ======================
CHART DISTRIBUSI PENGUNJUNG (PIN)
====================== */

$q_chart_visit_group = "
SELECT m.pin, COUNT(*) total
FROM visitor_count v
JOIN member m ON v.member_id = m.member_id
WHERE v.checkin_date BETWEEN ? AND ?
GROUP BY m.pin
ORDER BY total DESC
";

$stmtVG = $dbs->prepare($q_chart_visit_group);
$stmtVG->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtVG->execute();
$resVG = $stmtVG->get_result();

$label_visit_group = [];
$data_visit_group  = [];

while ($r = $resVG->fetch_assoc()) {
    $label_visit_group[] = $r['pin'] ?: 'Tanpa PIN';
    $data_visit_group[]  = (int)$r['total'];
}

/* ======================
DASHBOARD STATISTIK
====================== */

$q_member="SELECT COUNT(*) total FROM member";
$resM=$dbs->query($q_member);
$total_member=$resM->fetch_assoc()['total'];

$q_biblio="SELECT COUNT(*) total FROM biblio";
$resB=$dbs->query($q_biblio);
$total_biblio=$resB->fetch_assoc()['total'];

$q_item="SELECT COUNT(*) total FROM item";
$resI=$dbs->query($q_item);
$total_item=$resI->fetch_assoc()['total'];

$q_item_lent="SELECT COUNT(*) total FROM loan WHERE is_return=0";
$resL=$dbs->query($q_item_lent);
$total_item_lent=$resL->fetch_assoc()['total'];

$q_visit_total="
SELECT COUNT(*) total
FROM visitor_count
WHERE DATE(checkin_date) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'
";

$resV=$dbs->query($q_visit_total);
$total_visit=$resV->fetch_assoc()['total'];

/* ======================
STATISTIK ANGGOTA AKTIF
====================== */

$q_active_member="
SELECT m.member_name,m.member_id,COUNT(l.loan_id) total
FROM loan l
INNER JOIN member m ON m.member_id=l.member_id
WHERE m.expire_date > CURDATE()
AND DATE(l.loan_date) BETWEEN ? AND ?
GROUP BY m.member_id
ORDER BY total DESC
LIMIT 10
";

$stmtA=$dbs->prepare($q_active_member);
$stmtA->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtA->execute();
$active_member=$stmtA->get_result();


$q_member_type="
SELECT mt.member_type_name,m.member_name,COUNT(l.loan_id) total
FROM loan l
INNER JOIN member m ON m.member_id=l.member_id
INNER JOIN mst_member_type mt ON mt.member_type_id=m.member_type_id
WHERE m.expire_date > CURDATE()
AND DATE(l.loan_date) BETWEEN ? AND ?
GROUP BY m.member_id
ORDER BY total DESC
LIMIT 10
";

$stmtB=$dbs->prepare($q_member_type);
$stmtB->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtB->execute();
$member_type=$stmtB->get_result();


$q_active_visit="
SELECT m.member_name,m.member_id,COUNT(v.visitor_id) total
FROM visitor_count v
INNER JOIN member m ON m.member_id=v.member_id
WHERE m.expire_date > CURDATE()
AND DATE(v.checkin_date) BETWEEN ? AND ?
GROUP BY m.member_id
ORDER BY total DESC
LIMIT 10
";

$stmtC=$dbs->prepare($q_active_visit);
$stmtC->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtC->execute();
$active_visit=$stmtC->get_result();


/* ======================
TOP BUKU
====================== */

$q_buku="
SELECT title,classification,COUNT(*) total
FROM loan_history
WHERE loan_date BETWEEN ? AND ?
GROUP BY title,classification
ORDER BY total DESC
LIMIT 10
";

$stmt6=$dbs->prepare($q_buku);
$stmt6->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmt6->execute();
$buku_terlaris=$stmt6->get_result();

/* ======================
PUSTAKA DIBACA
====================== */

$q_read="
SELECT title,COUNT(*) total
FROM read_counter
WHERE created_at BETWEEN ? AND ?
GROUP BY title
ORDER BY total DESC
LIMIT 10
";

$stmtR=$dbs->prepare($q_read);
$stmtR->bind_param("ss",$tanggal_mulai,$tanggal_akhir);
$stmtR->execute();
$top_read=$stmtR->get_result();

/* ======================
CHART PEMINJAMAN
====================== */

/* Chart 1 : Klasifikasi (DDC 000–900) */
$q_chart_klas = "
SELECT FLOOR(classification/100)*100 AS klas, COUNT(*) total
FROM loan_history
WHERE loan_date BETWEEN ? AND ?
GROUP BY klas
ORDER BY klas
";

$stmtK = $dbs->prepare($q_chart_klas);
$stmtK->bind_param("ss", $tanggal_mulai, $tanggal_akhir);
$stmtK->execute();
$resK = $stmtK->get_result();

$label_klas = [];
$data_klas  = [];

while ($r = $resK->fetch_assoc()) {
    $label_klas[] = str_pad($r['klas'], 3, "0", STR_PAD_LEFT);
    $data_klas[]  = (int)$r['total'];
}


/* Chart 2 : Tipe Koleksi */
$q_chart_type = "
SELECT ct.coll_type_name, COUNT(*) total
FROM loan_history lh
JOIN item i ON lh.item_code = i.item_code
JOIN mst_coll_type ct ON i.coll_type_id = ct.coll_type_id
WHERE lh.loan_date BETWEEN ? AND ?
GROUP BY ct.coll_type_name
ORDER BY total DESC
LIMIT 10
";

$stmtT = $dbs->prepare($q_chart_type);
$stmtT->bind_param("ss", $tanggal_mulai, $tanggal_akhir);
$stmtT->execute();
$resT = $stmtT->get_result();

$label_type = [];
$data_type  = [];

while ($r = $resT->fetch_assoc()) {
    $label_type[] = $r['coll_type_name'];
    $data_type[]  = (int)$r['total'];
}


/* Chart 3 : Grup Peminjam (field PIN) */
$q_chart_group = "
SELECT m.pin, COUNT(*) total
FROM loan_history lh
JOIN member m ON lh.member_id = m.member_id
WHERE lh.loan_date BETWEEN ? AND ?
GROUP BY m.pin
ORDER BY total DESC
LIMIT 10
";

$stmtG = $dbs->prepare($q_chart_group);
$stmtG->bind_param("ss", $tanggal_mulai, $tanggal_akhir);
$stmtG->execute();
$resG = $stmtG->get_result();

$label_group = [];
$data_group  = [];

while ($r = $resG->fetch_assoc()) {
    $label_group[] = $r['pin'] ?: 'Tanpa PIN';
    $data_group[]  = (int)$r['total'];
}

?>


<style>


.report-title{
text-align:center;
margin-bottom:25px;
}

.report-title h2{
margin-bottom:5px;
}

.report-title h3{
margin-top:10px;
}

.chart-row{
display:flex;
gap:30px;
flex-wrap:wrap;
margin-bottom:30px;
}

.chart-box{
width:320px;
}

/* khusus saat print */

@media print{

.chart-row{
display:flex;
flex-wrap:nowrap;
justify-content:space-between;
}

.chart-box{
width:30%;
}

canvas{
max-width:100% !important;
height:auto !important;
}

}

.dashboard{
display:flex;
gap:20px;
flex-wrap:wrap;
margin-bottom:30px;
justify-content:center;
}

.dashboard-card{
background:#f8fafc;
border:1px solid #e5e7eb;
border-radius:8px;
padding:15px 25px;
min-width:180px;
text-align:center;
margin:5px;
}

.dashboard-title{
font-size:13px;
color:#666;
}

.dashboard-value{
font-size:28px;
font-weight:bold;
color:#1565c0;
}

.container{
background:white;
padding:20px;
}

.section{
margin-top:40px;
page-break-inside:avoid;
}

.calendar-cell{
height:55px;
position:relative;
}

.calendar-day{
position:absolute;
top:3px;
left:5px;
font-size:13px;
}

.calendar-total{
position:absolute;
bottom:4px;
right:5px;
font-weight:bold;
font-size:16px;
}

table{
page-break-inside:avoid;
}

table{
font-size:12px;
}

.chart-top{
margin-top:10px;
font-size:13px;
}

@media print{

.filter-box{display:none;}

.dashboard{
justify-content:center;
}

}

@media print{

.report-title{
text-align:center;
margin-bottom:20px;
}

}

@media print{

.section-pinjam{
page-break-inside:auto;
margin-top:10px;
}

}

@media print{

.chart-row{
margin-top:10px;
margin-bottom:10px;
}

}

@media print{

.chart-row{
page-break-inside:auto;
break-inside:auto;
}

.chart-box{
break-inside:avoid;
}

}

.popup-overlay{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.4);
display:none;
align-items:center;
justify-content:center;
z-index:9999;
}

.popup-box{
background:white;
padding:25px;
width:400px;
border-radius:6px;
}

.popup-box input{
width:100%;
margin-bottom:10px;
padding:6px;
border:1px solid #ccc;
border-radius:4px;
}

.area-ttd{
height:110px;
margin:5px 0;
display:flex;
align-items:flex-end;
justify-content:center;
}

.img-ttd{
max-height:110px;
max-width:260px;
object-fit:contain;
}

#lembar-pengesahan{
page-break-after:always;
}

#lembar-pengesahan{
display:none;
}

@media print{

#lembar-pengesahan{
display:block;
page-break-after:always;
}

}

@media print{

.section table{
page-break-inside:avoid;
break-inside:avoid;
}

.section h4{
page-break-after:avoid;
}

.section{
page-break-inside:avoid;
}

}
</style>


<div class="menuBox">
<div class="menuBoxInner reportIcon">
<div class="container">

<!-- LEMBAR PENGESAHAN -->

<div id="lembar-pengesahan" style="page-break-before:always;display:none">

<h2 style="text-align:center;margin-top:80px">

<?= strtoupper($sysconf['library_name']) ?>

</h2>

<div style="text-align:center;font-size:16px;margin-top:-5px">

<?= $sysconf['library_subname'] ?>

</div>

<h3 style="text-align:center;margin-top:25px">

LEMBAR PENGESAHAN

</h3>

<div style="text-align:center;font-size:17px;margin-top:15px">

Laporan Statistik Perpustakaan

</div>

<div style="text-align:center;font-size:16px;margin-top:8px">

<?= $nama_periode ?>

</div>

<div style="display:flex;justify-content:space-between">

<div style="width:40%;text-align:center">

Mengetahui,<br>
<span id="kepala_jabatan_text"></span>

<div class="area-ttd">
<img id="tte_kepala" class="img-ttd">
</div>

<div id="kepala_identitas">
<b id="kepala_nama_text"></b><br>
<span id="kepala_nip_text"></span>
</div>

</div>

<div style="width:40%;text-align:center">

<span id="kota_text"></span>, <?= date('d') ?> <?= bulan_local(date('Y-m-d')) ?><br>
<span id="petugas_jabatan_text"></span>

<div class="area-ttd">
<img id="tte_petugas" class="img-ttd">
</div>

<div id="petugas_identitas">
<b id="petugas_nama_text"></b><br>
<span id="petugas_nip_text"></span>
</div>

</div>

</div>

</div>

<div class="filter-box">

<form method="get" action="<?= $_SERVER['PHP_SELF'] ?>">

<input type="hidden" name="mod" value="<?= $_GET['mod'] ?? '' ?>">
<input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">

<label>Tanggal Mulai</label>
<input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>">

<label>Tanggal Akhir</label>
<input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">

<button class="s-btn btn btn-primary">Tampilkan</button>
<button type="button" onclick="bukaFormPengesahan()" class="s-btn btn btn-default">Print</button>

</form>

</div>


<div class="report-title">

<h2><?= $sysconf['library_name'] ?></h2>

<?php if(!empty($sysconf['library_subname'])){ ?>
<div style="font-size:14px;margin-top:-8px">
<?= $sysconf['library_subname'] ?>
</div>
<?php } ?>

<h3 style="margin-top:15px"><?= __('Library Statistics Report') ?></h3>

<p>
<?= __('Period') ?> :
<b>
<?= date('d',strtotime($tanggal_mulai)) ?> <?= bulan_local($tanggal_mulai) ?>
-
<?= date('d',strtotime($tanggal_akhir)) ?> <?= bulan_local($tanggal_akhir) ?>
</b>
</p>

</div>

<div class="dashboard">

<div class="dashboard-card">
<div class="dashboard-title">Total Peminjaman</div>
<div class="dashboard-value"><?= number_format($total_now) ?></div>
</div>

<div class="dashboard-card">
<div class="dashboard-title">Total Kunjungan</div>
<div class="dashboard-value"><?= number_format($total_visit) ?></div>
</div>

<div class="dashboard-card">
<div class="dashboard-title">Total Anggota</div>
<div class="dashboard-value"><?= number_format($total_member) ?></div>
</div>

<div class="dashboard-card">
<div class="dashboard-title">Total Judul</div>
<div class="dashboard-value"><?= number_format($total_biblio) ?></div>
</div>

<div class="dashboard-card">
<div class="dashboard-title">Total Koleksi</div>
<div class="dashboard-value"><?= number_format($total_item) ?></div>
</div>

<div class="dashboard-card">
<div class="dashboard-title">Eksemplar Dipinjam</div>
<div class="dashboard-value"><?= number_format($total_item_lent) ?></div>
</div>

</div>

<div class="section">

<h3>Statistik Anggota Aktif</h3>

<div style="display:flex;gap:40px;flex-wrap:wrap">

<div>

<h4>10 Peminjam terbanyak</h4>

<ol>

<?php while($r=$active_member->fetch_assoc()){ ?>

<li><?= $r['member_name'] ?> (<?= $r['member_id'] ?>) – <?= $r['total'] ?> pinjaman</li>

<?php } ?>

</ol>

</div>


<div>

<h4>10 Pengunjung Terbanyak</h4>

<ol>

<?php while($r=$active_visit->fetch_assoc()){ ?>

<li><?= $r['member_name'] ?> – <?= $r['total'] ?> kunjungan</li>

<?php } ?>

</ol>

</div>

</div>

</div>



<div class="section section-pinjam">

<h3>Laporan Peminjaman</h3>

<p>

Total Peminjaman : <b><?= $total_now ?></b><br>
Periode Sebelumnya : <b><?= $total_prev ?></b><br>
Perubahan : <b><?= $diff ?> (<?= $percent ?>%)</b>

</p>

<p><i><?= $analisa_pinjam ?></i></p>

<h3>Distribusi Peminjaman</h3>

<div class="chart-row">

<div class="chart-box">

<h4>Klasifikasi DDC</h4>

<canvas id="chartKlas"></canvas>

<div class="chart-top">
<?php

$items=[];
foreach($label_klas as $i=>$label){
$items[]=['label'=>$label,'total'=>$data_klas[$i]];
}

usort($items,function($a,$b){
return $b['total'] <=> $a['total'];
});

$top=array_slice($items,0,3);

?>

<ol>
<?php foreach($top as $r){ ?>
<li><?= $r['label'] ?> — <?= $r['total'] ?></li>
<?php } ?>
</ol>

</div>

</div>


<div class="chart-box">

<h4>Tipe Koleksi</h4>

<canvas id="chartType"></canvas>

<div class="chart-top">
<?php

$items=[];
foreach($label_type as $i=>$label){
$items[]=['label'=>$label,'total'=>$data_type[$i]];
}

usort($items,function($a,$b){
return $b['total'] <=> $a['total'];
});

$top=array_slice($items,0,3);

?>

<ol>
<?php foreach($top as $r){ ?>
<li><?= $r['label'] ?> — <?= $r['total'] ?></li>
<?php } ?>
</ol>

</div>

</div>

<div class="chart-box">

<h4>Grup Peminjam</h4>

<canvas id="chartGroup"></canvas>

<div class="chart-top">
<?php

$items=[];
foreach($label_group as $i=>$label){
$items[]=['label'=>$label,'total'=>$data_group[$i]];
}

usort($items,function($a,$b){
return $b['total'] <=> $a['total'];
});

$top=array_slice($items,0,3);

?>

<ol>
<?php foreach($top as $r){ ?>
<li><?= $r['label'] ?> — <?= $r['total'] ?></li>
<?php } ?>
</ol>

</div>

</div>

</div>
</div>

<?php

function bulan_local($date){

$bulan_id=[
1=>'Januari',
2=>'Februari',
3=>'Maret',
4=>'April',
5=>'Mei',
6=>'Juni',
7=>'Juli',
8=>'Agustus',
9=>'September',
10=>'Oktober',
11=>'November',
12=>'Desember'
];

$bulan_en=[
1=>'January',
2=>'February',
3=>'March',
4=>'April',
5=>'May',
6=>'June',
7=>'July',
8=>'August',
9=>'September',
10=>'October',
11=>'November',
12=>'December'
];

$d=new DateTime($date);
$bulan=(int)$d->format('n');

global $sysconf;

/* cek bahasa sistem */
$lang = $sysconf['default_lang'] ?? 'id_ID';

/* pilih bahasa */
if(strpos($lang,'id')!==false){
$nama_bulan=$bulan_id[$bulan];
}else{
$nama_bulan=$bulan_en[$bulan];
}

return $nama_bulan.' '.$d->format('Y');

}

function renderCalendar($data,$start,$end){

$current=clone $start;

while($current <= $end){

$bulan_awal=new DateTime($current->format('Y-m-01'));
$bulan_akhir=new DateTime($current->format('Y-m-t'));

?>

<h4 style="margin-top:30px;border-top:3px solid #ddd;padding-top:10px">
<?= bulan_local($bulan_awal->format('Y-m-d')) ?>
</h4>

<table class="s-table table table-bordered">

<tr>
<th>Minggu</th>
<th>Senin</th>
<th>Selasa</th>
<th>Rabu</th>
<th>Kamis</th>
<th>Jumat</th>
<th>Sabtu</th>
</tr>

<?php

$day=strtotime($bulan_awal->format('Y-m-d'));
$last=strtotime($bulan_akhir->format('Y-m-d'));

echo "<tr>";

$first_day=date('w',$day);

for($i=0;$i<$first_day;$i++) echo "<td></td>";

while($day <= $last){

$tgl=date('Y-m-d',$day);
$hari=date('j',$day);

$total=$data[$tgl] ?? '';

echo "<td class='calendar-cell'>";

echo "<div class='calendar-day'>$hari</div>";

if($total) echo "<div class='calendar-total'>$total</div>";

echo "</td>";

if(date('w',$day)==6) echo "</tr><tr>";

$day=strtotime("+1 day",$day);

}

echo "</tr>";

?>

</table>

<?php

$current->modify('first day of next month');

}

}

?>


<div class="section">

<h3>Kalender Peminjaman</h3>

<?php renderCalendar($data_pinjam,$start,$end); ?>

</div>



<div class="section">

<div class="section">

<h3 style="margin-top:40px">Laporan Kunjungan</h3>

<p>

Total Kunjungan : <b><?= $visit_now ?></b><br>
Periode Sebelumnya : <b><?= $visit_prev ?></b><br>
Perubahan : <b><?= $visit_diff ?> (<?= $visit_percent ?>%)</b>

</p>

<p><i><?= $analisa_visit ?></i></p>

<h3>Distribusi Pengunjung</h3>

<div style="width:420px;margin:auto">

<canvas id="chartVisitGroup"></canvas>

</div>

</div>

<h3>Kalender Kunjungan</h3>

<?php renderCalendar($data_visit,$start,$end); ?>

</div>



<div class="section">

<h3>Top 10 Buku Terlaris</h3>

<table class="s-table table table-bordered">

<tr>
<th>No</th>
<th>Judul</th>
<th>Klasifikasi</th>
<th>Total</th>
</tr>

<?php $n=1; while($r=$buku_terlaris->fetch_assoc()){ ?>

<tr>
<td><?= $n++ ?></td>
<td><?= $r['title'] ?></td>
<td><?= $r['classification'] ?></td>
<td><?= $r['total'] ?></td>
</tr>

<?php } ?>

</table>

<div class="section">

<h3>Pustaka Dibaca di Tempat</h3>

<table class="s-table table table-bordered">

<tr>
<th>No</th>
<th>Judul</th>
<th>Total Dibaca</th>
</tr>

<?php $n=1; while($r=$top_read->fetch_assoc()){ ?>

<tr>
<td><?= $n++ ?></td>
<td><?= $r['title'] ?></td>
<td><?= $r['total'] ?></td>
</tr>

<?php } ?>

</table>

</div>
</div>


</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

/* Warna konstan untuk kelas DDC */
const ddcColors = {
"000":"#3366CC",
"100":"#990099",
"200":"#DC3912",
"300":"#FF9900",
"400":"#F4C20D",
"500":"#109618",
"600":"#0099C6",
"700":"#DD4477",
"800":"#8B4513",
"900":"#7f7f7f"
};

/* Fungsi membuat chart */
function makeChart(id,labels,data,colors){

new Chart(document.getElementById(id),{

type:'doughnut',

data:{
labels:labels,
datasets:[{
data:data,
backgroundColor:colors
}]
},

options:{
plugins:{
legend:{ position:'bottom' }
},
responsive:true
}

});

}


/* Chart Klasifikasi */
const klasLabels = <?= json_encode($label_klas) ?>;
const klasData   = <?= json_encode($data_klas) ?>;

const klasColors = klasLabels.map(k => ddcColors[k] || "#cccccc");

makeChart(
'chartKlas',
klasLabels,
klasData,
klasColors
);


/* Chart Tipe Koleksi */
makeChart(
'chartType',
<?= json_encode($label_type) ?>,
<?= json_encode($data_type) ?>,
['#3366CC','#DC3912','#FF9900','#109618','#990099','#0099C6','#DD4477','#66AA00','#B82E2E','#316395']
);


/* Chart Grup Peminjam */
makeChart(
'chartGroup',
<?= json_encode($label_group) ?>,
<?= json_encode($data_group) ?>,
['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#84CC16']
);


new Chart(document.getElementById('chartVisitGroup'),{

type:'doughnut',

data:{
labels: <?= json_encode($label_visit_group) ?>,
datasets:[{
data: <?= json_encode($data_visit_group) ?>,
backgroundColor:[
'#6366F1','#F59E0B','#10B981','#EF4444','#3B82F6',
'#8B5CF6','#14B8A6','#F97316','#84CC16','#EC4899'
]
}]
},

options:{
responsive:true,
plugins:{
legend:{
position:'bottom',
labels:{
filter:function(legendItem,data){

return legendItem.index < 5;

}
}
}
}
}

});

</script>

<div id="popup-pengesahan" class="popup-overlay">

<div class="popup-box">

<h3>Lembar Pengesahan</h3>

<label>Kota</label>
<input type="text" id="kota" value="">

<label>Nama Petugas</label>
<input type="text" id="petugas_nama">

<label>Jabatan Petugas</label>
<input type="text" id="petugas_jabatan">

<label>NIP Petugas</label>
<input type="text" id="petugas_nip">

<hr>

<label>Nama Pimpinan</label>
<input type="text" id="kepala_nama">

<label>Jabatan Pimpinan</label>
<input type="text" id="kepala_jabatan">

<label>NIP Pimpinan</label>
<input type="text" id="kepala_nip">

<hr>

<label>
<input type="checkbox" id="mode_tte">
Gunakan TTe
</label>

<div style="margin-top:15px;text-align:right">

<button onclick="prosesCetak()" class="btn btn-primary">
Cetak
</button>

<button onclick="tutupFormPengesahan()" class="btn btn-default">
Batal
</button>

</div>

</div>
</div>
</div>

<script>

/* ===== FORM PENGESAHAN ===== */

function bukaFormPengesahan(){
document.getElementById("popup-pengesahan").style.display="flex";
}

function tutupFormPengesahan(){
document.getElementById("popup-pengesahan").style.display="none";
}

function prosesCetak(){

let tte = document.getElementById("mode_tte").checked;

let kota = document.getElementById("kota").value;

let pn = document.getElementById("petugas_nama").value;
let pj = document.getElementById("petugas_jabatan").value;
let pp = document.getElementById("petugas_nip").value;

let kn = document.getElementById("kepala_nama").value;
let kj = document.getElementById("kepala_jabatan").value;
let kp = document.getElementById("kepala_nip").value;

document.getElementById("kota_text").innerText = kota;

document.getElementById("petugas_jabatan_text").innerText = pj;
document.getElementById("kepala_jabatan_text").innerText = kj;

if(tte){

document.getElementById("petugas_identitas").style.display="none";
document.getElementById("kepala_identitas").style.display="none";

document.getElementById("tte_kepala").src="<?php echo SWB; ?>images/kepala.png";
document.getElementById("tte_petugas").src="<?php echo SWB; ?>images/petugas.png";

}else{

document.getElementById("petugas_nama_text").innerText = pn;
document.getElementById("petugas_nip_text").innerText = "NIP "+pp;

document.getElementById("kepala_nama_text").innerText = kn;
document.getElementById("kepala_nip_text").innerText = "NIP "+kp;

}

document.getElementById("lembar-pengesahan").style.display="block";

tutupFormPengesahan();

setTimeout(function(){
window.print();
},300);

}

window.onafterprint = function(){

document.getElementById("lembar-pengesahan").style.display="none";

}

</script>

