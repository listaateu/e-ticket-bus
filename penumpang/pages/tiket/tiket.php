<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/tiket/tiket.php
 * FUNGSI       : Halaman TIKET SAYA: daftar pemesanan + filter status.
 * DIPANGGIL DARI: index.php (page=tiket)
 * CATATAN      : URL: index.php?page=tiket
 * ============================================================
 */
$semua  = daftar_tiket_user($conn, $user_id);
$filter = $_GET['status'] ?? 'semua';
$tiket  = $filter === 'semua'
    ? $semua
    : array_values(array_filter($semua, fn($b) => status_tiket($b) === $filter));

$tab = ['semua' => 'Semua', 'belum_bayar' => 'Belum Dibayar', 'pending' => 'Menunggu', 'lunas' => 'Lunas', 'gagal' => 'Ditolak'];
?>
    <?php komponen_page_title('Tiket Saya', 'Riwayat pemesanan dan e-ticket kamu.', ['Tiket Saya' => null]); ?>

    <section id="tiket-saya" class="section">
      <div class="container">
        <ul class="nav nav-pills tab-status mb-4" data-aos="fade-up">
          <?php foreach ($tab as $k => $label): ?>
            <li class="nav-item">
              <a class="nav-link <?= $filter === $k ? 'active' : '' ?>" href="<?= url('tiket-saya', ['status' => $k]) ?>"><?= e($label) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php if (!$tiket): ?>
          <div class="kosong text-center" data-aos="fade-up">
            <i class="fa-solid fa-ticket"></i>
            <h5>Belum ada tiket</h5>
            <p class="text-muted">Yuk pesan tiket perjalananmu sekarang.</p>
            <a href="<?= url('jadwal') ?>" class="btn btn-primary btn-sm">Cari Jadwal</a>
          </div>
        <?php endif; ?>

        <?php foreach ($tiket as $b) { komponen_card_tiket($b); } ?>
      </div>
    </section>