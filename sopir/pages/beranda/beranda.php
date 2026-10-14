<?php
/**
 * ============================================================
 * FILE         : sopir/pages/beranda/beranda.php
 * FUNGSI       : Beranda sopir: sapaan, ringkasan hari ini (jadwal, penumpang, sudah/belum naik),
 *                keberangkatan terdekat, dan daftar jadwal hari ini.
 * DIPANGGIL DARI: sopir/index.php (page=beranda)
 * ============================================================
 */
$jadwal_hari_ini = jadwal_sopir($conn, $sopir['nama'], 'hari_ini');
$jadwal_akan     = jadwal_sopir($conn, $sopir['nama'], 'akan');
$terdekat        = $jadwal_akan[0] ?? null;   // yang berikutnya (atau yang sedang dalam perjalanan)

$jml_jadwal = count($jadwal_hari_ini);
$jml_dewasa = array_sum(array_map('intval', array_column($jadwal_hari_ini, 'total_dewasa')));
$jml_naik   = array_sum(array_map('intval', array_column($jadwal_hari_ini, 'total_naik')));
$jml_belum  = max(0, $jml_dewasa - $jml_naik);

$jam_sekarang = (int) date('G');
$sapaan = $jam_sekarang < 11 ? 'Selamat pagi' : ($jam_sekarang < 15 ? 'Selamat siang' : ($jam_sekarang < 18 ? 'Selamat sore' : 'Selamat malam'));
?>
    <section class="sp-hero">
      <div class="container position-relative">
        <p class="mb-1 opacity-75"><?= e(tanggal_indo(date('Y-m-d H:i:s'), false)) ?></p>
        <h2><?= e($sapaan) ?>, <?= e($sopir['nama']) ?></h2>
        <p>Periksa manifest penumpang, pindai e-ticket sebelum berangkat, dan pastikan semua penumpang sudah naik.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <a href="<?= url('scan', $terdekat ? ['id' => $terdekat['id']] : []) ?>" class="btn btn-light fw-semibold"><i class="bi bi-qr-code-scan me-1"></i>Scan Tiket</a>
          <a href="<?= url('jadwal') ?>" class="btn btn-outline-light"><i class="bi bi-calendar3 me-1"></i>Semua Jadwal</a>
        </div>
      </div>
    </section>

    <section class="section pt-4">
      <div class="container">

        <!-- Ringkasan hari ini -->
        <div class="sp-stat-grid">
          <div class="sp-stat">
            <div class="ikon"><i class="bi bi-signpost-split"></i></div>
            <div><div class="angka"><?= $jml_jadwal ?></div><div class="label">Jadwal hari ini</div></div>
          </div>
          <div class="sp-stat">
            <div class="ikon"><i class="bi bi-people"></i></div>
            <div><div class="angka"><?= $jml_dewasa ?></div><div class="label">Penumpang (lunas)</div></div>
          </div>
          <div class="sp-stat">
            <div class="ikon hijau"><i class="bi bi-check2-circle"></i></div>
            <div><div class="angka"><?= $jml_naik ?></div><div class="label">Sudah naik</div></div>
          </div>
          <div class="sp-stat">
            <div class="ikon oranye"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="angka"><?= $jml_belum ?></div><div class="label">Belum naik</div></div>
          </div>
        </div>

        <!-- Keberangkatan terdekat -->
        <h3 class="sp-judul mt-5">Keberangkatan terdekat</h3>
        <?php if ($terdekat): ?>
          <?php komponen_kartu_jadwal($terdekat); ?>
        <?php else: ?>
          <div class="kosong text-center">
            <i class="bi bi-calendar-x d-block"></i>
            <h5>Belum ada jadwal mendatang</h5>
            <p class="text-muted mb-0">Jadwal ditentukan admin. Hubungi admin bila seharusnya Anda bertugas.</p>
          </div>
        <?php endif; ?>

        <!-- Jadwal hari ini -->
        <h3 class="sp-judul mt-5">Jadwal hari ini</h3>
        <?php if ($jadwal_hari_ini): ?>
          <?php foreach ($jadwal_hari_ini as $j) { komponen_kartu_jadwal($j); } ?>
        <?php else: ?>
          <div class="kosong text-center">
            <i class="bi bi-cup-hot d-block"></i>
            <h5>Tidak ada jadwal hari ini</h5>
            <p class="text-muted mb-3">Lihat jadwal Anda untuk hari-hari berikutnya.</p>
            <a href="<?= url('jadwal') ?>" class="btn btn-primary btn-sm">Lihat Jadwal Saya</a>
          </div>
        <?php endif; ?>

      </div>
    </section>