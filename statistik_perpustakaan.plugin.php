<?php
/**
 * Plugin Name: Laporan Statistik Perpustakaan
 * Plugin URI: https://github.com
 * Description: Plugin untuk Statistik Perpustakaan 
 * Version: 1.0.0
 * Author: Indra Febriana Rulliawan (indra.f.rulliawan@gmail.com)
 * Author URI: https://github.com
 */

use SLiMS\Plugins;

// Ambil instance plugin manager
$plugin = Plugins::getInstance();

// Daftarkan menu ke modul reporting
$plugin->registerMenu(
    'reporting',
    'Statistik Perpustakaan',
    __DIR__ . '/index.php'
);
