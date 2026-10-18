<?php defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php ?>
<!-- Footer -->
<footer class="footer-custom">
      <div class="footer-left">
        <span class="footer-logo">
          <i class="bi bi-bus-front"></i> E-Ticket Bus
        </span>
        <span class="footer-separator">|</span>
        <span class="footer-copy">&copy; <?= date('Y') ?> E-Ticket Bus &bull; Sistem Pemesanan Tiket Bus Online</span>
      </div>
      <div class="footer-right">
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/admin/index.php" class="footer-link">Dashboard</a></li>
          <li><a href="<?= BASE_URL ?>/admin/index.php?page=bus" class="footer-link">Data Bus</a></li>
          <li><a href="<?= BASE_URL ?>/admin/index.php?page=data_pemesanan" class="footer-link">Data Pemesanan</a></li>
          <li><a href="<?= BASE_URL ?>/admin/index.php?page=verifikasi_pembayaran" class="footer-link">Verifikasi Pembayaran</a></li>
          <li><span class="footer-link">Sistem Aktif <span class="status-dot"></span></span></li>
        </ul>
      </div>
    </footer>
<!-- Footer -->