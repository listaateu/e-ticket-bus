<?php
/**
 * ============================================================
 * FILE         : sopir/function/helpers.php
 * FUNGSI       : Fungsi bantu: e() (aman XSS), tanggal_indo(), jam(), url() (link antar halaman),
 *                redirect(), pesan flash, token CSRF.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Menambah halaman baru? Tambahkan juga di peta url() di bawah.
 * ============================================================
 */

function e(mixed $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function tanggal_indo(?string $datetime, bool $dengan_jam = true): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $t = $datetime ? strtotime($datetime) : false;
    if (!$t) {
        return '-';
    }
    $hasil = $hari[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $dengan_jam ? $hasil . ' · ' . date('H:i', $t) . ' WIB' : $hasil;
}

function jam(?string $datetime): string
{
    $t = $datetime ? strtotime($datetime) : false;
    return $t ? date('H:i', $t) : '-';
}

function persen(int $bagian, int $total): int
{
    return $total > 0 ? (int) round($bagian / $total * 100) : 0;
}

/** URL antar halaman, mis. url('manifest', ['id' => 5]). Nama halaman dipetakan ke ?page=...&aksi=... */
function url(string $halaman = 'beranda', array $param = []): string
{
    $peta = [
        'beranda'   => ['beranda', ''],
        'jadwal'    => ['jadwal', ''],
        'manifest'  => ['jadwal', 'manifest'],
        'ubah-naik' => ['jadwal', 'toggle'],
        'scan'      => ['scan', ''],
        'validasi'  => ['scan', 'validasi'],
        'profil'    => ['profil', ''],
    ];
    [$page, $aksi] = $peta[$halaman] ?? ['beranda', ''];
    $q = ['page' => $page];
    if ($aksi !== '') {
        $q['aksi'] = $aksi;
    }
    return 'index.php?' . http_build_query(array_merge($q, $param));
}

function redirect(string $tujuan): void
{
    header('Location: ' . $tujuan);
    exit;
}

/* ---------- Flash message ---------- */
function set_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
require_once __DIR__ . '/../../includes/session_aman.php';   // permintaan_satu_situs()

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf']) && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf'])
        && permintaan_satu_situs();   // lapis kedua: Origin/Referer harus dari situs ini sendiri
}