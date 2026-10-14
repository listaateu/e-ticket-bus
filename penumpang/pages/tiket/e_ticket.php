<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/tiket/e_ticket.php
 * FUNGSI       : Halaman E-TICKET (hanya bila status pembayaran lunas).
 *                Satu QR untuk SETIAP penumpang dewasa. Bayi tidak punya QR.
 * DIPANGGIL DARI: index.php (page=tiket&aksi=e_ticket)
 * CATATAN      : URL: index.php?page=tiket&aksi=e_ticket&id=ID_BOOKING
 * ============================================================
 */
$id      = (int) ($_GET['id'] ?? 0);
$booking = get_booking($conn, $id, $user_id);
if (!$booking) {
    set_flash('gagal', 'Tiket tidak ditemukan.');
    redirect(url('tiket-saya'));
}
if (status_tiket($booking) !== 'lunas') {
    set_flash('info', 'E-ticket tersedia setelah pembayaran diverifikasi admin.');
    redirect(url('pembayaran', ['id' => $id]));
}
if (tiket_selesai($booking)) {
    set_flash('info', 'Perjalanan ini sudah selesai, e-ticket tidak berlaku lagi.');
    redirect(url('tiket-saya'));
}
$pakai_qr = true;
$penumpang = daftar_penumpang_booking($conn, (int) $booking['id']);

// ---- Nama sopir/kondektur (kolom `sopir` di tabel schedules, dipilih admin) ----
// Kalau get_booking() sudah membawa kolom `sopir`, pakai itu. Kalau belum, ambil dari schedules.
$nama_sopir = trim((string) ($booking['sopir'] ?? ''));
if ($nama_sopir === '') {
    $id_jadwal = (int) ($booking['schedule_id'] ?? $booking['jadwal_id'] ?? 0);
    if ($id_jadwal > 0) {
        $q_sopir = $conn->prepare("SELECT sopir FROM schedules WHERE id = ? LIMIT 1");
        $q_sopir->bind_param('i', $id_jadwal);
        $q_sopir->execute();
        $baris_sopir = $q_sopir->get_result()->fetch_assoc();
        $nama_sopir = trim((string) ($baris_sopir['sopir'] ?? ''));
    }
}
if ($nama_sopir === '') {
    $nama_sopir = 'Belum ditentukan';
}

// ---- Kode tiket per penumpang dewasa ----
// Kalau ada dewasa yang belum punya kode_tiket (pemesanan baru), isi otomatis di sini.
$kode_booking = (string) $booking['kode_booking'];
$booking_id   = (int) $booking['id'];

$isi = $conn->prepare(
    "UPDATE booking_penumpang
     SET kode_tiket = CONCAT(?, '-', id)
     WHERE booking_id = ? AND tipe = 'dewasa' AND kode_tiket IS NULL"
);
$isi->bind_param('si', $kode_booking, $booking_id);
$isi->execute();

$ambil = $conn->prepare(
    "SELECT id, nama, tipe, nomor_kursi, kode_tiket, status_naik
     FROM booking_penumpang
     WHERE booking_id = ?
     ORDER BY (tipe = 'bayi'), id"
);
$ambil->bind_param('i', $booking_id);
$ambil->execute();
$daftar_qr = $ambil->get_result()->fetch_all(MYSQLI_ASSOC);

$dewasa_qr = array_values(array_filter($daftar_qr, fn($p) => $p['tipe'] === 'dewasa' && !empty($p['kode_tiket'])));
$bayi_qr   = array_values(array_filter($daftar_qr, fn($p) => $p['tipe'] === 'bayi'));
?>
    <?php komponen_page_title('E-Ticket', 'Tunjukkan QR masing-masing penumpang kepada kondektur.', ['Tiket Saya' => url('tiket-saya'), 'E-Ticket' => null]); ?>

    <style>
      .qr-kartu { border: 1px solid #e1e6f5; border-radius: 14px; padding: 16px; margin-bottom: 14px; text-align: center; }
      .qr-kartu .nm { font-weight: 700; color: var(--heading-color); }
      .qr-kartu .ks { color: #0194f3; font-weight: 700; font-size: 14px; }
      .qr-kartu .qr-pax { display: inline-block; padding: 10px; margin: 10px 0 6px; background: #fff; border: 1px solid #e1e6f5; border-radius: 12px; }
      .qr-kartu .kode { font-family: monospace; font-size: 14px; font-weight: 700; letter-spacing: .5px; color: var(--heading-color); }
      .qr-kartu.sudah-naik { opacity: .6; }
      .qr-bayi { font-size: 13px; color: #687176; text-align: center; margin-top: 4px; }
      @media print { .qr-kartu { break-inside: avoid; } }
    </style>

    <section id="e-ticket" class="section">
      <div class="container">
        <div class="ticket mx-auto" data-aos="zoom-in">
          <div class="ticket-atas">
            <div class="d-flex justify-content-between align-items-center">
              <span class="merek"><i class="fa-solid fa-bus me-2"></i>E-Ticket Bus</span>
              <span class="badge bg-success">LUNAS</span>
            </div>
            <div class="rute-besar mt-3">
              <div><small>Dari</small><strong><?= e($booking['kota_asal']) ?></strong></div>
              <i class="bi bi-arrow-right"></i>
              <div class="text-end"><small>Ke</small><strong><?= e($booking['kota_tujuan']) ?></strong></div>
            </div>
          </div>

          <div class="ticket-tengah">
            <div class="row gy-3">
              <div class="col-12">
                <small>Penumpang (<?= e(teks_penumpang(jumlah_dewasa($booking), (int) ($booking['jumlah_bayi'] ?? 0))) ?>)</small>
                <ul class="tiket-pax mt-1">
                  <?php if ($penumpang): ?>
                    <?php foreach ($penumpang as $px): ?>
                      <li>
                        <span class="nm"><?= e($px['nama']) ?><small><?= $px['tipe'] === 'bayi' ? 'Bayi · dipangku, tanpa kursi' : 'Dewasa' ?></small></span>
                        <?php if ($px['tipe'] === 'dewasa'): ?><span class="ks">Kursi <?= e($px['nomor_kursi']) ?></span><?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li><span class="nm"><?= e($booking['nama_penumpang']) ?><small>Dewasa</small></span><span class="ks">Kursi <?= e($booking['nomor_kursi']) ?></span></li>
                  <?php endif; ?>
                </ul>
              </div>
              <div class="col-6"><small>Berangkat</small><div class="nilai"><?= e(tanggal_indo($booking['jam_berangkat'], false)) ?></div></div>
              <div class="col-6"><small>Jam</small><div class="nilai"><?= e(jam($booking['jam_berangkat'])) ?> WIB</div></div>
              <div class="col-6"><small>Bus</small><div class="nilai"><?= e($booking['nama_bus']) ?> · <?= e($booking['kelas']) ?></div></div>
              <div class="col-6"><small>Plat Nomor</small><div class="nilai"><?= e($booking['plat_nomor']) ?></div></div>
              <div class="col-12"><small>Sopir / Kondektur</small><div class="nilai"><?= e($nama_sopir) ?></div></div>
            </div>
          </div>

          <div class="ticket-bawah">
            <div class="text-center mb-3">
              <div class="kode-booking"><?= e($booking['kode_booking']) ?></div>
              <small class="text-muted">Kode pemesanan</small>
            </div>

            <?php if ($dewasa_qr): ?>
              <?php foreach ($dewasa_qr as $px): ?>
                <div class="qr-kartu<?= !empty($px['status_naik']) ? ' sudah-naik' : '' ?>">
                  <div class="nm"><?= e($px['nama']) ?></div>
                  <div class="ks">Kursi <?= e($px['nomor_kursi']) ?><?= !empty($px['status_naik']) ? ' · Sudah naik' : '' ?></div>
                  <div class="qr-pax" data-kode="<?= e($px['kode_tiket']) ?>"></div>
                  <div class="kode"><?= e($px['kode_tiket']) ?></div>
                </div>
              <?php endforeach; ?>
              <?php if ($bayi_qr): ?>
                <div class="qr-bayi">
                  <i class="bi bi-info-circle"></i>
                  Bayi (<?= e(implode(', ', array_column($bayi_qr, 'nama'))) ?>) tidak memerlukan QR sendiri, ikut penumpang dewasa.
                </div>
              <?php endif; ?>
              <div class="text-center mt-2"><small class="text-muted">Setiap penumpang dewasa dipindai satu per satu oleh kondektur.</small></div>
            <?php else: ?>
              <!-- Cadangan untuk pemesanan lama yang belum punya data per penumpang -->
              <div class="text-center">
                <div class="qr-pax" data-kode="<?= e($booking['kode_booking']) ?>" style="display:inline-block;padding:10px;background:#fff;border:1px solid #e1e6f5;border-radius:12px;"></div>
                <div><small class="text-muted">Kondektur akan memindai kode ini untuk validasi.</small></div>
              </div>
            <?php endif; ?>

            <!-- Penampung lama (disembunyikan) supaya script QR bawaan template tidak error -->
            <div id="qrcode" data-kode="<?= e($booking['kode_booking']) ?>" hidden></div>
          </div>
        </div>

        <div class="text-center mt-4 d-print-none">
          <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak / Simpan PDF</button>
          <a href="<?= url('tiket-saya') ?>" class="btn btn-outline-secondary ms-2">Kembali</a>
        </div>
      </div>
    </section>

    <script>
      // Buat QR untuk tiap penumpang setelah semua script (termasuk library QR) selesai dimuat
      window.addEventListener('load', function () {
        document.querySelectorAll('.qr-pax').forEach(function (el) {
          var kode = el.getAttribute('data-kode');
          if (!kode || el.childNodes.length) { return; }
          if (typeof QRCode === 'function') {
            new QRCode(el, { text: kode, width: 180, height: 180 });
          } else {
            el.textContent = kode;   // cadangan kalau library QR tidak ditemukan
          }
        });
      });
    </script>