<?php
/**
 * ============================================================
 * FILE         : penumpang/partials/script.php
 * FUNGSI       : JavaScript template + penumpang.js, lalu penutup </body></html>.
 * DIPANGGIL DARI: index.php
 * CATATAN      : Library QR hanya dimuat bila $pakai_qr = true (halaman e_ticket).
 * ============================================================
 */
?>
  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <?php if (!empty($pakai_qr)): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <?php endif; ?>

  <!-- Main JS (template) + JS khusus penumpang -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/penumpang.js"></script>

</body>

</html>