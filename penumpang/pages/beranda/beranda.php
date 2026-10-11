<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/beranda/beranda.php
 * FUNGSI       : Halaman BERANDA gaya Traveloka: hero biru + kartu cari, cara pesan, jadwal terdekat.
 * DIPANGGIL DARI: index.php (page=beranda / default)
 * CATATAN      : URL: index.php
 * ============================================================
 */
$kota      = daftar_kota($conn);
$terdekat  = cari_jadwal($conn, '', '', '', 4);
$jml_bus   = (int) $conn->query('SELECT COUNT(*) FROM buses')->fetch_column();
$jml_rute  = (int) $conn->query('SELECT COUNT(DISTINCT kota_asal, kota_tujuan) FROM schedules')->fetch_column();
$jml_tiket = (int) $conn->query('SELECT COUNT(*) FROM bookings')->fetch_column();
$jml_kota  = count($kota);
?>
    <!-- Hero -->
    <section class="tv-hero">
      <div class="container">
        <h2><?= $user ? 'Halo, ' . e($user['nama']) . '!' : 'Selamat datang!' ?> Mau berangkat ke mana?</h2>
        <p>Pesan tiket bus interkota tanpa antre. Pilih rute, pilih nomor kursi favoritmu, bayar online, lalu tunjukkan e-ticket saat naik bus.</p>
      </div>
    </section><!-- /Hero -->

    <!-- Kartu pencarian (menumpuk di atas hero) -->
    <section class="tv-search-wrap">
      <div class="container">
        <div class="tv-search-card" data-aos="fade-up">
          <div class="tv-tabs">
            <span class="tv-tab active"><i class="fa-solid fa-bus"></i> Tiket Bus</span>
          </div>

          <?php komponen_form_cari($kota); ?>

          <div class="tv-trusted">
            <?php
            $stat = [[$jml_bus, 'Armada'], [$jml_rute, 'Rute'], [$jml_kota, 'Kota'], [$jml_tiket, 'Tiket Terjual']];
            foreach ($stat as [$angka, $label]): ?>
              <div><strong><?= (int) $angka ?></strong><?= e($label) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section><!-- /Kartu pencarian -->

    <!-- Cara Pesan -->
    <section id="cara-pesan" class="tv-section">
      <div class="container">
        <h3 class="tv-section-title">Pesan tiket dalam 4 langkah</h3>
        <p class="tv-section-sub">Mudah, cepat, tanpa perlu antre di terminal.</p>

        <div class="tv-steps">
          <?php
          $langkah = [
              ['fa-magnifying-glass-location', 'Cari Rute', 'Masukkan kota asal, tujuan, dan tanggal keberangkatan.'],
              ['fa-chair', 'Pilih Kursi', 'Lihat denah bus dan pilih nomor kursi yang masih kosong.'],
              ['fa-wallet', 'Bayar Online', 'Transfer lalu unggah bukti, admin akan memverifikasi.'],
              ['fa-qrcode', 'Tunjukkan E-Ticket', 'Kondektur memindai QR di e-ticket kamu saat naik.'],
          ];
          foreach ($langkah as $i => [$ikon, $judul, $isi]): ?>
            <div class="tv-step" data-aos="fade-up" data-aos-delay="<?= ($i + 1) * 80 ?>">
              <div class="ikon"><i class="fa-solid <?= $ikon ?>"></i></div>
              <h4><?= e($judul) ?></h4>
              <p><?= e($isi) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section><!-- /Cara Pesan -->

    <!-- Jadwal Terdekat -->
    <section id="jadwal-terdekat" class="tv-section tv-bg">
      <div class="container">
        <h3 class="tv-section-title">Berangkat dalam waktu dekat</h3>
        <p class="tv-section-sub">Segera amankan kursimu sebelum kehabisan.</p>

        <?php if (!$terdekat): ?>
          <div class="text-center text-muted py-4">Belum ada jadwal keberangkatan.</div>
        <?php endif; ?>
        <?php foreach ($terdekat as $j) { komponen_card_jadwal($j); } ?>

        <div class="text-center mt-4">
          <a href="<?= url('jadwal') ?>" class="tv-btn-outline">Lihat Semua Jadwal</a>
        </div>
      </div>
    </section><!-- /Jadwal Terdekat -->