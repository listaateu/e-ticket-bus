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
    $status  = status_tiket($b);
    $selesai = tiket_selesai($b);   // lunas + bus sudah berangkat
    ?>
    <div class="card-tiket<?= $selesai ? ' tiket-selesai' : '' ?>" data-aos="fade-up">
      <div class="row align-items-center gy-3">
        <div class="col-lg-4">
          <div class="kode"><?= e($b['kode_booking']) ?></div>
          <div class="fw-semibold"><?= e($b['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= e($b['kota_tujuan']) ?></div>
          <small class="text-muted"><?= e(tanggal_indo($b['jam_berangkat'])) ?></small>
        </div>
        <div class="col-lg-2 col-6">
          <div class="small text-muted">Bus</div>
          <div><?= e($b['nama_bus']) ?> · <?= e($b['kelas']) ?></div>
        </div>
        <div class="col-lg-2 col-6">
          <div class="small text-muted">Kursi</div>
          <div class="fw-bold fs-5"><?= e($b['nomor_kursi']) ?></div>
          <small class="text-muted"><?= e(teks_penumpang(jumlah_dewasa($b), (int) ($b['jumlah_bayi'] ?? 0))) ?></small>
        </div>
        <div class="col-lg-2 col-6">
          <?= $selesai ? komponen_badge_status('selesai') : komponen_badge_status($status) ?>
          <?php if (tiket_tertinggal($b)): ?>
            <small class="d-block text-danger mt-1"><i class="bi bi-exclamation-triangle-fill"></i> Anda tertinggal bus</small>
          <?php endif; ?>
          <?php if (batas_bayar_aktif($b)): ?>
            <small class="d-block text-danger mt-1"><i class="bi bi-clock-history"></i> Bayar sebelum <?= e(teks_batas_bayar($b['batas_bayar'])) ?></small>
          <?php endif; ?>
          <?php if ($status === 'pending' && !empty($b['sudah_berangkat'])): ?>
            <small class="d-block text-danger mt-1"><i class="bi bi-exclamation-circle"></i> Bus sudah berangkat. Hubungi admin untuk pengembalian dana</small>
          <?php endif; ?>
        </div>
        <div class="col-lg-2 col-6 d-grid">
          <?php if ($selesai): ?>
            <a href="<?= url('pembayaran', ['id' => $b['id']]) ?>" class="btn btn-outline-secondary btn-sm">Lihat Detail</a>
          <?php elseif ($status === 'lunas'): ?>
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