<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_card_tiket.php
 * FUNGSI       : Komponen: kartu satu tiket di halaman Tiket Saya.
 * DIPANGGIL DARI: pages/tiket/tiket.php
 * ============================================================
 */
/** Kartu satu tiket di halaman "Tiket Saya" */
function komponen_card_tiket(array $b): void
{
    $status = status_tiket($b);
    ?>
    <div class="card-tiket" data-aos="fade-up">
      <div class="row align-items-center gy-3">
        <div class="col-lg-4">
          <div class="kode"><?= e($b['kode_booking']) ?></div>
          <div class="fw-semibold"><?= e($b['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= e($b['kota_tujuan']) ?></div>
          <small class="text-muted"><?= e(tanggal_indo($b['jam_berangkat'])) ?></small>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small text-muted">Bus</div>
          <div><?= e($b['nama_bus']) ?> · <?= e($b['kelas']) ?></div>
        </div>
        <div class="col-lg-1 col-6">
          <div class="small text-muted">Kursi</div>
          <div class="fw-bold fs-5"><?= e($b['nomor_kursi']) ?></div>
        </div>
        <div class="col-lg-2 col-6">
          <?= komponen_badge_status($status) ?>
          <?php if (batas_bayar_aktif($b)): ?>
            <small class="d-block text-danger mt-1"><i class="bi bi-clock-history"></i> Bayar sebelum <?= e(teks_batas_bayar($b['batas_bayar'])) ?></small>
          <?php endif; ?>
        </div>
        <div class="col-lg-2 col-6 d-grid">
          <?php if ($status === 'lunas'): ?>
            <a href="<?= url('e-ticket', ['id' => $b['id']]) ?>" class="btn btn-primary btn-sm"><i class="bi bi-qr-code me-1"></i>E-Ticket</a>
          <?php elseif ($status === 'pending'): ?>
            <a href="<?= url('pembayaran', ['id' => $b['id']]) ?>" class="btn btn-outline-secondary btn-sm">Lihat Status</a>
          <?php else: ?>
            <a href="<?= url('pembayaran', ['id' => $b['id']]) ?>" class="btn btn-warning btn-sm"><?= $status === 'gagal' ? 'Bayar Ulang' : 'Bayar Sekarang' ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php }