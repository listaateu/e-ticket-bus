<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/tiket/e_ticket.php
 * FUNGSI       : Halaman E-TICKET dengan QR (hanya bila status pembayaran lunas).
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
$pakai_qr = true;
?>
    <?php komponen_page_title('E-Ticket', 'Tunjukkan tiket ini kepada kondektur.', ['Tiket Saya' => url('tiket-saya'), 'E-Ticket' => null]); ?>

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
              <div class="col-6"><small>Penumpang</small><div class="nilai"><?= e($booking['nama_penumpang']) ?></div></div>
              <div class="col-6"><small>Nomor Kursi</small><div class="nilai"><?= e($booking['nomor_kursi']) ?></div></div>
              <div class="col-6"><small>Berangkat</small><div class="nilai"><?= e(tanggal_indo($booking['jam_berangkat'], false)) ?></div></div>
              <div class="col-6"><small>Jam</small><div class="nilai"><?= e(jam($booking['jam_berangkat'])) ?> WIB</div></div>
              <div class="col-6"><small>Bus</small><div class="nilai"><?= e($booking['nama_bus']) ?> · <?= e($booking['kelas']) ?></div></div>
              <div class="col-6"><small>Plat Nomor</small><div class="nilai"><?= e($booking['plat_nomor']) ?></div></div>
            </div>
          </div>

          <div class="ticket-bawah text-center">
            <div id="qrcode" data-kode="<?= e($booking['kode_booking']) ?>"></div>
            <div class="kode-booking"><?= e($booking['kode_booking']) ?></div>
            <small class="text-muted">Kondektur akan memindai kode ini untuk validasi.</small>
          </div>
        </div>

        <div class="text-center mt-4 d-print-none">
          <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak / Simpan PDF</button>
          <a href="<?= url('tiket-saya') ?>" class="btn btn-outline-secondary ms-2">Kembali</a>
        </div>
      </div>
    </section>