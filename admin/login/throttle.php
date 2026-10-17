<?php
/**
 * admin/login/throttle.php
 * Pembatas percobaan login (anti tebak-tebak password / brute force).
 * - Per kombinasi (alamat IP + username yang diketik): maks 5 kali salah, lalu dikunci 10 menit.
 * - Per alamat IP: maks 20 kali salah (ganti-ganti username pun tetap kena), lalu dikunci 10 menit.
 * Catatan percobaan disimpan di folder sementara server (bukan di folder web), tanpa perlu tabel baru.
 */
defined('ADMIN_LOGIN') || exit('Akses langsung tidak diizinkan.');

const THR_MAKS_USER = 5;     // salah berturut-turut untuk 1 IP + 1 username
const THR_MAKS_IP   = 20;    // salah berturut-turut untuk 1 IP (username apa pun)
const THR_JENDELA   = 900;   // detik: hitungan salah di-reset kalau sudah 15 menit tenang
const THR_KUNCI     = 600;   // detik: lama dikunci (10 menit)

function thr_path(string $kunci): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eticket_thr_' . hash('sha256', $kunci) . '.json';
}

function thr_baca(string $kunci): array
{
    $bawaan = ['n' => 0, 'mulai' => 0, 'kunci' => 0];
    $f = thr_path($kunci);
    if (!is_file($f)) {
        return $bawaan;
    }
    $d = json_decode((string) @file_get_contents($f), true);
    return is_array($d) ? array_merge($bawaan, $d) : $bawaan;
}

function thr_tulis(string $kunci, array $data): void
{
    @file_put_contents(thr_path($kunci), json_encode($data), LOCK_EX);
}

/** Sisa detik dikunci (0 = boleh mencoba). */
function thr_sisa(string $kunci): int
{
    $d = thr_baca($kunci);
    return max(0, (int) $d['kunci'] - time());
}

/** Catat 1 percobaan gagal; kunci kalau sudah mencapai batas. */
function thr_gagal(string $kunci, int $maks): void
{
    $d   = thr_baca($kunci);
    $now = time();
    if ($now - (int) $d['mulai'] > THR_JENDELA) {
        $d = ['n' => 0, 'mulai' => $now, 'kunci' => 0];
    }
    $d['n']++;
    if ($d['n'] >= $maks) {
        $d = ['n' => 0, 'mulai' => $now, 'kunci' => $now + THR_KUNCI];
    }
    thr_tulis($kunci, $d);
}

/** Hapus catatan gagal (dipanggil setelah login berhasil). */
function thr_reset(string $kunci): void
{
    @unlink(thr_path($kunci));
}