<?php
/**
 * ============================================================
 * FILE         : includes/alasan_tolak.php
 * FUNGSI       : Daftar alasan penolakan pembayaran + fungsi menyusun teks alasan.
 * DIPANGGIL DARI: admin/function/verifikasi_pembayaran.php,
 *                admin/pages/verifikasi_pembayaran/verifikasi_pembayaran_detail.php
 * CATATAN      : Mau tambah alasan baru? Cukup tambah satu baris di ALASAN_TOLAK.
 *                Kuncinya (kiri) dipakai di form, teksnya (kanan) yang dilihat penumpang.
 * ============================================================
 */

const ALASAN_TOLAK = [
    'nominal'  => 'Nominal transfer tidak sesuai dengan total bayar',
    'buram'    => 'Bukti transfer buram atau tidak terbaca',
    'bukan'    => 'Gambar yang diunggah bukan bukti transfer',
    'rekening' => 'Rekening atau metode tujuan tidak sesuai',
    'tanggal'  => 'Tanggal atau waktu transfer tidak sesuai',
    'belum'    => 'Dana belum masuk ke rekening kami',
    'berangkat'=> 'Bus sudah berangkat. Dana akan dikembalikan oleh admin',
    'lainnya'  => 'Alasan lain (tulis di catatan)',
];

const CATATAN_TOLAK_MAKS = 150;   // batas panjang catatan (huruf)

/**
 * Menyusun teks alasan yang disimpan ke database dan dilihat penumpang.
 * - Mengembalikan null kalau alasan tidak valid,
 *   atau kalau memilih "lainnya" tapi catatan kosong.
 */
function susun_alasan_tolak(string $kode, string $catatan): ?string
{
    if (!isset(ALASAN_TOLAK[$kode])) {
        return null;
    }

    $catatan = trim((string) preg_replace('/\s+/', ' ', $catatan));
    $catatan = mb_substr($catatan, 0, CATATAN_TOLAK_MAKS);

    if ($kode === 'lainnya') {
        return $catatan === '' ? null : $catatan;
    }

    return $catatan === ''
        ? ALASAN_TOLAK[$kode]
        : ALASAN_TOLAK[$kode] . '. Catatan: ' . $catatan;
}