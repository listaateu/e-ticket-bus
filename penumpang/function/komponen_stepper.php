<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_stepper.php
 * FUNGSI       : Komponen: garis langkah pemesanan (1 Pesan - 2 Bayar - 3 E-tiket) di atas halaman
 *                pilih kursi & pembayaran, gaya Traveloka.
 * DIPANGGIL DARI: pages/jadwal/pilih_kursi.php, pages/tiket/pembayaran.php
 * CATATAN      : File komponen_*.php dimuat otomatis oleh index.php, tidak perlu di-require manual.
 *                $aktif = 1 (Pesan), 2 (Bayar), 3 (E-tiket). Langkah sebelum $aktif tampil centang.
 * ============================================================
 */
function komponen_stepper(int $aktif): void
{
    $langkah = [1 => 'Pesan', 2 => 'Bayar', 3 => 'E-tiket'];
    ?>
    <div class="tv-stepper-bar">
      <div class="container">
        <ol class="tv-stepper" aria-label="Langkah pemesanan">
          <?php foreach ($langkah as $no => $label):
              $kelas = $no < $aktif ? 'selesai' : ($no === $aktif ? 'aktif' : '');
          ?>
            <li class="<?= $kelas ?>" <?= $no === $aktif ? 'aria-current="step"' : '' ?>>
              <span class="bulat"><?= $no < $aktif ? '<i class="bi bi-check-lg"></i>' : $no ?></span>
              <span class="nama"><?= e($label) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
<?php }