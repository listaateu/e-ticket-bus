<?php
/**
 * ============================================================
 * FILE         : sopir/database/koneksi.php
 * FUNGSI       : Koneksi ke database MySQL "e-ticket_bus". Menghasilkan variabel $conn.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Sama dengan penumpang/database/koneksi.php. Ubah user/password di sini kalau MySQL kamu berbeda.
 * ============================================================
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
    exit('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}