<?php
/**
 * Plugin Name: Laporan Gabungan Perpustakaan
 * Plugin URI: https://github.com/indra-f-r/Laporan_Gabungan_SLiMS
 * Description: Plugin untuk Laporan Gabungan Perpustakaan 
 * Version: 1.0.0
 * Author: Indra Febriana Rulliawan (indra.f.rulliawan@gmail.com)
 * Author URI: https://github.com/indra-f-r
 */

use SLiMS\Plugins;

// Ambil instance plugin manager
$plugin = Plugins::getInstance();

// Daftarkan menu ke modul reporting
$plugin->registerMenu(
    'reporting',
    'Laporan Gabungan',
    __DIR__ . '/index.php'
);
