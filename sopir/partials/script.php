<?php
/**
 * ============================================================
 * FILE         : sopir/partials/script.php
 * FUNGSI       : JavaScript template + sopir.js, lalu penutup </body></html>.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Library pemindai kamera (html5-qrcode) hanya dimuat bila $pakai_scanner = true
 *                (halaman Scan). Library dimuat dari internet (CDN), dan kamera browser hanya
 *                bisa dipakai di localhost atau HTTPS.
 * ============================================================
 */
?>
  <!-- Vendor JS Files (main.js template membutuhkan semuanya) -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <?php if (!empty($pakai_scanner)): ?>
  <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <?php endif; ?>

  <!-- Main JS (template) + JS khusus sopir -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/sopir.js"></script>

</body>

</html>