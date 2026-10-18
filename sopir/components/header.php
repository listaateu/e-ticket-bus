<?php
/**
 * ============================================================
 * FILE         : sopir/components/header.php
 * FUNGSI       : Navbar atas (logo, menu, nama sopir, tombol Keluar) dan pembuka <main>.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Menu aktif ditentukan variabel $menu_aktif dari index.php. Tambah menu: edit array $menu di file ini.
 *                Tombol Keluar memakai class "js-logout" -> memunculkan modal konfirmasi (modal ada di components/footer.php).
 * ============================================================
 */
?>
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="<?= url('beranda') ?>" class="logo d-flex align-items-center me-auto">
        <h1 class="sitename"><i class="fa-solid fa-bus me-2"></i>E-Ticket Bus <small class="label-peran">Sopir</small></h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <?php
          $menu = ['beranda' => 'Beranda', 'jadwal' => 'Jadwal Saya', 'scan' => 'Scan Tiket', 'profil' => 'Profil'];
          foreach ($menu as $kode => $label):
              $aktif = ($menu_aktif === $kode) ? 'active' : '';
          ?>
            <li><a href="index.php?page=<?= $kode ?>" class="<?= $aktif ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <?php $foto_header = foto_sopir_url((int) $sopir['id']); ?>
      <a href="<?= url('profil') ?>" class="nama-sopir d-none d-md-inline-flex" title="Buka profil">
        <?php if ($foto_header !== ''): ?>
          <img src="<?= e($foto_header) ?>" alt="" class="avatar-mini">
        <?php else: ?>
          <span class="avatar-mini avatar-inisial"><?= e(inisial_nama($sopir['nama'])) ?></span>
        <?php endif; ?>
        <?= e($sopir['nama']) ?>
      </a>
      <a class="btn-getstarted js-logout" href="<?= e(LOGOUT_URL) ?>">Keluar</a>

    </div>
  </header>

  <main class="main">