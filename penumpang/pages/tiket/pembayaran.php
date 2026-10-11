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
    set_flash('gagal', 'Pemesanan tidak ditemukan.');
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

$status = status_tiket($booking);
$bisa_bayar = in_array($status, ['belum_bayar', 'gagal'], true);
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
              <div class="d-flex justify-content-between align-items-center">
                <span>Total Bayar</span>
                <span class="total-bayar"><?= e(rupiah($booking['harga'])) ?></span>
              </div>
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
                  <?php if ($booking['bukti_transfer']): ?>
                    <a href="<?= e(URL_BUKTI . $booking['bukti_transfer']) ?>" class="glightbox btn btn-outline-secondary btn-sm">Lihat Bukti</a>
                  <?php endif; ?>
                </div>

              <?php else: ?>
                <h5 class="mb-3">Pilih Metode Pembayaran</h5>
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
                    <div>Nominal: <strong><?= e(rupiah($booking['harga'])) ?></strong></div>
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