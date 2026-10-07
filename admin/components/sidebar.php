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
          <li class="sidebar-menu-item">
            <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute" class="sidebar-menu-link<?= ($menu_aktif ?? '') === 'jadwal_dan_rute' ? ' active' : '' ?>" title="Jadwal &amp; Rute">
              <i class="bi bi-signpost-split-fill"></i>
              <span>Jadwal &amp; Rute</span>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="/e-ticket-bus/admin/index.php?page=user" class="sidebar-menu-link<?= ($menu_aktif ?? '') === 'user' ? ' active' : '' ?>" title="Data User">
              <i class="bi bi-people-fill"></i>
              <span>Data User</span>
            </a>
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