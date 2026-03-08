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


?>


<style>

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
font-size:12px;
}

.calendar-total{
position:absolute;
bottom:6px;
right:6px;
font-weight:bold;
}

table{
page-break-inside:avoid;
}

@media print{
.filter-box{display:none;}
}

</style>


<div class="menuBox">
<div class="menuBoxInner reportIcon">
<div class="container">

<h2>Laporan Statistik Perpustakaan</h2>


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

</div>



<?php

function renderCalendar($data,$start,$end){

$current=clone $start;

while($current <= $end){

$bulan_awal=new DateTime($current->format('Y-m-01'));
$bulan_akhir=new DateTime($current->format('Y-m-t'));

if($bulan_awal < $start) $bulan_awal=clone $start;
if($bulan_akhir > $end) $bulan_akhir=clone $end;

?>

<h4 style="margin-top:30px;border-top:3px solid #ddd;padding-top:10px">
<?= date('F Y',strtotime($bulan_awal->format('Y-m-d'))) ?>
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
</div>