# 📊 Laporan Gabungan Perpustakaan (SLiMS Plugin)

Plugin ini merupakan modul tambahan untuk SLiMS (Senayan Library Management System) yang digunakan untuk menampilkan laporan gabungan perpustakaan dalam bentuk dashboard statistik, grafik interaktif, tabel rekap data, serta laporan siap cetak yang dilengkapi dengan lembar pengesahan.

Plugin ini dirancang agar mudah digunakan oleh petugas, termasuk yang tidak memiliki latar belakang teknis, dengan tampilan yang sederhana namun informatif.

---

## 🚀 Fitur Utama

### 📈 Dashboard Statistik
- Menampilkan total pengunjung  
- Menampilkan total transaksi peminjaman  
- Menampilkan jam sibuk pengunjung dan transaksi  
- Kalender kunjungan harian  

### 📚 Analisis Koleksi
- Menampilkan Top 10 buku terlaris  
- Menampilkan data buku yang dibaca di tempat  
- Rekap koleksi berdasarkan:  
  - Klasifikasi (DDC)  
  - Tipe koleksi  
  - GMD (General Material Designation)  

### 📊 Visualisasi Data
- Grafik interaktif menggunakan Chart.js  
- Mendukung bar chart, line chart, dan doughnut chart  
- Pewarnaan klasifikasi konsisten untuk memudahkan analisis  

### 🖨️ Cetak Laporan
- Fitur cetak laporan langsung dari sistem  
- Lembar pengesahan otomatis  
- Dukungan tanda tangan:  
  - Manual  
  - Tanda Tangan Elektronik (TTE)  
- Input data:  
  - Nama petugas  
  - NIP  
  - Jabatan  
  - Nama pimpinan  

---

## ⚙️ Instalasi

1. Download atau clone repository plugin ini  
2. Copy folder plugin ke direktori /plugins/  
3. Letakkan pada: slims/plugins/laporan_gabungan/  
4. Pastikan terdapat file: index.php dan laporan_gabungan.plugin.php  
5. Login ke SLiMS sebagai administrator  
6. Masuk ke menu System → Plugins  
7. Aktifkan plugin "Laporan Gabungan Perpustakaan"  

---

## 🗂️ Struktur Folder

laporan_gabungan/  
├── index.php  
├── laporan_gabungan.plugin.php  
└── (opsional assets tambahan)  

---

## 🧠 Cara Kerja

Plugin ini bekerja dengan mengambil data dari database SLiMS, kemudian mengolahnya menjadi informasi yang lebih mudah dipahami.

Sumber data yang digunakan antara lain:
- Data kunjungan pengunjung  
- Data transaksi peminjaman  
- Data bibliografi dan item koleksi  
- Data klasifikasi (DDC)  
- Data tipe koleksi dan GMD  

Data tersebut kemudian diproses menjadi:
- Statistik ringkasan  
- Rekap berdasarkan kategori  
- Data visual dalam bentuk grafik  

Seluruh hasil ditampilkan dalam satu halaman dashboard yang terintegrasi.

---

## 🖥️ Cara Penggunaan

1. Masuk ke menu Reporting → Laporan Gabungan  
2. Sistem akan menampilkan dashboard, grafik, dan tabel laporan  
3. Untuk mencetak laporan:  
   - Klik tombol cetak  
   - Isi form pengesahan  
   - Pilih jenis tanda tangan (manual atau TTE)  
   - Klik cetak  

---

## 📌 Kebutuhan Sistem

- SLiMS versi 9.x atau lebih baru  
- PHP minimal versi 7.4  
- MySQL atau MariaDB  
- Browser modern (Chrome / Edge)  

---

## ⚠️ Catatan Penting

- Plugin ini tidak mengubah core SLiMS  
- Aman digunakan untuk production  
- Disarankan melakukan backup sebelum instalasi  

---

## 🛠️ Pengembangan Lanjutan (Opsional)

- Export ke PDF  
- Export ke Excel  
- Filter tanggal laporan  
- Integrasi dashboard manajemen  
- Optimasi performa query  

---

## 👨‍💻 Author

Indra F. Rulliawan  
Perpustakaan Wacana Teknologi  
SMKN 1 Majalengka  

---

## 📝 Catatan

Untuk kebutuhan pengelompokan kelas atau grup anggota, sistem secara default mengambil data dari field `pin` pada tabel `member`.

Jika pada sistem Anda data kelas atau grup disimpan pada field lain, silakan lakukan penyesuaian pada file `index.php` dengan cara mencari penggunaan field `pin`, kemudian menggantinya dengan field yang sesuai (misalnya `group_type` atau field lainnya) agar data kelas atau grup dapat ditampilkan dengan benar sesuai struktur database yang digunakan.
