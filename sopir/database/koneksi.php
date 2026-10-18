<?php
/**
 * ============================================================
 * FILE         : sopir/database/koneksi.php
 * FUNGSI       : Koneksi ke database MySQL "e-ticket_bus". Menghasilkan variabel $conn.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Sama dengan penumpang/database/koneksi.php. Ubah user/password di sini kalau MySQL kamu berbeda.
 * ============================================================
 */
// Zona waktu WIB: supaya sapaan (pagi/siang/sore/malam), jam, dan 'hari ini' sesuai waktu Indonesia.
date_default_timezone_set('Asia/Jakarta');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'e-ticket_bus';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+07:00'");   // MySQL ikut WIB, sama dengan PHP
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}