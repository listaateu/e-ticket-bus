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

  <!-- Auto-refresh: kalau login berganti / logout di tab lain, tab ini ikut refresh sendiri -->
  <script>
  (function () {
    var sesiAwal = <?= json_encode(user_id_login() . '|' . role_login(), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var url = window.location.pathname + '?cek_sesi=1';
    var sedangCek = false;

    function cekSesi() {
      if (sedangCek) { return; }
      sedangCek = true;
      fetch(url, { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d && d.sesi !== sesiAwal) { window.location.reload(); } })
        .catch(function () { /* koneksi putus: abaikan, coba lagi nanti */ })
        .then(function () { sedangCek = false; });
    }

    document.addEventListener('visibilitychange', function () { if (!document.hidden) { cekSesi(); } });
    window.addEventListener('focus', cekSesi);
    window.addEventListener('pageshow', function (e) { if (e.persisted) { cekSesi(); } });
    setInterval(function () { if (!document.hidden) { cekSesi(); } }, 5000);
  })();
  </script>

</body>

</html>