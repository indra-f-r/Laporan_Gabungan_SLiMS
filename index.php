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
TOTAL PEMINJAMAN
====================== */

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
height:80px;
position:relative;
}

.calendar-day{
position:absolute;
top:4px;
left:6px;
font-size:16px;
}

.calendar-total{
position:absolute;
bottom:6px;
right:6px;
font-weight:bold;
font-size:20px;
}

table{
page-break-inside:avoid;
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

</style>


<div class="menuBox">
<div class="menuBoxInner reportIcon">
<div class="container">

<div class="filter-box">

<form method="get" action="<?= $_SERVER['PHP_SELF'] ?>">

<input type="hidden" name="mod" value="<?= $_GET['mod'] ?? '' ?>">
<input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">

<label>Tanggal Mulai</label>
<input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>">

<label>Tanggal Akhir</label>
<input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">

<button class="s-btn btn btn-primary">Tampilkan</button>
<button type="button" onclick="window.print()" class="s-btn btn btn-default">Print</button>

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



<div class="section">

<h3>Laporan Peminjaman</h3>

<p>

Total Peminjaman : <b><?= $total_now ?></b><br>
Periode Sebelumnya : <b><?= $total_prev ?></b><br>
Perubahan : <b><?= $diff ?> (<?= $percent ?>%)</b>

</p>

<h3>Distribusi Peminjaman</h3>

<div class="chart-row">

<div class="chart-row">

<div class="chart-box">
<h4>Klasifikasi DDC</h4>
<canvas id="chartKlas"></canvas>
</div>

<div class="chart-box">
<h4>Tipe Koleksi</h4>
<canvas id="chartType"></canvas>
</div>

<div class="chart-box">
<h4>Grup Peminjam</h4>
<canvas id="chartGroup"></canvas>
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

if($bulan_awal < $start) $bulan_awal=clone $start;
if($bulan_akhir > $end) $bulan_akhir=clone $end;

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

</script>
</div>
