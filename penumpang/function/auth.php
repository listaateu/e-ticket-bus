<?php
/**
 * ============================================================
 * FILE         : penumpang/function/auth.php
 * FUNGSI       : Status login penumpang. Beranda & Jadwal boleh dibuka TAMU (tanpa login);
 *                halaman pemesanan/tiket/profil wajib login.
 * DIPANGGIL DARI: index.php
 * CATATAN      : Sesuaikan LOGIN_URL / REGISTER_URL / LOGOUT_URL dan nama session
 *                ($_SESSION["user_id"], ["role"]) dengan folder /login milikmu (dibuat terakhir).
 * ============================================================
 */
const LOGIN_URL    = '../login/index.php';
const REGISTER_URL = '../login/register.php';
const LOGOUT_URL   = '../login/logout.php';
const ROLE_PENUMPANG = 'penumpang';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function user_id_login(): int
{
    return (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? ($_SESSION['user']['id'] ?? 0));
}

function role_login(): string
{
    return (string) ($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? ''));
}

/** true bila pengunjung sudah login SEBAGAI penumpang */
function sudah_login_penumpang(): bool
{
    return user_id_login() > 0 && role_login() === ROLE_PENUMPANG;
}

/**
 * Dipanggil di halaman yang butuh login (pilih kursi, tiket, profil).
 * Tamu dilempar ke login; alamat halaman ini disimpan di session supaya
 * setelah login/register berhasil, penumpang dikembalikan ke halaman tadi.
 */
function wajib_penumpang(): void
{
    if (sudah_login_penumpang()) {
        return;
    }
    $_SESSION['redirect_setelah_login'] = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . LOGIN_URL);
    exit;
}

/**
 * Dipakai oleh halaman login/register (nanti) setelah login penumpang berhasil:
 *   header('Location: ' . ambil_redirect_setelah_login('../penumpang/index.php'));
 * Hanya menerima alamat internal (diawali "/" dan bukan "//") agar tidak jadi open-redirect.
 */
function ambil_redirect_setelah_login(string $default = 'index.php'): string
{
    $tujuan = $_SESSION['redirect_setelah_login'] ?? '';
    unset($_SESSION['redirect_setelah_login']);
    if ($tujuan !== '' && $tujuan[0] === '/' && !str_starts_with($tujuan, '//') && !str_contains($tujuan, "\n") && !str_contains($tujuan, "\r")) {
        return $tujuan;
    }
    return $default;
}

function get_user(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT id, nama, username, email, no_hp, role FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}