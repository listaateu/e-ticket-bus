<?php
/**
 * ============================================================
 * FILE         : sopir/pages/scan/scan.php
 * FUNGSI       : Halaman SCAN TIKET. Sopir memilih jadwal, lalu memindai QR e-ticket penumpang dengan
 *                kamera, atau mengetik / menembak kode dengan alat scanner USB. Hasilnya (hijau / kuning /
 *                merah) muncul langsung tanpa reload, dan penumpang yang valid dicatat SUDAH NAIK.
 * DIPANGGIL DARI: sopir/index.php (page=scan)
 * CATATAN      : URL: index.php?page=scan&id=ID_JADWAL (tanpa id = jadwal terdekat hari ini).
 *                Pilihan jadwal hanya berisi jadwal HARI INI yang belum selesai.
 *                Pemeriksaan ada di function/scan_tiket.php, pengiriman JSON di sopir/index.php (aksi=validasi),
 *                kamera & tampilan hasil di assets/js/sopir.js.
 * ============================================================
 */
// Pilihan jadwal = jadwal HARI INI yang belum selesai (urut jam berangkat). Jadwal hari-hari lain tidak ditampilkan,
// karena tiketnya belum bisa dipindai hari ini.
$pilihan = array_values(array_filter(
    jadwal_sopir($conn, $sopir['nama'], 'hari_ini'),
    fn($j) => empty($j['sudah_tiba'])
));
usort($pilihan, fn($a, $b) => strcmp((string) $a['jam_berangkat'], (string) $b['jam_berangkat']));

// Jadwal yang dipilih: dari ?id= bila valid & belum selesai (mis. dari tombol Scan di Manifest), kalau tidak pakai yang terdekat hari ini
$jadwal = null;
$id_minta = (int) ($_GET['id'] ?? 0);
if ($id_minta > 0) {
    $kandidat = get_jadwal_sopir($conn, $id_minta, $sopir['nama']);
    if ($kandidat && empty($kandidat['sudah_tiba'])) {
        $jadwal = $kandidat;
        $sudah_ada = array_filter($pilihan, fn($j) => (int) $j['id'] === (int) $kandidat['id']);
        if (!$sudah_ada) {
            array_unshift($pilihan, $kandidat);   // jadwal yang diminta (bukan hari ini) tetap muncul supaya bisa dipilih
        }
    }
}
if (!$jadwal && $pilihan) {
    $jadwal = $pilihan[0];
}

if ($jadwal) {
    $hitung  = hitung_naik($conn, (int) $jadwal['id']);
    $riwayat = riwayat_naik($conn, (int) $jadwal['id']);
}
?>
    <?php komponen_page_title('Scan Tiket', 'Pindai QR e-ticket penumpang sebelum bus berangkat.', ['Scan Tiket' => null]); ?>

    <section id="scan" class="section">
      <div class="container">

        <?php if (!$jadwal): ?>
          <div class="kosong text-center">
            <i class="bi bi-calendar-x d-block"></i>
            <h5>Tidak ada jadwal untuk dipindai hari ini</h5>
            <p class="text-muted mb-3">Hanya jadwal hari ini yang belum selesai yang bisa dipindai. Jadwal ditentukan admin; hubungi admin bila seharusnya Anda bertugas.</p>
            <a href="<?= url('jadwal') ?>" class="btn btn-primary btn-sm">Lihat Jadwal Saya</a>
          </div>
        <?php else: ?>

        <!-- Pilih jadwal -->
        <form method="get" action="index.php" class="panel mb-4">
          <input type="hidden" name="page" value="scan">
          <label for="pilih-jadwal" class="form-label fw-semibold mb-1">Jadwal yang sedang diperiksa</label>
          <select id="pilih-jadwal" name="id" class="form-select form-select-lg" onchange="this.form.submit()">
            <?php foreach (array_slice($pilihan, 0, 30) as $j): ?>
              <option value="<?= (int) $j['id'] ?>"<?= (int) $j['id'] === (int) $jadwal['id'] ? ' selected' : '' ?>>
                <?= e($j['kota_asal'] . ' → ' . $j['kota_tujuan'] . ' · ' . tanggal_indo($j['jam_berangkat']) . ' · ' . $j['plat_nomor']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-primary mt-2">Pilih</button></noscript>
        </form>

        <div id="scan-app" class="row gy-4"
             data-endpoint="<?= e(url('validasi')) ?>"
             data-csrf="<?= e(csrf_token()) ?>"
             data-jadwal="<?= (int) $jadwal['id'] ?>">

          <!-- Kiri: kamera + input manual -->
          <div class="col-lg-6">
            <div class="panel">
              <h5><i class="bi bi-qr-code-scan me-2"></i>Pindai QR</h5>

              <div id="reader" class="scanner-box" hidden></div>
              <div id="kamera-info" class="scanner-info text-center text-muted">
                <i class="bi bi-camera-video d-block"></i>
                <div>Kamera belum aktif</div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button type="button" id="btn-kamera" class="btn btn-primary flex-fill"><i class="bi bi-camera-video me-1"></i>Mulai Kamera</button>
                <button type="button" id="btn-stop" class="btn btn-outline-secondary" hidden><i class="bi bi-stop-circle me-1"></i>Hentikan</button>
              </div>
              <p class="text-muted small mt-2 mb-0">Kamera hanya bisa dipakai lewat <strong>localhost</strong> atau <strong>HTTPS</strong>, dan perlu izin kamera dari browser.</p>

              <hr>
              <form id="form-manual" autocomplete="off">
                <label for="kode-manual" class="form-label fw-semibold">Atau ketik / tembak kode tiket</label>
                <div class="input-group">
                  <input type="text" id="kode-manual" class="form-control form-control-lg font-monospace"
                         placeholder="TKT-261014-7407E-4" maxlength="60" autocapitalize="characters" spellcheck="false" autofocus>
                  <button class="btn btn-primary" type="submit">Periksa</button>
                </div>
                <div class="form-text">Alat scanner USB bisa langsung dipakai: arahkan kursor ke kolom ini lalu tembak QR.</div>
              </form>
            </div>
          </div>

          <!-- Kanan: hasil + progres + riwayat -->
          <div class="col-lg-6">
            <div id="hasil-scan" class="hasil-scan netral" aria-live="polite">
              <div class="ikon"><i class="bi bi-upc-scan"></i></div>
              <div class="judul">Menunggu scan</div>
              <div class="pesan">Hasil pemeriksaan tiket akan tampil di sini.</div>
              <dl class="rincian" hidden></dl>
            </div>

            <div class="panel mt-4">
              <?php komponen_progress_naik($hitung['naik'], $hitung['total'], 'progres-scan'); ?>
              <a href="<?= url('manifest', ['id' => $jadwal['id']]) ?>" class="small d-inline-block mt-2"><i class="bi bi-list-check me-1"></i>Buka manifest lengkap</a>
            </div>

            <div class="panel mt-4">
              <h5>Baru saja naik</h5>
              <ul id="riwayat-list" class="riwayat-list list-unstyled mb-0">
                <?php foreach ($riwayat as $r): ?>
                  <li>
                    <span><strong><?= e($r['nama']) ?></strong> <small class="text-muted">&middot; Kursi <?= e($r['nomor_kursi']) ?></small></span>
                    <small class="text-muted"><?= e(jam($r['waktu_naik'])) ?></small>
                  </li>
                <?php endforeach; ?>
                <li id="riwayat-kosong" class="text-muted small"<?= $riwayat ? ' hidden' : '' ?>>Belum ada penumpang yang dipindai.</li>
              </ul>
            </div>
          </div>

        </div>
        <?php endif; ?>

      </div>
    </section>