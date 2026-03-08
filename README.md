# Laporan Gabungan

Plugin laporan Gabungan untuk **SLiMS (Senayan Library Management System)** yang menampilkan aktivitas perpustakaan secara visual dan informatif dalam satu halaman laporan.

Plugin ini membantu pustakawan melihat pola aktivitas peminjaman dan kunjungan perpustakaan berdasarkan rentang tanggal tertentu, dilengkapi dengan kalender aktivitas harian serta statistik anggota yang paling aktif.

---

## Fitur

Plugin ini menyediakan beberapa laporan statistik penting.

### Statistik Anggota Aktif
Menampilkan aktivitas anggota yang paling aktif dalam periode tertentu:

- Top 10 anggota paling sering meminjam
- Top pengunjung perpustakaan

### Rekap Peminjaman
- Total peminjaman pada periode yang dipilih
- Perbandingan dengan periode sebelumnya
- Persentase perubahan aktivitas peminjaman

### Kalender Aktivitas Peminjaman
Menampilkan **grid kalender aktivitas peminjaman per hari** yang dipisah berdasarkan bulan sehingga memudahkan analisis pola penggunaan koleksi.

### Kalender Kunjungan Perpustakaan
Menampilkan **grid kalender kunjungan perpustakaan** sehingga pustakawan dapat melihat hari-hari dengan tingkat kunjungan tinggi atau rendah.

### Buku Paling Sering Dipinjam
Menampilkan **Top 10 buku paling sering dipinjam** berdasarkan periode laporan.

---

## Tampilan Laporan

Plugin ini menghasilkan laporan dalam satu halaman yang terdiri dari:

- Statistik Anggota Aktif
- Rekap Peminjaman
- Kalender Peminjaman
- Kalender Kunjungan
- Top Buku Terlaris

Seluruh tabel dan kalender dirancang agar **tidak terpotong ketika dicetak (print friendly)** sehingga cocok untuk laporan perpustakaan.

---

## Instalasi

1. Download atau clone repository ini
git clone https://github.com/indra-f-r/Laporan_Gabungan-slims.git
2. Salin folder plugin ke direktori
slims/plugins/
Contoh struktur direktori:
plugins/
└── statistik_perpustakaan
    ├── statistik_perpustakaan.plugin.php
    └── index.php

3. Masuk ke **SLiMS Admin Panel**
Reporting → Statistik Perpustakaan
---

## Cara Penggunaan

1. Buka menu **Reporting → Laporan Perpustakaan**

2. Tentukan **tanggal mulai** dan **tanggal akhir** laporan.

3. Klik **Tampilkan** untuk melihat laporan statistik.

4. Klik **Print** untuk mencetak laporan.

---

## Struktur Laporan

Statistik Anggota Aktif  
- Top Peminjam  
- Top Pengunjung  
Rekap Peminjaman  
Kalender Peminjaman  
Kalender Kunjungan  
Top Buku Terlaris  
---

## Kebutuhan Sistem

- SLiMS 9 (Bulian) atau lebih baru
- PHP 7.4 atau lebih baru
- MySQL / MariaDB

---

## Tujuan Plugin

Plugin ini dibuat untuk membantu pustakawan:

- memahami pola penggunaan koleksi
- menganalisis aktivitas anggota
- memantau tingkat kunjungan perpustakaan
- mempermudah penyusunan laporan statistik perpustakaan

---

## Kontribusi

Kontribusi sangat terbuka. Silakan:

- membuat issue
- mengirim pull request
- memberikan saran pengembangan

---

## Lisensi

Plugin ini dirilis dengan lisensi **GNU** sehingga bebas digunakan, dimodifikasi, dan dikembangkan kembali.
