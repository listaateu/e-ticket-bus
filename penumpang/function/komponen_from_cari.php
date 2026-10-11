<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_form_cari.php
 * FUNGSI       : Komponen: form cari jadwal gaya Traveloka (asal, tujuan, tanggal).
 * DIPANGGIL DARI: pages/beranda/beranda.php, pages/jadwal/jadwal.php
 * ============================================================
 */
/** Form pencarian jadwal: asal, tujuan, tanggal ($gelap dibiarkan demi kompatibilitas) */
function komponen_form_cari(array $kota, array $nilai = [], bool $gelap = false): void
{
    static $script_dimuat = false;
    ?>
    <form action="index.php" method="get" class="tv-cari">
      <input type="hidden" name="page" value="jadwal">

      <div class="tv-cari-grid">
        <div class="tv-col">
          <label for="tv-asal">Asal</label>
          <div class="tv-input">
            <i class="bi bi-record-circle"></i>
            <input type="text" id="tv-asal" name="asal" list="daftar-kota" placeholder="Mis. Magelang" autocomplete="off" value="<?= e($nilai['asal'] ?? '') ?>">
          </div>
          <button type="button" class="tv-swap" title="Tukar asal dan tujuan" aria-label="Tukar asal dan tujuan" onclick="tvTukarKota(this)">
            <i class="bi bi-arrow-left-right"></i>
          </button>
        </div>

        <div class="tv-col">
          <label for="tv-tujuan">Tujuan</label>
          <div class="tv-input">
            <i class="bi bi-geo-alt"></i>
            <input type="text" id="tv-tujuan" name="tujuan" list="daftar-kota" placeholder="Mis. Jakarta" autocomplete="off" value="<?= e($nilai['tujuan'] ?? '') ?>">
          </div>
        </div>

        <div class="tv-col">
          <label for="tv-tanggal">Tanggal Berangkat</label>
          <div class="tv-input">
            <i class="bi bi-calendar-event"></i>
            <input type="date" id="tv-tanggal" name="tanggal" min="<?= date('Y-m-d') ?>" value="<?= e($nilai['tanggal'] ?? '') ?>">
          </div>
        </div>

        <button type="submit" class="tv-go"><i class="bi bi-search"></i><span>Cari Bus</span></button>
      </div>

      <datalist id="daftar-kota">
        <?php foreach ($kota as $k): ?><option value="<?= e($k) ?>"><?php endforeach; ?>
      </datalist>
    </form>

    <?php if (!$script_dimuat): $script_dimuat = true; ?>
    <script>
      // Tombol bulat di tengah: tukar isi kolom Asal dan Tujuan
      function tvTukarKota(tombol) {
        var f = tombol.closest('form');
        var asal = f.querySelector('[name="asal"]');
        var tujuan = f.querySelector('[name="tujuan"]');
        var sementara = asal.value;
        asal.value = tujuan.value;
        tujuan.value = sementara;
      }
    </script>
    <?php endif; ?>
<?php }