<?php defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php ?>
<!-- Top Navbar -->
<header class="navbar-custom">
      <div class="navbar-left">
        <!-- Desktop sidebar toggle (visible on large screens only) -->
        <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3"
          id="desktop-sidebar-toggle" aria-label="Minimize Sidebar">
          <i class="bi bi-chevron-bar-left"></i>
        </button>
        <!-- Mobile sidebar toggle -->
        <button class="sidebar-toggle-btn me-2" id="sidebar-toggle" aria-label="Toggle Navigation">
          <i class="bi bi-list"></i>
        </button>

        <!-- Quick Actions Dropdown -->
        <div class="dropdown ms-2">
          <button class="btn-quick-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
            id="quick-actions-dropdown">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-quick-action" aria-labelledby="quick-actions-dropdown">
            <li class="dropdown-header">Pintasan Cepat</li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-plus"></i> Bus Baru</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person-plus"></i> Jadwal Baru</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-box-seam"></i> User Baru</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
          </ul>
        </div>
      </div>

      <!-- Mid navbar: search pill -->
      <div class="navbar-search-wrapper">
        <input type="text" class="navbar-search-input" placeholder="Cari bus, jadwal, atau kode booking..." id="main-search">
        <button class="navbar-search-btn" aria-label="Search">
          <i class="bi bi-search"></i>
        </button>
      </div>

      <!-- Right actions -->
      <div class="navbar-actions">
        <!-- Fullscreen Toggle -->
        <button class="navbar-action-btn me-1" aria-label="Toggle Fullscreen" id="btn-fullscreen">
          <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <div class="dropdown">
          <button class="navbar-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="btn-notifications" data-bs-auto-close="outside">
            <i class="bi bi-bell"></i>
            <span class="navbar-action-badge" id="notif-titik" style="display:none"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-notification p-0"
            aria-labelledby="btn-notifications">
            <div class="notification-header">
              <h6 class="notification-title" id="notif-judul">Notifikasi</h6>
            </div>
            <!-- Diisi otomatis oleh assets/js/notif_pembayaran.js -->
            <div class="notification-list" id="notif-daftar">
              <div class="notif-kosong" style="padding:22px 16px;text-align:center;color:#6b7280">Memuat...</div>
            </div>
            <a href="<?= BASE_URL ?>/admin/index.php?page=verifikasi_pembayaran&status=pending" class="notification-footer">Lihat semua yang menunggu</a>
          </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown ms-2">
          <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="profile-dropdown">
            <img src="<?= BASE_URL ?>/admin/assets/images/avatar.png" alt="Profile Image" class="navbar-profile-img">
            <span class="navbar-profile-name d-none d-md-inline"><?= htmlspecialchars((string) ($_SESSION['nama'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></span>
            <i class="bi bi-chevron-down navbar-profile-caret"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
            <li class="dropdown-header">Selamat datang!</li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Akun Saya</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#modalKeluar">
                <i class="bi bi-box-arrow-right"></i> Keluar
              </button>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Konfirmasi keluar -->
    <div class="modal fade" id="modalKeluar" tabindex="-1" aria-labelledby="judulModalKeluar" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
          <div class="modal-body text-center p-4">
            <div class="mb-3 text-danger" style="font-size:2.5rem;line-height:1"><i class="bi bi-box-arrow-right"></i></div>
            <h5 class="mb-2" id="judulModalKeluar">Keluar dari admin?</h5>
            <p class="text-muted small mb-4">Kamu harus masuk lagi untuk membuka halaman admin.</p>
            <form method="post" action="<?= BASE_URL ?>/admin/login/logout.php" class="d-flex gap-2 m-0">
              <?= csrf_field() ?>
              <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-danger flex-fill">Ya, Keluar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
<!-- Top Navbar -->