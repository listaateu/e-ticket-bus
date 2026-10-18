<?php
/**
 * ============================================================
 * FILE         : includes/session_aman.php
 * FUNGSI       : Pengaman bersama untuk sisi PENUMPANG, SOPIR, dan LOGIN, juga dipakai admin:
 *                1. mulai_session_pengguna() : session dengan cookie HttpOnly + SameSite=Lax
 *                   (browser tidak ikut mengirim cookie login pada form POST dari situs lain).
 *                   Nama session sengaja dibedakan dari admin (ETB_USER vs ETB_ADMIN), jadi
 *                   admin dan pengguna tidak saling menimpa di satu browser/alamat.
 *                2. permintaan_satu_situs()  : lapis kedua anti-CSRF, memastikan header Origin/Referer
 *                   permintaan POST berasal dari alamat situs ini sendiri.
 *                3. kirim_header_keamanan()  : halaman tidak bisa dimasukkan ke <iframe> situs lain
 *                   (mencegah clickjacking, yaitu tombol disusupi tampilan palsu).
 * DIPANGGIL DARI: penumpang/function/auth.php, sopir/function/auth.php, login/logout.php,
 *                admin/login/auth.php (hanya permintaan_satu_situs)
 * ============================================================
 */

if (!function_exists('kirim_header_keamanan')) {
    function kirim_header_keamanan(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
    }
}

if (!function_exists('mulai_session_pengguna')) {
    function mulai_session_pengguna(): void
    {
        kirim_header_keamanan();
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');    // tolak session ID buatan penyerang
        ini_set('session.use_only_cookies', '1');   // session ID tidak boleh lewat alamat (URL)
        session_name('ETB_USER');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,                      // tidak bisa dibaca JavaScript
            'samesite' => 'Lax',                     // tidak ikut terkirim pada POST dari situs lain
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

/**
 * true bila permintaan ini datang dari halaman situs ini sendiri.
 * - Header Origin dipakai bila ada (browser selalu mengirimnya pada POST), kalau tidak ada dipakai Referer.
 * - Kalau keduanya tidak dikirim, dianggap lolos: token CSRF tetap menjadi penjaga utama.
 */
if (!function_exists('permintaan_satu_situs')) {
    function permintaan_satu_situs(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $kunci) {
            $nilai = (string) ($_SERVER[$kunci] ?? '');
            if ($nilai === '') {
                continue;
            }
            $bagian = parse_url($nilai);
            $asal   = strtolower((string) ($bagian['host'] ?? '') . (isset($bagian['port']) ? ':' . $bagian['port'] : ''));
            return $asal !== '' && $host !== '' && $asal === $host;   // Origin "null" -> ditolak
        }
        return true;
    }
}