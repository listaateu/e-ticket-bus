<?php
/**
 * ============================================================
 * FILE         : sopir/function/komponen_flash.php
 * FUNGSI       : Komponen: kotak pesan sukses/gagal sekali tampil.
 * DIPANGGIL DARI: sopir/index.php
 * ============================================================
 */
function komponen_flash(): void
{
    $f = get_flash();
    if (!$f) {
        return;
    }
    $kelas = $f['tipe'] === 'sukses' ? 'success' : ($f['tipe'] === 'info' ? 'info' : 'danger');
    echo '<div class="container pt-3"><div class="alert alert-' . $kelas . ' alert-dismissible fade show mb-0" role="alert">'
        . e($f['pesan'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div></div>';
}