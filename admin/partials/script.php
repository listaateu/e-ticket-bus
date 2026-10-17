<?php defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php ?>
<!-- Alamat dasar project untuk JavaScript (dipakai notif_pembayaran.js) -->
  <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
  <!-- Local Third-Party Libraries Script dependencies -->
  <script src="<?= BASE_URL ?>/admin/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/admin/assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="<?= BASE_URL ?>/admin/assets/libs/flatpickr/flatpickr.min.js"></script>

  <!-- Local dashboard interactions controller -->
  <script src="<?= BASE_URL ?>/admin/assets/js/dashboard.js"></script>

  <!-- Notifikasi pembayaran masuk (tanpa refresh) -->
  <script src="<?= BASE_URL ?>/admin/assets/js/notif_pembayaran.js"></script>