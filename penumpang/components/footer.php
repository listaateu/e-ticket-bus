<?php
/**
 * ============================================================
 * FILE         : penumpang/components/footer.php
 * FUNGSI       : Penutup </main>, footer, tombol scroll-top, dan preloader.
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