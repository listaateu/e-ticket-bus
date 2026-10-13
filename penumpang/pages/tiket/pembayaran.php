<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/tiket/pembayaran.php
 * FUNGSI       : Halaman PEMBAYARAN gaya Traveloka: hitung mundur, pilih metode (QRIS / transfer bank,
 *                tanpa tunai), unggah bukti, rincian tiket di samping.
 * DIPANGGIL DARI: index.php (page=tiket&aksi=pembayaran)
 * CATATAN      : URL: index.php?page=tiket&aksi=pembayaran&id=ID_BOOKING
 *                Daftar metode & nomor rekening ada di function/pembayaran.php (DETAIL_METODE).
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

// Jam tiba & durasi
$tiba_valid = !empty($booking['jam_tiba'] ?? '') && strtotime($booking['jam_tiba']) > strtotime($booking['jam_berangkat']);
?>
    <?php komponen_stepper($status === 'lunas' ? 3 : 2); ?>

    <section id="pembayaran" class="tv-page tv-pay">
      <div class="container">

        <?php if ($bisa_bayar && batas_bayar_aktif($booking)): ?>
          <div class="tv-timer" id="banner-batas" data-sisa="<?= (int) $booking['sisa_detik'] ?>">
            <div class="teks">
              Tenang, harganya tidak akan berubah. Yuk selesaikan pembayaran dalam
              <span class="angka" id="hitung-mundur">--:--:--</span>
            </div>
            <span class="jam"><i class="bi bi-clock-fill"></i></span>
          </div>
        <?php endif; ?>

        <div class="row g-4">

          <!-- ================= Kolom kiri ================= -->
          <div class="col-lg-8">

            <?php if ($status === 'lunas'): ?>
              <div class="tv-bcard tv-status ok">
                <span class="ikon"><i class="fa-solid fa-circle-check"></i></span>
                <h4>Pembayaran Lunas</h4>
                <?php if (tiket_selesai($booking)): ?>
                  <p>Perjalanan ini sudah selesai, jadi e-ticket tidak berlaku lagi. Terima kasih sudah memilih kami.</p>
                <?php else: ?>
                  <p>Tiketmu sudah aktif. Tunjukkan e-ticket kepada kondektur sebelum naik bus.</p>
                  <a href="<?= url('e-ticket', ['id' => $id]) ?>" class="tv-cta tv-cta-inline"><i class="bi bi-qr-code me-1"></i> Lihat E-Ticket</a>
                <?php endif; ?>
              </div>

            <?php elseif ($status === 'pending'): ?>
              <div class="tv-bcard tv-status wait">
                <span class="ikon"><i class="fa-solid fa-hourglass-half"></i></span>
                <h4>Menunggu Verifikasi Admin</h4>
                <p class="mb-1">Metode: <strong><?= e($booking['metode_pembayaran']) ?></strong></p>
                <p>Bukti pembayaranmu sudah diterima. Kami akan mengabari setelah diverifikasi.</p>
                <p class="kecil"><i class="bi bi-arrow-repeat"></i> Halaman ini akan berubah sendiri begitu admin selesai memeriksa, tidak perlu di-refresh.</p>
                <?php if (!empty($booking['sudah_berangkat'])): ?>
                  <div class="alert alert-danger mt-3 mb-3">
                    <strong>Bus ini sudah berangkat.</strong> Pembayaranmu tidak akan disetujui.
                    Silakan hubungi admin untuk pengembalian dana.
                  </div>
                <?php endif; ?>
                <?php if ($booking['bukti_transfer']): ?>
                  <a href="<?= e(URL_BUKTI . $booking['bukti_transfer']) ?>" class="glightbox tv-btn-outline">Lihat Bukti</a>
                <?php endif; ?>
              </div>

            <?php else: ?>
              <?php if ($status === 'gagal'): ?>
                <div class="tv-alert-tolak">
                  <i class="bi bi-exclamation-octagon-fill"></i>
                  <div>
                    <strong>Pembayaran sebelumnya ditolak admin.</strong>
                    <?php if (!empty($booking['alasan_tolak'])): ?>
                      <div>Alasan: <?= e($booking['alasan_tolak']) ?></div>
                    <?php endif; ?>
                    <div>Silakan unggah bukti yang benar.</div>
                  </div>
                </div>
              <?php endif; ?>

              <form method="post" enctype="multipart/form-data" id="form-bayar">
                <?= csrf_field() ?>

                <div class="tv-bcard tv-metode-card">
                  <div class="tv-pay-title">
                    <h2>Mau bayar pakai metode apa?</h2>
                    <span class="aman"><i class="bi bi-shield-check"></i> Pembayaran aman</span>
                  </div>

                  <?php
                  $per_grup = [];
                  foreach (DETAIL_METODE as $nama => $d) { $per_grup[$d['grup']][$nama] = $d; }
                  $urut = 0;
                  foreach (GRUP_METODE as $kode_grup => $judul_grup):
                      if (empty($per_grup[$kode_grup])) continue;
                  ?>
                    <div class="tv-grup-judul"><?= e($judul_grup) ?></div>

                    <?php foreach ($per_grup[$kode_grup] as $nama => $d): $urut++; $jenis = jenis_metode($nama); ?>
                      <label class="tv-metode">
                        <input type="radio" name="metode" value="<?= e($nama) ?>" data-jenis="<?= e($jenis) ?>" <?= $urut === 1 ? 'checked' : '' ?> required>
                        <span class="tv-metode-baris">
                          <span class="tv-metode-ikon" style="background:<?= e($d['warna']) ?>"><i class="fa-solid <?= e($d['ikon']) ?>"></i></span>
                          <span class="tv-metode-teks">
                            <strong><?= e($d['label']) ?>
                              <?php if (!empty($d['badge'])): ?><em class="tv-badge-hijau"><?= e($d['badge']) ?></em><?php endif; ?>
                            </strong>
                            <small><?= e($d['sub']) ?></small>
                          </span>
                          <span class="tv-radio"></span>
                        </span>

                        <!-- Panel yang terbuka saat metode ini dipilih -->
                        <span class="tv-metode-panel">
                          <?php if ($jenis === 'qris'): ?>
                            <span class="tv-panel-isi tv-qris">
                              <?php if (is_file(FILE_QRIS)): ?>
                                <img src="<?= e(URL_QRIS) ?>?v=<?= (int) filemtime(FILE_QRIS) ?>" alt="Kode QRIS">
                              <?php else: ?>
                                <span class="tv-qris-kosong"><i class="bi bi-qr-code-scan"></i>Gambar QRIS belum dipasang.<br>Hubungi admin atau pilih metode lain.</span>
                              <?php endif; ?>
                              <span class="tv-langkah">
                                <b>1</b> Buka aplikasi e-wallet atau m-banking<br>
                                <b>2</b> Scan kode QR di samping, lalu masukkan nominal <strong>tepat</strong> seperti di bawah<br>
                                <b>3</b> Simpan screenshot buktinya, lalu unggah di bawah
                              </span>
                            </span>
                          <?php else: ?>
                            <span class="tv-panel-isi">
                              <span class="tv-kv">
                                <small>Transfer ke <?= e($d['bank']) ?></small>
                                <span class="nilai">
                                  <strong class="mono"><?= e($d['rek']) ?></strong>
                                  <button type="button" class="tv-salin" data-salin="<?= e($d['rek']) ?>"><i class="bi bi-copy"></i> Salin</button>
                                </span>
                                <small>a.n. <?= e($d['an']) ?></small>
                              </span>
                            </span>
                          <?php endif; ?>

                          <span class="tv-panel-isi">
                            <span class="tv-kv nominal">
                              <small>Nominal yang harus dibayar</small>
                              <span class="nilai">
                                <strong class="oren"><?= e(rupiah($total_transfer)) ?></strong>
                                <button type="button" class="tv-salin" data-salin="<?= (int) $total_transfer ?>"><i class="bi bi-copy"></i> Salin</button>
                              </span>
                              <?php if ($kode_unik > 0): ?>
                                <small class="ket">Bayar <b>tepat sampai 3 angka terakhir</b> (sudah termasuk kode unik <?= e(rupiah($kode_unik)) ?>) supaya cepat dicocokkan admin.</small>
                              <?php endif; ?>
                            </span>
                          </span>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </div>

                <!-- Unggah bukti -->
                <div class="tv-bcard">
                  <div class="tv-bcard-head">
                    <span class="tv-bicon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                    <div>
                      <div class="tv-btitle">Unggah Bukti Pembayaran</div>
                      <div class="tv-bsub">Screenshot atau foto struk. JPG / PNG / WEBP, maksimal 2 MB.</div>
                    </div>
                  </div>

                  <label class="tv-drop" for="input-bukti" id="area-drop">
                    <input type="file" name="bukti" id="input-bukti" accept="image/jpeg,image/png,image/webp" required>
                    <span class="tv-drop-isi" id="drop-kosong">
                      <i class="bi bi-image"></i>
                      <strong>Pilih gambar bukti</strong>
                      <small>atau tarik &amp; lepas ke sini</small>
                    </span>
                    <img id="preview-bukti" class="tv-drop-preview d-none" alt="Pratinjau bukti">
                    <span class="tv-drop-nama d-none" id="nama-bukti"></span>
                  </label>
                </div>

                <!-- Total + tombol bayar -->
                <div class="tv-bcard tv-total-card">
                  <button type="button" class="tv-total-baris" id="btn-rincian" aria-expanded="false" aria-controls="rincian-total">
                    <span>Harga Total</span>
                    <span class="nilai"><?= e(rupiah($total_transfer)) ?> <i class="bi bi-chevron-down"></i></span>
                  </button>
                  <div class="tv-rincian" id="rincian-total" hidden>
                    <div><span>Tiket <?= e($booking['nama_bus']) ?> · Dewasa x1</span><span><?= e(rupiah($booking['harga'])) ?></span></div>
                    <?php if ($kode_unik > 0): ?>
                      <div><span>Kode unik</span><span><?= e(rupiah($kode_unik)) ?></span></div>
                    <?php endif; ?>
                  </div>

                  <button type="submit" class="tv-cta" id="btn-kirim">Kirim Bukti Pembayaran</button>
                  <p class="tv-syarat">
                    <i class="bi bi-lock-fill"></i>
                    Setelah dikirim, admin akan memeriksa buktimu. E-ticket langsung aktif begitu pembayaran disetujui.
                  </p>
                  <p class="tv-syarat text-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Selesaikan pembayaran dalam <?= e(teks_durasi_bayar()) ?>. Setelah waktu habis, pesanan dibatalkan otomatis dan bukti tidak bisa dikirim lagi.
                    Jangan transfer setelah itu, karena uangnya tidak dapat dikembalikan.
                  </p>
                </div>
              </form>
            <?php endif; ?>
          </div>

          <!-- ================= Kolom kanan: rincian tiket ================= -->
          <div class="col-lg-4">
            <div class="tv-rincian-tiket">
              <div class="tv-rt-head">
                <span class="ikon"><i class="fa-solid fa-bus"></i></span>
                <div>
                  <strong>Rincian Tiket Bus</strong>
                  <small>No. Pesanan <b><?= e($booking['kode_booking']) ?></b></small>
                </div>
                <span class="ms-auto"><?= komponen_badge_status($status) ?></span>
              </div>

              <div class="tv-rt-sec">
                <div class="label">Detail bus</div>
                <div class="op"><?= e($booking['nama_bus']) ?>
                  <span class="tv-kelas <?= e(strtolower(preg_replace('/[^a-z]/i', '', (string) $booking['kelas']))) ?>"><?= e($booking['kelas']) ?></span>
                </div>
                <div class="rute"><?= e($booking['kota_asal']) ?> <i class="bi bi-arrow-right"></i> <?= e($booking['kota_tujuan']) ?></div>
                <div class="tgl"><?= e(tanggal_indo($booking['jam_berangkat'], false)) ?></div>
                <div class="jam">
                  <?= e(jam($booking['jam_berangkat'])) ?><?= $tiba_valid ? ' - ' . e(jam($booking['jam_tiba'])) : '' ?> WIB
                </div>
              </div>

              <div class="tv-rt-sec">
                <div class="label">Penumpang</div>
                <div class="tv-rt-pax">
                  <span><?= e($booking['nama_penumpang']) ?></span>
                  <span class="jenis">Dewasa</span>
                </div>
                <div class="tv-rt-pax">
                  <span>Nomor kursi</span>
                  <span class="kursi"><?= e($booking['nomor_kursi']) ?></span>
                </div>
              </div>
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
          // Hapus form + nomor rekening + QRIS saat itu juga, supaya penumpang tidak sempat transfer
          var form = document.getElementById('form-bayar');
          if (form) {
            form.innerHTML = '<div class="tv-bcard text-center p-4">'
              + '<h4>Waktu pembayaran habis</h4>'
              + '<p>Pesanan ini dibatalkan otomatis. Jangan transfer lagi, karena pembayaran tidak bisa diproses dan tidak dapat dikembalikan.</p>'
              + '<a href="' + <?= json_encode(url('jadwal'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?> + '" class="tv-cta tv-cta-inline">Pesan Kursi Lagi</a>'
              + '</div>';
          }
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