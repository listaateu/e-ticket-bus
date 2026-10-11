<?php
/**
 * ============================================================
 * FILE         : login/auth.php
 * FUNGSI       : Cek login & role (admin / kondektur / penumpang) untuk halaman yang dilindungi.
 * DIPANGGIL DARI: admin/function/*.php  ->  require_once __DIR__ . '/../login/auth.php';
 *                                           wajibRole(['admin']);
 * CATATAN      : Membaca session yang diisi halaman login:
 *                $_SESSION['user_id'] (atau ['id'] / ['user']['id']) dan $_SESSION['role'] (atau ['user']['role']).
 *                Kalau halaman login kamu memakai nama session lain, sesuaikan di fungsi
 *                idLogin() dan roleLogin() di bawah.
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// MODE PENGEMBANGAN (sementara, selama sistem login belum dibuat)
// true  = pengecekan login dilewati, halaman admin bisa dipakai tanpa login.
// false = pengecekan login AKTIF.
// WAJIB diubah ke false setelah halaman login selesai dibuat!
// ============================================================
if (!defined('AUTH_DEV_BYPASS')) {
    define('AUTH_DEV_BYPASS', true);
}

// Alamat halaman login. Ubah bila file login kamu bernama/berlokasi lain.
if (!defined('AUTH_LOGIN_URL')) {
    define('AUTH_LOGIN_URL', '/e-ticket-bus/admin/login/index.php');
}

if (!function_exists('idLogin')) {
    function idLogin(): int
    {
        return (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? ($_SESSION['user']['id'] ?? 0));
    }
}

if (!function_exists('roleLogin')) {
    function roleLogin(): string
    {
        return (string) ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? ''));
    }
}

/**
 * Izinkan akses hanya untuk role tertentu, mis. wajibRole(['admin']).
 * - Belum login            -> dilempar ke halaman login.
 * - Sudah login, role salah -> ditolak (403).
 */
if (!function_exists('wajibRole')) {
    function wajibRole(array $roleBoleh): void
    {
        if (AUTH_DEV_BYPASS) {
            return;
        }

        if (idLogin() <= 0) {
            $_SESSION['redirect_setelah_login'] = $_SERVER['REQUEST_URI'] ?? '';
            header('Location: ' . AUTH_LOGIN_URL);
            exit;
        }

        if (!in_array(roleLogin(), $roleBoleh, true)) {
            http_response_code(403);
            exit('Akses ditolak: halaman ini hanya untuk ' . htmlspecialchars(implode(', ', $roleBoleh), ENT_QUOTES, 'UTF-8') . '.');
        }
    }
}