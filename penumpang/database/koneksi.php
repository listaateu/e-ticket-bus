<?php
/**
 * ============================================================
 * FILE         : penumpang/database/koneksi.php
 * FUNGSI       : Koneksi ke database MySQL "e-ticket_bus". Menghasilkan variabel $conn.
 * DIPANGGIL DARI: index.php (require_once di bagian atas)
 * CATATAN      : Ubah user/password/nama database di sini kalau MySQL kamu berbeda.
 * ============================================================
 */
/**
 * Koneksi database e-ticket bus (penumpang)
 * Sesuaikan jika user / password MySQL kamu berbeda.
 */
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'e-ticket_bus';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('Koneksi database gagal: ' . $e->getMessage());   // detail hanya di log server, tidak ditampilkan ke pengunjung
    exit('Koneksi ke database gagal.');
}