<?php
/**
 * ============================================================
 * FILE         : penumpang/components/footer.php
 * FUNGSI       : Penutup </main>, footer, tombol scroll-top, preloader, dan modal konfirmasi keluar.
 * DIPANGGIL DARI: index.php
 * ============================================================
 */
?>
  </main>

  <footer id="footer" class="footer dark-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-about">
          <a href="<?= url('beranda') ?>" class="logo d-flex align-items-center">
            <span class="sitename">E-Ticket Bus</span>
          </a>
          <p>Pesan tiket bus interkota tanpa antre. Pilih rute, pilih kursi, bayar online, lalu tunjukkan e-ticket kepada kondektur saat naik.</p>
          <div class="social-links d-flex mt-4">
            <a href="#"><i class="bi bi-instagram"></i></a>
            <a href="#"><i class="bi bi-facebook"></i></a>
            <a href="#"><i class="bi bi-whatsapp"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6 footer-links">
          <h4>Menu</h4>
          <ul>
            <li><a href="<?= url('beranda') ?>">Beranda</a></li>
            <li><a href="<?= url('jadwal') ?>">Cari Jadwal</a></li>
            <?php if (!empty($sudah_login)): ?>
            <li><a href="<?= url('tiket-saya') ?>">Tiket Saya</a></li>
            <li><a href="<?= url('profil') ?>">Profil</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="col-lg-4 col-md-12 footer-contact text-center text-md-start">
          <h4>Kontak</h4>
          <p>Terminal Tidar, Magelang</p>
          <p>Jawa Tengah, Indonesia</p>
          <p class="mt-4"><strong>Telepon:</strong> <span>0812-0000-0000</span></p>
          <p><strong>Email:</strong> <span>cs@eticketbus.test</span></p>
        </div>
      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">E-Ticket Bus</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        <!-- Kredit template (jangan dihapus jika memakai versi gratis) -->
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
    #modalKeluar .modal-content { border: 0; border-radius: 16px; }
    #modalKeluar .ikon-keluar { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 50%; background: #e8f4fe; color: #0194f3; display: flex; align-items: center; justify-content: center; font-size: 30px; }
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
            <a href="#" id="tombolKeluarYa" class="btn btn-primary">Ya, Keluar</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Dipasang setelah semua script (termasuk Bootstrap) selesai dimuat
    document.addEventListener('DOMContentLoaded', function () {
      var modalEl = document.getElementById('modalKeluar');
      var tombolYa = document.getElementById('tombolKeluarYa');
      document.querySelectorAll('.js-logout').forEach(function (link) {
        link.addEventListener('click', function (ev) {
          var tujuan = link.getAttribute('href');
          if (window.bootstrap && bootstrap.Modal && modalEl) {
            ev.preventDefault();
            tombolYa.setAttribute('href', tujuan);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
          } else if (!window.confirm('Yakin ingin keluar?')) {   // cadangan kalau Bootstrap tidak termuat
            ev.preventDefault();
          }
        });
      });
    });
  </script>