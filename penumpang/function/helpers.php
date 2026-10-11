<?php
/**
 * ============================================================
 * FILE         : penumpang/function/helpers.php
 * FUNGSI       : Fungsi bantu umum: e() (aman XSS), rupiah(), tanggal_indo(), url() (pembuat link antar halaman), redirect(), pesan flash, token CSRF.
 * DIPANGGIL DARI: index.php
 * CATATAN      : Fungsi url() menerjemahkan nama halaman jadi ?page=...&aksi=... Kalau menambah halaman baru, tambahkan juga di peta url().
 * ============================================================
 */
/** Fungsi bantu umum */

function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function tanggal_indo(string $datetime, bool $dengan_jam = true): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $t = strtotime($datetime);
    if (!$t) {
        return '-';
    }
    $hasil = $hari[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $dengan_jam ? $hasil . ' · ' . date('H:i', $t) . ' WIB' : $hasil;
}

function jam(string $datetime): string
{
    return date('H:i', strtotime($datetime));
}

/**
 * URL antar halaman, mis. url('jadwal', ['asal' => 'Jakarta']).
 * Nama halaman dipetakan ke ?page=...&aksi=...
 */
function url(string $halaman = 'beranda', array $param = []): string
{
    $peta = [
        'beranda'     => ['beranda', ''],
        'jadwal'      => ['jadwal', ''],
        'pilih-kursi' => ['jadwal', 'pilih_kursi'],
        'tiket-saya'  => ['tiket', ''],
        'pembayaran'  => ['tiket', 'pembayaran'],
        'e-ticket'    => ['tiket', 'e_ticket'],
        'profil'      => ['profil', ''],
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
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}