<?php
/**
 * ============================================================
 * FILE         : sopir/components/footer.php
 * FUNGSI       : Penutup </main>, footer, tombol scroll-top, preloader, dan modal konfirmasi keluar.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : #scroll-top dan #preloader WAJIB ada, karena main.js template memakainya.
 * ============================================================
 */
?>
  </main>

  <footer id="footer" class="footer footer-soft">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-6 col-md-12 footer-about">
          <a href="<?= url('beranda') ?>" class="logo d-flex align-items-center">
            <span class="sitename">E-Ticket Bus &middot; Sopir</span>
          </a>
          <p>Periksa manifest penumpang, pindai e-ticket sebelum berangkat, dan catat penumpang yang sudah naik. Datanya langsung terlihat oleh admin.</p>
        </div>

        <div class="col-lg-3 col-6 footer-links">
          <h4>Menu</h4>
          <ul>
            <li><a href="<?= url('beranda') ?>">Beranda</a></li>
            <li><a href="<?= url('jadwal') ?>">Jadwal Saya</a></li>
            <li><a href="<?= url('scan') ?>">Scan Tiket</a></li>
            <li><a href="<?= url('profil') ?>">Profil</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-6 footer-contact">
          <h4>Bantuan</h4>
          <p>Terminal Tidar, Magelang</p>
          <p class="mt-3"><strong>Admin:</strong> <span>0812-0000-0000</span></p>
        </div>
      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">E-Ticket Bus</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Modal konfirmasi keluar -->
  <style>
    #modalKeluar .modal-content { border: 0; border-radius: 22px; box-shadow: 0 20px 50px rgba(63,127,196,.25); }
    #modalKeluar .ikon-keluar { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 50%; background: #e7f0fb; color: #3f7fc4; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    #modalKeluar .btn { border-radius: 10px; font-weight: 600; padding: 9px 22px; }
  </style>
  <div class="modal fade" id="modalKeluar" tabindex="-1" aria-labelledby="judulKeluar" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content text-center p-3">
        <div class="modal-body">
          <div class="ikon-keluar"><i class="bi bi-box-arrow-right"></i></div>
          <h5 id="judulKeluar" class="fw-bold mb-2">Yakin ingin keluar?</h5>
          <p class="text-muted mb-4">Kamu akan keluar dari akunmu dan perlu masuk lagi untuk melanjutkan.</p>
          <div class="d-flex gap-2 justify-content-center">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" id="tombolKeluarYa" class="btn btn-primary">Ya, Keluar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Keluar wajib lewat POST + token CSRF (link biasa tidak bisa memaksa pengguna keluar) -->
  <form id="formKeluar" method="post" action="<?= e(LOGOUT_URL) ?>" class="d-none">
    <?= csrf_field() ?>
  </form>
  <script>
    // Dipasang setelah semua script (termasuk Bootstrap) selesai dimuat
    document.addEventListener('DOMContentLoaded', function () {
      var modalEl = document.getElementById('modalKeluar');
      var tombolYa = document.getElementById('tombolKeluarYa');
      var formKeluar = document.getElementById('formKeluar');
      tombolYa.addEventListener('click', function () { formKeluar.submit(); });
      document.querySelectorAll('.js-logout').forEach(function (link) {
        link.addEventListener('click', function (ev) {
          ev.preventDefault();   // jangan membuka alamat logout lewat GET
          if (window.bootstrap && bootstrap.Modal && modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
          } else if (window.confirm('Yakin ingin keluar?')) {   // cadangan kalau Bootstrap tidak termuat
            formKeluar.submit();
          }
        });
      });
    });
  </script>