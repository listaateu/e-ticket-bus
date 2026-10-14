<?php
/**
 * ============================================================
 * FILE         : sopir/pages/jadwal/manifest.php
 * FUNGSI       : MANIFEST penumpang satu jadwal: nama, kursi, pemesan, kode tiket, dan status naik.
 *                Hanya penumpang dengan pembayaran LUNAS yang tampil. Halaman ini HANYA-LIHAT: status naik
 *                hanya berubah lewat halaman Scan Tiket (QR atau ketik kode), tidak bisa diubah manual di sini.
 * DIPANGGIL DARI: sopir/index.php (page=jadwal&aksi=manifest)
 * CATATAN      : URL: index.php?page=jadwal&aksi=manifest&id=ID_JADWAL
 *                Gaya tampilan (sp-ringkas, sp-filter, pil, dst) ada di assets/css/sopir.css.
 * ============================================================
 */
$id     = (int) ($_GET['id'] ?? 0);
$jadwal = get_jadwal_sopir($conn, $id, $sopir['nama']);   // null bila bukan jadwal milik sopir ini
if (!$jadwal) {
    set_flash('gagal', 'Jadwal tidak ditemukan atau bukan jadwal Anda.');
    redirect(url('jadwal'));
}

$manifest    = manifest_jadwal($conn, $id);
$dewasa      = array_values(array_filter($manifest, fn($p) => $p['tipe'] === 'dewasa'));
$bayi        = array_values(array_filter($manifest, fn($p) => $p['tipe'] === 'bayi'));
$hitung      = hitung_naik($conn, $id);
$belum_lunas = jumlah_belum_lunas($conn, $id);
$selesai     = !empty($jadwal['sudah_tiba']);
[$kelas_status, $label_status] = status_jadwal($jadwal);
$belum_naik  = max(0, (int) $hitung['total'] - (int) $hitung['naik']);
?>
    <!-- Banner -->
    <section class="sp-hero sp-hero-kecil d-print-none">
      <div class="container position-relative">
        <ol class="sp-crumb">
          <li><a href="<?= url('beranda') ?>">Beranda</a></li>
          <li><a href="<?= url('jadwal') ?>">Jadwal Saya</a></li>
          <li>Manifest</li>
        </ol>
        <h2>Manifest Penumpang</h2>
        <p><?= e($jadwal['kota_asal']) ?> &rarr; <?= e($jadwal['kota_tujuan']) ?> &middot; <?= e(tanggal_indo($jadwal['jam_berangkat'])) ?></p>
      </div>
    </section>

    <section id="manifest" class="section">
      <div class="container">

        <!-- Judul khusus cetak -->
        <h4 class="d-none d-print-block mb-3">Manifest Penumpang &mdash; <?= e($jadwal['kota_asal']) ?> &rarr; <?= e($jadwal['kota_tujuan']) ?>, <?= e(tanggal_indo($jadwal['jam_berangkat'])) ?></h4>

        <!-- Ringkasan jadwal -->
        <div class="panel sp-ringkas mb-4">
          <div class="row gy-4 align-items-center">
            <div class="col-lg-7">
              <span class="badge bg-<?= $kelas_status ?> status-badge mb-2"><?= e($label_status) ?></span>
              <div class="rute fs-3"><?= e($jadwal['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= e($jadwal['kota_tujuan']) ?></div>
              <div class="text-muted">
                <i class="bi bi-clock me-1"></i><?= e(tanggal_indo($jadwal['jam_berangkat'])) ?><?php if (!empty($jadwal['jam_tiba'])): ?> &middot; tiba <?= e(jam($jadwal['jam_tiba'])) ?><?php endif; ?>
              </div>
              <div class="mt-1">
                <i class="bi bi-bus-front text-muted me-1"></i><?= e($jadwal['nama_bus']) ?> &middot; <?= e($jadwal['kelas']) ?> &middot; <strong><?= e($jadwal['plat_nomor']) ?></strong>
              </div>

              <div class="sp-mini-grid">
                <div class="sp-mini"><div class="angka"><?= (int) $hitung['total'] ?></div><div class="label">Penumpang</div></div>
                <div class="sp-mini hijau"><div class="angka"><?= (int) $hitung['naik'] ?></div><div class="label">Sudah naik</div></div>
                <div class="sp-mini oranye"><div class="angka"><?= $belum_naik ?></div><div class="label">Belum naik</div></div>
              </div>
            </div>

            <div class="col-lg-5">
              <?php komponen_progress_naik($hitung['naik'], $hitung['total']); ?>
              <?php if ($bayi): ?>
                <small class="text-muted d-block mt-1"><i class="bi bi-person-arms-up"></i> + <?= count($bayi) ?> bayi (dipangku, ikut penumpang dewasa)</small>
              <?php endif; ?>
              <div class="d-grid gap-2 mt-3 d-print-none">
                <?php if (!$selesai): ?>
                  <a href="<?= url('scan', ['id' => $id]) ?>" class="btn btn-primary"><i class="bi bi-qr-code-scan me-1"></i>Scan Tiket</a>
                <?php endif; ?>
                <div class="d-flex gap-2">
                  <a href="<?= url('manifest', ['id' => $id]) ?>" class="btn btn-outline-secondary btn-sm flex-fill"><i class="bi bi-arrow-clockwise me-1"></i>Muat ulang</a>
                  <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php if ($belum_lunas > 0): ?>
          <div class="alert alert-warning py-2 d-print-none">
            <i class="bi bi-info-circle me-1"></i>
            Ada <?= $belum_lunas ?> pemesanan di jadwal ini yang belum lunas. Penumpangnya baru masuk manifest setelah admin memverifikasi pembayaran.
          </div>
        <?php endif; ?>

        <?php if ($dewasa): ?>
          <!-- Daftar penumpang dewasa -->
          <div class="panel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
              <h5 class="mb-0">Penumpang dewasa <span class="text-muted fw-normal">(<?= count($dewasa) ?>)</span></h5>
              <div class="cari-wrap d-print-none">
                <i class="bi bi-search"></i>
                <input type="search" id="cari-manifest" class="form-control form-control-sm cari-manifest"
                       placeholder="Cari nama, kursi, atau kode tiket..." autocomplete="off">
              </div>
            </div>

            <div class="sp-filter mb-3 d-print-none">
              <button type="button" class="aktif" data-filter="semua">Semua (<?= count($dewasa) ?>)</button>
              <button type="button" data-filter="belum">Belum naik (<?= $belum_naik ?>)</button>
              <button type="button" data-filter="naik">Sudah naik (<?= (int) $hitung['naik'] ?>)</button>
            </div>

            <div class="table-responsive">
              <table class="table align-middle tabel-manifest mb-0">
                <thead>
                  <tr>
                    <th>Kursi</th>
                    <th>Penumpang</th>
                    <th class="d-none d-md-table-cell">Pemesan</th>
                    <th class="d-none d-lg-table-cell">Kode tiket</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dewasa as $p): $naik = (int) $p['status_naik'] === 1; ?>
                    <tr class="<?= $naik ? 'sudah-naik' : '' ?>"
                        data-cari="<?= e($p['nama'] . ' ' . $p['pemesan'] . ' ' . $p['nomor_kursi'] . ' ' . $p['kode_tiket'] . ' ' . $p['kode_booking']) ?>">
                      <td><span class="kursi-no"><?= e($p['nomor_kursi']) ?></span></td>
                      <td>
                        <div class="fw-semibold"><?= e($p['nama']) ?></div>
                        <small class="text-muted d-md-none">Pemesan: <?= e($p['pemesan']) ?></small>
                      </td>
                      <td class="d-none d-md-table-cell">
                        <?= e($p['pemesan']) ?><br><small class="text-muted font-monospace"><?= e($p['kode_booking']) ?></small>
                      </td>
                      <td class="d-none d-lg-table-cell font-monospace small">
                        <?php if (!empty($p['kode_tiket'])): ?>
                          <?= e($p['kode_tiket']) ?>
                        <?php else: ?>
                          <span class="text-muted" title="Kode dibuat saat penumpang membuka e-ticket">belum dibuat</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($naik): ?>
                          <span class="pil hijau"><i class="bi bi-check-circle-fill"></i>Sudah naik<?= !empty($p['waktu_naik']) ? ' · ' . e(jam($p['waktu_naik'])) : '' ?></span>
                          <?php if (!empty($p['pemeriksa'])): ?><small class="d-block text-muted mt-1">oleh <?= e($p['pemeriksa']) ?></small><?php endif; ?>
                        <?php else: ?>
                          <span class="pil abu"><i class="bi bi-hourglass-split"></i>Belum naik</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <tr id="cari-kosong" hidden><td colspan="5" class="text-center text-muted py-4">Tidak ada penumpang yang cocok.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <?php if ($bayi): ?>
            <div class="panel mt-4">
              <h5>Bayi (<?= count($bayi) ?>)</h5>
              <p class="text-muted small mb-2">Bayi dipangku, tanpa kursi dan tanpa QR. Ikut penumpang dewasa pada pemesanan yang sama.</p>
              <ul class="list-unstyled mb-0">
                <?php foreach ($bayi as $b): ?>
                  <li class="py-1"><i class="bi bi-person-arms-up text-muted me-2"></i><strong><?= e($b['nama']) ?></strong> <small class="text-muted">&middot; pemesan <?= e($b['pemesan']) ?> (<?= e($b['kode_booking']) ?>)</small></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <script>
            // Filter Semua / Belum naik / Sudah naik (terpisah dari kotak cari, jadi keduanya bisa dipakai bersamaan)
            document.addEventListener('DOMContentLoaded', function () {
              var tombol = document.querySelectorAll('.sp-filter [data-filter]');
              var baris  = document.querySelectorAll('.tabel-manifest tbody tr[data-cari]');
              tombol.forEach(function (t) {
                t.addEventListener('click', function () {
                  tombol.forEach(function (x) { x.classList.remove('aktif'); });
                  t.classList.add('aktif');
                  var f = t.getAttribute('data-filter');
                  baris.forEach(function (r) {
                    var naik = r.classList.contains('sudah-naik');
                    var sembunyi = (f === 'naik' && !naik) || (f === 'belum' && naik);
                    if (sembunyi) { r.setAttribute('data-sembunyi', '1'); } else { r.removeAttribute('data-sembunyi'); }
                  });
                });
              });
            });
          </script>

        <?php else: ?>
          <div class="kosong-manifest">
            <div class="lingkar"><i class="bi bi-people"></i></div>
            <h5>Belum ada penumpang lunas</h5>
            <p class="text-muted mb-4">Penumpang muncul di sini setelah admin memverifikasi pembayaran mereka.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2 d-print-none">
              <a href="<?= url('jadwal') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali ke Jadwal Saya</a>
              <a href="<?= url('manifest', ['id' => $id]) ?>" class="btn btn-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Muat ulang</a>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </section>