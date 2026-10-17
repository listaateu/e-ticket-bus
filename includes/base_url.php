<?php
/**
 * ============================================================
 * FILE         : includes/base_url.php
 * FUNGSI       : Menentukan BASE_URL = alamat folder project di browser, OTOMATIS.
 *                Contoh hasilnya:
 *                  http://localhost/e-ticket-bus/...   -> BASE_URL = "/e-ticket-bus"
 *                  http://e-ticket-bus.test/...        -> BASE_URL = ""   (virtual host langsung ke folder project)
 *                Semua link/redirect yang butuh alamat penuh memakai BASE_URL, jadi project bisa
 *                dibuka lewat localhost maupun virtual host (.test) tanpa mengubah kode.
 * DIPANGGIL DARI: admin/login/auth.php, penumpang/function/auth.php, sopir/function/auth.php,
 *                login/*.php, includes/bus_helper.php, penumpang/function/pembayaran.php
 * CATATAN      : Cara kerja: membandingkan lokasi folder project di komputer dengan DOCUMENT_ROOT server.
 *                Kalau tidak bisa dibandingkan (mis. memakai Alias Apache), dipakai nilai lama "/e-ticket-bus".
 * ============================================================
 */
if (!defined('BASE_URL')) {
    $akar_project = realpath(__DIR__ . '/..');   // folder yang berisi admin/, penumpang/, sopir/, login/, includes/
    $akar_server  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $base_url     = null;

    if ($akar_project !== false && $akar_server !== false) {
        $a = rtrim(str_replace('\\', '/', $akar_project), '/');
        $d = rtrim(str_replace('\\', '/', $akar_server), '/');

        if (strcasecmp($a, $d) === 0) {
            $base_url = '';                                         // project = akar server (virtual host)
        } elseif (strncasecmp($a, $d . '/', strlen($d) + 1) === 0) {
            $base_url = substr($a, strlen($d));                     // project di dalam subfolder, mis. "/e-ticket-bus"
        }
    }

    if ($base_url === null) {
        $base_url = '/e-ticket-bus';                                // cadangan: sama seperti sebelumnya
    }

    // Kalau nama folder mengandung spasi/karakter khusus, ubah supaya aman dipakai di alamat
    $base_url = implode('/', array_map('rawurlencode', explode('/', $base_url)));

    define('BASE_URL', $base_url);
    unset($akar_project, $akar_server, $base_url, $a, $d);
}