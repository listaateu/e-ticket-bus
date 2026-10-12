<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/tiket/pembayaran.php
 * FUNGSI       : Halaman PEMBAYARAN: ringkasan pesanan, pilih metode, upload bukti.
 * DIPANGGIL DARI: index.php (page=tiket&aksi=pembayaran)
 * CATATAN      : URL: index.php?page=tiket&aksi=pembayaran&id=ID_BOOKING
 * ============================================================
 */
$id      = (int) ($_GET['id'] ?? 0);
$booking = get_booking($conn, $id, $user_id);
if (!$booking) {
    set_flash('gagal', 'Pemesanan tidak ditemukan, atau sudah dibatalkan karena melewati batas waktu pembayaran.');
    redirect(url('tiket-saya'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        set_flash('gagal', 'Sesi tidak valid, silakan ulangi.');
        redirect(url('pembayaran', ['id' => $id]));
    }
    [$ok, $pesan] = simpan_pembayaran($conn, $booking, $_POST['metode'] ?? '', $_FILES['bukti'] ?? []);
    set_flash($ok ? 'sukses' : 'gagal', $pesan);
    redirect(url('pembayaran', ['id' => $id]));
}

$status         = status_tiket($booking);
$bisa_bayar     = in_array($status, ['belum_bayar', 'gagal'], true);
$kode_unik      = (int) ($booking['kode_unik'] ?? 0);   // 0 untuk pemesanan lama
$total_transfer = total_bayar($booking);                // harga tiket + kode unik
?>
    <?php komponen_page_title('Pembayaran', 'Kode booking ' . $booking['kode_booking'], ['Tiket Saya' => url('tiket-saya'), 'Pembayaran' => null]); ?>

    <section id="pembayaran" class="section">
      <div class="container">
        <div class="row gy-4">

          <div class="col-lg-5" data-aos="fade-up">
            <div class="panel">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Detail Pesanan</h5>
                <?= komponen_badge_status($status) ?>
              </div>
              <dl class="detail-list">
                <dt>Kode Booking</dt><dd class="fw-bold"><?= e($booking['kode_booking']) ?></dd>
                <dt>Penumpang</dt><dd><?= e($booking['nama_penumpang']) ?></dd>
                <dt>Rute</dt><dd><?= e($booking['kota_asal']) ?> → <?= e($booking['kota_tujuan']) ?></dd>
                <dt>Berangkat</dt><dd><?= e(tanggal_indo($booking['jam_berangkat'])) ?></dd>
                <dt>Bus</dt><dd><?= e($booking['nama_bus']) ?> (<?= e($booking['kelas']) ?>)</dd>
                <dt>Nomor Kursi</dt><dd class="fw-bold"><?= e($booking['nomor_kursi']) ?></dd>
              </dl>
              <hr>
              <?php if ($kode_unik > 0): ?>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Harga tiket</span>
                  <span><?= e(rupiah($booking['harga'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Kode unik</span>
                  <span><?= e(rupiah($kode_unik)) ?></span>
                </div>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center">
                <span>Total Bayar</span>
                <span class="total-bayar"><?= e(rupiah($total_transfer)) ?></span>
              </div>
              <?php if ($kode_unik > 0): ?>
                <small class="text-muted d-block mt-2">
                  Kode unik adalah angka tambahan (maksimal Rp999) agar pembayaranmu
                  mudah dikenali admin dan cepat diverifikasi.
                </small>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
            <div class="panel">
              <?php if ($status === 'lunas'): ?>
                <div class="text-center py-4">
                  <i class="fa-solid fa-circle-check status-ikon text-success"></i>
                  <h5 class="mt-3">Pembayaran Lunas</h5>
                  <p class="text-muted">Tiketmu sudah aktif. Tunjukkan e-ticket kepada kondektur.</p>
                  <a href="<?= url('e-ticket', ['id' => $id]) ?>" class="btn btn-primary"><i class="bi bi-qr-code me-1"></i> Lihat E-Ticket</a>
                </div>

              <?php elseif ($status === 'pending'): ?>
                <div class="text-center py-4">
                  <i class="fa-solid fa-hourglass-half status-ikon text-warning"></i>
                  <h5 class="mt-3">Menunggu Verifikasi Admin</h5>
                  <p class="text-muted mb-1">Metode: <strong><?= e($booking['metode_pembayaran']) ?></strong></p>
                  <p class="text-muted">Bukti pembayaranmu sudah diterima. Kami akan mengabari setelah diverifikasi.</p>
                  <p class="small text-muted"><i class="bi bi-arrow-repeat"></i> Halaman ini akan berubah sendiri begitu admin selesai memeriksa, tidak perlu di-refresh.</p>
                  <?php if ($booking['bukti_transfer']): ?>
                    <a href="<?= e(URL_BUKTI . $booking['bukti_transfer']) ?>" class="glightbox btn btn-outline-secondary btn-sm">Lihat Bukti</a>
                  <?php endif; ?>
                </div>

              <?php else: ?>
                <h5 class="mb-3">Pilih Metode Pembayaran</h5>
                <?php if (batas_bayar_aktif($booking)): ?>
                  <div class="alert alert-warning d-flex align-items-start gap-2" id="banner-batas" data-sisa="<?= (int) $booking['sisa_detik'] ?>">
                    <i class="bi bi-clock-history fs-5"></i>
                    <div>
                      Selesaikan pembayaran sebelum <strong><?= e(tanggal_indo($booking['batas_bayar'])) ?></strong>.
                      Sisa waktu: <strong id="hitung-mundur">--:--:--</strong>
                      <div class="small mt-1">
                        Lewat dari batas ini, pesanan dibatalkan otomatis dan kursi <?= e($booking['nomor_kursi']) ?>
                        dibuka lagi untuk penumpang lain.
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if ($status === 'gagal'): ?>
                  <div class="alert alert-danger">
                    <strong>Pembayaran sebelumnya ditolak admin.</strong>
                    <?php if (!empty($booking['alasan_tolak'])): ?>
                      <div class="mt-1">Alasan: <?= e($booking['alasan_tolak']) ?></div>
                    <?php endif; ?>
                    <div class="mt-1">Silakan unggah bukti yang benar.</div>
                  </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="form-bayar">
                  <?= csrf_field() ?>
                  <div class="metode-list mb-3">
                    <?php $n = 0; foreach (METODE_BAYAR as $nama => $rek): $n++; ?>
                      <label class="metode">
                        <input type="radio" name="metode" value="<?= e($nama) ?>" data-rek="<?= e($rek) ?>" <?= $n === 1 ? 'checked' : '' ?> required>
                        <span><?= e($nama) ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>

                  <div class="info-rek mb-3">
                    <small class="text-muted d-block">Kirim pembayaran ke:</small>
                    <strong id="teks-rek"><?= e(array_values(METODE_BAYAR)[0]) ?></strong>
                    <div>Nominal: <strong><?= e(rupiah($total_transfer)) ?></strong></div>
                    <?php if ($kode_unik > 0): ?>
                      <small class="text-muted d-block mt-1">
                        Transfer <strong>tepat sesuai nominal ini</strong> (termasuk 3 angka terakhir)
                        supaya pembayaranmu cepat dicocokkan.
                      </small>
                    <?php endif; ?>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Unggah Bukti Pembayaran</label>
                    <input type="file" name="bukti" id="input-bukti" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-text">JPG / PNG / WEBP, maksimal 2 MB.</div>
                    <img id="preview-bukti" class="preview-bukti d-none" alt="Pratinjau bukti">
                  </div>

                  <button type="submit" class="btn btn-primary w-100">Kirim Bukti Pembayaran</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </section>

    <?php if (batas_bayar_aktif($booking)): ?>
    <!-- Hitung mundur batas bayar. Saat habis, halaman dimuat ulang sekali: pesanan sudah dibatalkan server. -->
    <script>
    (function () {
      var banner = document.getElementById('banner-batas');
      var teks   = document.getElementById('hitung-mundur');
      if (!banner || !teks) return;

      var tujuan = Date.now() + parseInt(banner.getAttribute('data-sisa'), 10) * 1000;
      var kunci  = 'muat-ulang-batas-<?= (int) $booking['id'] ?>';
      var timer;

      function dua(n) { return (n < 10 ? '0' : '') + n; }

      function tick() {
        var detik = Math.max(0, Math.round((tujuan - Date.now()) / 1000));
        teks.textContent = dua(Math.floor(detik / 3600)) + ':' + dua(Math.floor((detik % 3600) / 60)) + ':' + dua(detik % 60);
        if (detik <= 0) {
          clearInterval(timer);
          teks.textContent = 'Waktu habis';
          try {
            // Pengaman: muat ulang hanya sekali, supaya tidak berulang kalau server belum membatalkan
            if (sessionStorage.getItem(kunci)) return;
            sessionStorage.setItem(kunci, '1');
          } catch (e) {}
          setTimeout(function () { location.reload(); }, 1500);
        }
      }

      timer = setInterval(tick, 1000);
      tick();
    })();
    </script>
    <?php endif; ?>

    <?php if ($status === 'pending'): ?>
    <!-- Menunggu verifikasi: tanya server tiap 5 detik. Begitu admin memutuskan, tampilkan pemberitahuan lalu muat ulang. -->
    <script>
    (function () {
      var id        = '<?= (int) $booking['id'] ?>';
      var alamat    = <?= json_encode(url('tiket-saya', ['aksi' => 'cek_status']), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
      var tiketSaya = <?= json_encode(url('tiket-saya'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
      var selesai   = false;

      function toast(teks, jenis) {
        var d = document.createElement('div');
        d.className = 'alert alert-' + jenis + ' shadow';
        d.style.cssText = 'position:fixed;top:90px;left:50%;transform:translateX(-50%);z-index:2000;min-width:280px;max-width:90%;text-align:center;font-weight:600';
        d.textContent = teks;
        document.body.appendChild(d);
      }

      function cek() {
        if (selesai || document.hidden) return;   // tab tidak sedang dilihat: tidak perlu bertanya
        fetch(alamat, { credentials: 'same-origin', cache: 'no-store' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var st = (data.status || {})[id] || 'hilang';
            if (st === 'pending' || selesai) return;
            selesai = true;

            if (st === 'lunas')      toast('Pembayaran berhasil! Tiketmu sudah aktif.', 'success');
            else if (st === 'gagal') toast('Pembayaran ditolak admin. Lihat alasannya di halaman ini.', 'danger');
            else                     toast('Pesanan ini sudah dibatalkan.', 'secondary');

            setTimeout(function () {
              if (st === 'hilang') location.href = tiketSaya;
              else location.reload();
            }, 1800);
          })
          .catch(function () { /* koneksi putus sebentar: coba lagi di putaran berikutnya */ });
      }

      setInterval(cek, 5000);
      document.addEventListener('visibilitychange', cek);   // balik ke tab ini -> langsung cek
    })();
    </script>
    <?php endif; ?>