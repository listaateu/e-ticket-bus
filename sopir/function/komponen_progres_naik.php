<?php
/**
 * ============================================================
 * FILE         : sopir/function/komponen_progress_naik.php
 * FUNGSI       : Komponen: batang progres "x dari y penumpang sudah naik".
 * DIPANGGIL DARI: komponen_kartu_jadwal.php, pages/jadwal/manifest.php, pages/scan/scan.php
 * CATATAN      : $id (opsional) dipakai JavaScript halaman Scan untuk memperbarui angkanya tanpa reload.
 * ============================================================
 */
function komponen_progress_naik(int $naik, int $total, string $id = ''): void
{
    $pct = persen($naik, $total);
    $atribut = $id !== '' ? ' id="' . e($id) . '"' : '';
    ?>
    <div class="progres-naik"<?= $atribut ?> data-naik="<?= $naik ?>" data-total="<?= $total ?>">
      <div class="d-flex justify-content-between small mb-1">
        <span class="text-muted">Penumpang naik</span>
        <strong><span class="js-naik"><?= $naik ?></span> / <span class="js-total"><?= $total ?></span></strong>
      </div>
      <div class="progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar js-bar<?= $total > 0 && $naik >= $total ? ' bg-success' : '' ?>" style="width: <?= $pct ?>%"></div>
      </div>
    </div>
<?php }