<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_badge_status.php
 * FUNGSI       : Komponen kecil: label status pembayaran (Lunas / Menunggu / dst.).
 * DIPANGGIL DARI: index.php (otomatis lewat glob komponen_*.php)
 * ============================================================
 */
/** Badge status pembayaran */
function komponen_badge_status(string $status): string
{
    $peta = [
        'belum_bayar' => ['secondary', 'Belum Dibayar'],
        'pending'     => ['warning text-dark', 'Menunggu Verifikasi'],
        'lunas'       => ['success', 'Lunas'],
        'gagal'       => ['danger', 'Ditolak'],
    ];
    [$kelas, $label] = $peta[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge bg-' . $kelas . ' status-badge">' . e($label) . '</span>';
}