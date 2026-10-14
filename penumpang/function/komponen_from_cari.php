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
    [$dewasa, $bayi] = jumlah_penumpang_dari($nilai);   // 1-4 dewasa, bayi 0..dewasa
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

        <!-- Jumlah penumpang: dewasa 1-<?= MAKS_DEWASA ?>, bayi maksimal sebanyak dewasa -->
        <div class="tv-col tv-pax-pick" data-maks-dewasa="<?= MAKS_DEWASA ?>">
          <label>Penumpang</label>
          <button type="button" class="tv-input tv-pax-btn" aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-people"></i>
            <span class="tv-pax-teks"><?= e(teks_penumpang($dewasa, $bayi)) ?></span>
            <i class="bi bi-chevron-down tv-pax-panah"></i>
          </button>
          <input type="hidden" name="dewasa" class="tv-in-dewasa" value="<?= $dewasa ?>">
          <input type="hidden" name="bayi" class="tv-in-bayi" value="<?= $bayi ?>">

          <div class="tv-pax-pop" hidden>
            <div class="tv-pax-row">
              <div>
                <strong>Dewasa</strong>
                <small>Maksimal <?= MAKS_DEWASA ?> orang</small>
              </div>
              <div class="tv-step">
                <button type="button" data-aksi="kurang" data-jenis="dewasa" aria-label="Kurangi dewasa"><i class="bi bi-dash"></i></button>
                <span class="tv-step-n" data-n="dewasa"><?= $dewasa ?></span>
                <button type="button" data-aksi="tambah" data-jenis="dewasa" aria-label="Tambah dewasa"><i class="bi bi-plus"></i></button>
              </div>
            </div>
            <div class="tv-pax-row">
              <div>
                <strong>Bayi</strong>
                <small>Gratis, tanpa kursi sendiri. Maks. 1 bayi per dewasa</small>
              </div>
              <div class="tv-step">
                <button type="button" data-aksi="kurang" data-jenis="bayi" aria-label="Kurangi bayi"><i class="bi bi-dash"></i></button>
                <span class="tv-step-n" data-n="bayi"><?= $bayi ?></span>
                <button type="button" data-aksi="tambah" data-jenis="bayi" aria-label="Tambah bayi"><i class="bi bi-plus"></i></button>
              </div>
            </div>
            <button type="button" class="tv-pax-ok">Selesai</button>
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