<?php
/**
 * ============================================================
 * FILE         : penumpang/components/header.php
 * FUNGSI       : Navbar atas (logo, menu, tombol Masuk/Daftar/Keluar) dan pembuka <main>.
 * DIPANGGIL DARI: index.php
 * CATATAN      : Menu aktif ditentukan variabel $menu_aktif dari index.php. Tambah menu: edit array $menu di file ini.
 *                Tombol Keluar memakai class "js-logout" -> memunculkan modal konfirmasi (modal ada di components/footer.php).
 * ============================================================
 */
?>
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="<?= url('beranda') ?>" class="logo d-flex align-items-center me-auto">
        <h1 class="sitename"><i class="fa-solid fa-bus me-2"></i>E-Ticket Bus</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <?php
          $menu = ['beranda' => 'Beranda', 'jadwal' => 'Cari Jadwal'];
          if (!empty($sudah_login)) {   // menu khusus penumpang yang sudah login
              $menu += ['tiket' => 'Tiket Saya', 'profil' => 'Profil'];
          }
          foreach ($menu as $kode => $label):
              $aktif = ($menu_aktif === $kode) ? 'active' : '';
          ?>
            <li><a href="index.php?page=<?= $kode ?>" class="<?= $aktif ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <?php if (!empty($sudah_login)): ?>
        <?php $foto_header = foto_penumpang_url((int) ($user['id'] ?? 0)); ?>
        <a href="<?= url('profil') ?>" class="avatar-nav" title="Buka profil">
          <?php if ($foto_header !== ''): ?>
            <img src="<?= e($foto_header) ?>" alt="Profil">
          <?php else: ?>
            <span><?= e(inisial_nama((string) ($user['nama'] ?? ''))) ?></span>
          <?php endif; ?>
        </a>
        <a class="btn-getstarted js-logout" href="<?= e(LOGOUT_URL) ?>">Keluar</a>
      <?php else: ?>
        <a class="btn-getstarted btn-masuk" href="<?= e(LOGIN_URL) ?>">Masuk</a>
        <a class="btn-getstarted" href="<?= e(REGISTER_URL) ?>">Daftar</a>
      <?php endif; ?>

    </div>
  </header>

  <main class="main">