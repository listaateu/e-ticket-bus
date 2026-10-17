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

// Jangan tampilkan pesan error PHP ke pengunjung (bisa membocorkan path & isi query). Error tetap dicatat di log server.
require_once __DIR__ . '/../../includes/base_url.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');   // tolak session ID buatan penyerang
ini_set('session.use_only_cookies', '1');  // session ID tidak boleh lewat alamat (URL)

if (session_status() === PHP_SESSION_NONE) {
    // Cookie session tidak bisa dibaca JavaScript dan tidak ikut terkirim dari situs lain (membantu melawan CSRF)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// ============================================================
// MODE PENGEMBANGAN (sementara, selama sistem login belum dibuat)
// true  = pengecekan login dilewati, halaman admin bisa dipakai tanpa login.
// false = pengecekan login AKTIF.
// WAJIB diubah ke false setelah halaman login selesai dibuat!
// ============================================================
if (!defined('AUTH_DEV_BYPASS')) {
    define('AUTH_DEV_BYPASS', false);   // halaman login admin sudah ada, jadi pengecekan login AKTIF
}

// Alamat halaman login. Ubah bila file login kamu bernama/berlokasi lain.
if (!defined('AUTH_LOGIN_URL')) {
    define('AUTH_LOGIN_URL', BASE_URL . '/admin/login/index.php');
}

// Alamat beranda admin (tujuan setelah login berhasil)
if (!defined('ADMIN_HOME_URL')) {
    define('ADMIN_HOME_URL', BASE_URL . '/admin/index.php');
}

// Batas waktu sesi admin (detik)
if (!defined('AUTH_IDLE_TIMEOUT')) {
    define('AUTH_IDLE_TIMEOUT', 1800);    // 30 menit tidak ada aktivitas -> harus login lagi
}
if (!defined('AUTH_MAX_LIFETIME')) {
    define('AUTH_MAX_LIFETIME', 28800);   // paling lama 8 jam sejak login, seaktif apa pun
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
 * true bila sesi admin sudah lewat batas waktu (terlalu lama diam, atau sudah lewat 8 jam sejak login).
 */
if (!function_exists('admin_sesi_kedaluwarsa')) {
    function admin_sesi_kedaluwarsa(): bool
    {
        $sekarang = time();
        $mulai    = (int) ($_SESSION['admin_login_at'] ?? 0);
        $terakhir = (int) ($_SESSION['admin_terakhir'] ?? 0);
        if ($mulai <= 0 || $terakhir <= 0) {
            return true;
        }
        return ($sekarang - $terakhir > AUTH_IDLE_TIMEOUT) || ($sekarang - $mulai > AUTH_MAX_LIFETIME);
    }
}

/** true bila pengunjung sudah login sebagai admin dan sesinya masih berlaku. */
if (!function_exists('admin_sudah_login')) {
    function admin_sudah_login(): bool
    {
        return idLogin() > 0 && roleLogin() === 'admin' && !admin_sesi_kedaluwarsa();
    }
}

/** Hapus sesi sampai bersih (data di server + cookie di browser). */
if (!function_exists('admin_hapus_sesi')) {
    function admin_hapus_sesi(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?: 'Strict',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

/**
 * Izinkan akses hanya untuk role tertentu, mis. wajibRole(['admin']).
 * - Belum login            -> dilempar ke halaman login.
 * - Sesi admin kedaluwarsa -> sesi dihapus, dilempar ke halaman login.
 * - Sudah login, role salah -> ditolak (403).
 * notif_pembayaran.php dipanggil otomatis oleh JavaScript tiap beberapa detik; dia TIDAK memperpanjang
 * sesi (supaya batas 30 menit diam tetap berlaku) dan menjawab 401, bukan pindah halaman.
 */
if (!function_exists('wajibRole')) {
    function wajibRole(array $roleBoleh): void
    {
        if (AUTH_DEV_BYPASS) {
            return;
        }

        $endpoint_latar = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'notif_pembayaran.php';
        $habis = false;

        if (idLogin() > 0 && roleLogin() === 'admin') {
            if (admin_sesi_kedaluwarsa()) {
                admin_hapus_sesi();
                session_start();           // sesi baru yang kosong
                $habis = true;
            } elseif (!$endpoint_latar) {
                $_SESSION['admin_terakhir'] = time();   // perpanjang batas diam
            }
        }

        if (idLogin() <= 0) {
            if ($endpoint_latar) {
                http_response_code(401);
                exit;
            }
            // Hanya halaman yang dibuka biasa (GET) yang diingat; proses POST tidak bisa dibuka ulang
            $_SESSION['redirect_setelah_login'] = (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET')
                ? ($_SERVER['REQUEST_URI'] ?? '')
                : '';
            header('Location: ' . AUTH_LOGIN_URL . ($habis ? '?habis=1' : ''));
            exit;
        }

        if (!in_array(roleLogin(), $roleBoleh, true)) {
            http_response_code(403);
            exit('Akses ditolak: halaman ini hanya untuk ' . htmlspecialchars(implode(', ', $roleBoleh), ENT_QUOTES, 'UTF-8') . '.');
        }
    }
}


// ============================================================
// BANTUAN KEAMANAN (dipakai semua halaman & proses admin)
// ============================================================

/**
 * Ambil parameter GET/POST SEBAGAI TEKS. Kalau yang dikirim bukan teks (mis. ?status[]=x),
 * dipakai nilai bawaan, jadi tidak ada TypeError dan tidak ada data aneh yang masuk ke query.
 */
if (!function_exists('param_str')) {
    function param_str(string $kunci, string $bawaan = '', string $sumber = 'GET', int $maks = 100): string
    {
        $data  = $sumber === 'POST' ? $_POST : $_GET;
        $nilai = $data[$kunci] ?? $bawaan;
        if (!is_string($nilai)) {
            return $bawaan;
        }
        return mb_substr(trim($nilai), 0, $maks, 'UTF-8');
    }
}

/** Ambil parameter yang HARUS salah satu dari daftar resmi. Di luar daftar -> nilai bawaan. */
if (!function_exists('param_enum')) {
    function param_enum(string $kunci, array $boleh, string $bawaan = '', string $sumber = 'GET'): string
    {
        $nilai = param_str($kunci, $bawaan, $sumber, 50);
        return in_array($nilai, $boleh, true) ? $nilai : $bawaan;
    }
}

/** Ambil parameter angka bulat positif (id). Bukan angka -> 0. */
if (!function_exists('param_id')) {
    function param_id(string $kunci, string $sumber = 'GET'): int
    {
        $nilai = param_str($kunci, '0', $sumber, 12);
        return ctype_digit($nilai) ? (int) $nilai : 0;
    }
}

/** Token CSRF: satu token acak per session. */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/** Input tersembunyi berisi token. WAJIB ada di setiap <form method="post">. */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

/** Cek token yang dikirim form sama dengan token session. */
if (!function_exists('csrf_valid')) {
    function csrf_valid(): bool
    {
        $kirim = $_POST['csrf_token'] ?? '';
        $asli  = $_SESSION['csrf_token'] ?? '';
        return is_string($kirim) && is_string($asli) && $kirim !== '' && hash_equals($asli, $kirim);
    }
}

/**
 * Pintu untuk proses yang mengubah data (hapus/simpan) di admin/function/*.php.
 * - Bukan POST          -> 405 (link yang dibuka/disalin ke tab baru tidak berbuat apa-apa)
 * - Token CSRF salah    -> 403
 */
if (!function_exists('wajibPostCsrf')) {
    function wajibPostCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Metode tidak diizinkan. Gunakan tombol di halaman admin.');
        }
        if (!csrf_valid()) {
            http_response_code(403);
            exit('Token keamanan tidak valid atau sudah kedaluwarsa. Kembali ke halaman admin, muat ulang, lalu coba lagi.');
        }
    }
}