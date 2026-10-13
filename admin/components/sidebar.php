<?php
$kelas_aktif  = $_GET['kelas'] ?? '';
$jadwal_aktif = ($menu_aktif ?? '') === 'jadwal_dan_rute';
$user_aktif   = in_array($menu_aktif ?? '', ['user', 'sopir'], true);
?>
<style>
  /* Geser sedikit ke kanan saat disorot */
  .sidebar-menu-link {
    transition: transform 0.15s ease, background-color 0.2s ease, color 0.2s ease;
  }
  .sidebar-menu-link:hover {
    transform: translateX(3px);
  }

  /* Panah dropdown ikut berputar saat menu terbuka */
  .sidebar-menu-link[data-bs-toggle="collapse"] {
    cursor: pointer;
  }
  .sidebar-menu-link[data-bs-toggle="collapse"] .dropdown-caret {
    transition: transform 0.3s ease;
  }

  /* Isi dropdown (animasi turun/naik ditangani collapse Bootstrap) */
  .sidebar-submenu {
    margin: 0;
    padding-top: 0.25rem;
    padding-bottom: 0.5rem;
  }
  .sidebar-submenu li {
    margin-bottom: 0.2rem;
  }
  .sidebar-submenu-link {
    border-radius: 8px;
    padding: 0.45rem 0.75rem;
    transition: transform 0.15s ease, background-color 0.2s ease, color 0.2s ease;
  }
  .sidebar-submenu-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.05);
    transform: translateX(3px);
  }
  .sidebar-submenu-link.active {
    color: #fff;
    font-weight: 600;
    background-color: rgba(255, 255, 255, 0.08);
  }
  .sidebar-submenu-link.active::before {
    content: "• ";
    color: var(--brand-lime);
  }
</style>

<!-- Sidebar -->
<div class="sidebar-wrapper" id="sidebar">
    <!-- Brand Logo / Identity -->
    <a href="/e-ticket-bus/admin/index.php" class="sidebar-brand">
      <i class="bi bi-bus-front"></i>
      <span>E-Ticket Bus</span>
    </a>

    <!-- Navigation Menu -->
    <div class="flex-grow-1 overflow-y-auto">
      <!-- Group: Menu -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Menu</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="/e-ticket-bus/admin/index.php" class="sidebar-menu-link<?= ($menu_aktif ?? 'dashboard') === 'dashboard' ? ' active' : '' ?>" title="Dashboard">
              <i class="bi bi-grid-fill"></i>
              <span>Dashboard</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Group: Master Data -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Master Data</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="/e-ticket-bus/admin/index.php?page=bus" class="sidebar-menu-link<?= ($menu_aktif ?? '') === 'bus' ? ' active' : '' ?>" title="Data Bus">
              <i class="bi bi-bus-front-fill"></i>
              <span>Data Bus</span>
            </a>
          </li>

          <!-- Jadwal & Rute (dropdown per kelas, terbuka saat diklik) -->
          <li class="sidebar-menu-item">
            <a href="#submenuJadwal"
               class="sidebar-menu-link<?= $jadwal_aktif ? ' active' : '' ?>"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="<?= $jadwal_aktif ? 'true' : 'false' ?>"
               aria-controls="submenuJadwal"
               title="Jadwal &amp; Rute">
              <i class="bi bi-signpost-split-fill"></i>
              <span>Jadwal &amp; Rute</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>

            <div class="collapse<?= $jadwal_aktif ? ' show' : '' ?>" id="submenuJadwal">
              <ul class="sidebar-submenu">
                <li>
                  <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute"
                     class="sidebar-submenu-link<?= $jadwal_aktif && $kelas_aktif === '' ? ' active' : '' ?>">Semua Kelas</a>
                </li>
                <li>
                  <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&kelas=Eksekutif"
                     class="sidebar-submenu-link<?= $kelas_aktif === 'Eksekutif' ? ' active' : '' ?>">Eksekutif</a>
                </li>
                <li>
                  <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&kelas=Bisnis"
                     class="sidebar-submenu-link<?= $kelas_aktif === 'Bisnis' ? ' active' : '' ?>">Bisnis</a>
                </li>
                <li>
                  <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&kelas=Ekonomi"
                     class="sidebar-submenu-link<?= $kelas_aktif === 'Ekonomi' ? ' active' : '' ?>">Ekonomi</a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Data User (dropdown: Sopir & Penumpang, terbuka saat diklik) -->
          <li class="sidebar-menu-item">
            <a href="#submenuUser"
               class="sidebar-menu-link<?= $user_aktif ? ' active' : '' ?>"
               data-bs-toggle="collapse"
               role="button"
               aria-expanded="<?= $user_aktif ? 'true' : 'false' ?>"
               aria-controls="submenuUser"
               title="Data User">
              <i class="bi bi-people-fill"></i>
              <span>Data User</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>

            <div class="collapse<?= $user_aktif ? ' show' : '' ?>" id="submenuUser">
              <ul class="sidebar-submenu">
                <li>
                  <a href="/e-ticket-bus/admin/index.php?page=sopir"
                     class="sidebar-submenu-link<?= ($menu_aktif ?? '') === 'sopir' ? ' active' : '' ?>">Data Sopir</a>
                </li>
                <li>
                  <a href="/e-ticket-bus/admin/index.php?page=user"
                     class="sidebar-submenu-link<?= ($menu_aktif ?? '') === 'user' ? ' active' : '' ?>">Data Penumpang</a>
                </li>
              </ul>
            </div>
          </li>
        </ul>
      </div>

      <!-- Group: Transaksi -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Transaksi</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran" class="sidebar-menu-link<?= ($menu_aktif ?? '') === 'verifikasi_pembayaran' ? ' active' : '' ?>" title="Verifikasi Pembayaran">
              <i class="bi bi-cash-coin"></i>
              <span>Verifikasi Pembayaran</span>
              <span class="notif-count" id="badge-verifikasi"></span>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="/e-ticket-bus/admin/index.php?page=data_pemesanan" class="sidebar-menu-link<?= ($menu_aktif ?? '') === 'data_pemesanan' ? ' active' : '' ?>" title="Data Pemesanan">
              <i class="bi bi-ticket-perforated-fill"></i>
              <span>Data Pemesanan</span>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Sidebar Profile Card (Dynamic Footer) -->
    <div class="sidebar-profile">
      <img src="/e-ticket-bus/admin/assets/images/avatar.png" alt="Administrator" class="sidebar-profile-img"
        onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=256&auto=format&fit=crop'">
      <div class="sidebar-profile-info">
        <div class="sidebar-profile-name">Administrator</div>
        <div class="sidebar-profile-email">admin@eticketbus.com</div>
      </div>
    </div>
</div>
<!-- Sidebar -->