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
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php?page=bus&amp;aksi=tambah"><i class="bi bi-bus-front"></i> Bus Baru</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php?page=jadwal_dan_rute&amp;aksi=tambah"><i class="bi bi-signpost-split"></i> Jadwal Baru</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php?page=sopir&amp;aksi=tambah"><i class="bi bi-person-badge"></i> Sopir Baru</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php?page=user&amp;aksi=tambah"><i class="bi bi-person-plus"></i> Penumpang Baru</a></li>
          </ul>
        </div>
      </div>

      <!-- Mid navbar: kotak cari. Mencari di halaman daftar yang sedang dibuka (?q=...). -->
      <?php
      // Halaman daftar yang bisa dicari. Dari Dashboard (atau halaman lain), pencarian diarahkan ke Data Pemesanan.
      $placeholder_cari = [
          'bus'                   => 'Cari nama bus, plat nomor, kapasitas, atau kelas...',
          'jadwal_dan_rute'       => 'Cari rute, bus, sopir, jam berangkat, atau harga...',
          'user'                  => 'Cari nama, username, email, atau no. HP penumpang...',
          'sopir'                 => 'Cari nama atau email sopir...',
          'verifikasi_pembayaran' => 'Cari kode booking, penumpang, rute, metode, atau status...',
          'data_pemesanan'        => 'Cari kode booking, penumpang, rute, bus, kursi, atau status...',
      ];
      $menu_aktif   = $menu_aktif ?? '';   // diset di admin/index.php; default kosong bila belum ada
      $sedang_cari  = array_key_exists($menu_aktif, $placeholder_cari);
      $halaman_cari = $sedang_cari ? $menu_aktif : 'data_pemesanan';
      // Pencarian langsung (hasil muncul saat mengetik) hanya di halaman daftar, bukan di form tambah/ubah.
      $cari_langsung = $sedang_cari && ($aksi ?? '') === '';
      $q_sekarang   = ($sedang_cari && is_string($_GET['q'] ?? null)) ? mb_substr(trim($_GET['q']), 0, 100, 'UTF-8') : '';

      // Filter yang ikut dibawa saat mencari (supaya pilihan kelas / tab / status tidak hilang)
      $filter_dibawa = ['bus' => ['kelas'], 'jadwal_dan_rute' => ['kelas', 'tab'],
                        'verifikasi_pembayaran' => ['status'], 'data_pemesanan' => ['status']];
      $url_hapus_q = BASE_URL . '/admin/index.php?page=' . $halaman_cari;
      ?>
      <form method="get" action="<?= BASE_URL ?>/admin/index.php" class="navbar-search-wrapper" role="search">
        <input type="hidden" name="page" value="<?= htmlspecialchars($halaman_cari, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($sedang_cari) : foreach ($filter_dibawa[$halaman_cari] ?? [] as $kunci_filter) :
          $nilai_filter = $_GET[$kunci_filter] ?? '';
          if (is_string($nilai_filter) && $nilai_filter !== '') :
            $url_hapus_q .= '&' . $kunci_filter . '=' . urlencode($nilai_filter); ?>
          <input type="hidden" name="<?= $kunci_filter ?>" value="<?= htmlspecialchars($nilai_filter, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; endforeach; endif; ?>
        <input type="text" name="q" class="navbar-search-input" maxlength="100" autocomplete="off"
               value="<?= htmlspecialchars($q_sekarang, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= htmlspecialchars($placeholder_cari[$halaman_cari], ENT_QUOTES, 'UTF-8') ?>" id="main-search"
               aria-label="Cari data">
        <a href="<?= htmlspecialchars($url_hapus_q, ENT_QUOTES, 'UTF-8') ?>" id="hapus-cari" title="Hapus pencarian" aria-label="Hapus pencarian"
           style="position:absolute;right:3rem;top:50%;transform:translateY(-50%);color:#6b7280;text-decoration:none;line-height:1<?= $q_sekarang === '' ? ';display:none' : '' ?>">
          <i class="bi bi-x-lg"></i>
        </a>
        <button type="submit" class="navbar-search-btn" aria-label="Cari">
          <i class="bi bi-search"></i>
        </button>
      </form>

      <?php if ($cari_langsung) : ?>
      <!-- Pencarian langsung: tiap ketikan memuat ulang isi tabel (tanpa reload halaman) -->
      <script>
      (function () {
        var form  = document.querySelector('.navbar-search-wrapper');
        var input = document.getElementById('main-search');
        var hapus = document.getElementById('hapus-cari');
        var isi   = document.getElementById('isi-halaman');
        if (!form || !input || !isi || !window.fetch || !window.DOMParser || !window.AbortController) return;

        var timer = null, ctrl = null, terakhir = input.value.trim();

        function alamat() {
          var p = new URLSearchParams(new FormData(form));
          var q = input.value.trim();
          if (q === '') { p.delete('q'); } else { p.set('q', q); }
          return form.action + '?' + p.toString();
        }

        function muat() {
          var u = alamat();
          terakhir = input.value.trim();
          if (ctrl) ctrl.abort();
          ctrl = new AbortController();
          isi.style.opacity = '.6';
          fetch(u, { credentials: 'same-origin', signal: ctrl.signal })
            .then(function (r) { return r.text(); })
            .then(function (html) {
              var baru = new DOMParser().parseFromString(html, 'text/html').getElementById('isi-halaman');
              if (!baru) { window.location.href = u; return; }   // mis. sesi habis -> ke halaman login
              isi.innerHTML = baru.innerHTML;
              isi.style.opacity = '';
              history.replaceState(null, '', u);
            })
            .catch(function (e) {
              if (e && e.name === 'AbortError') return;
              isi.style.opacity = '';
            });
        }

        function atur() {
          if (hapus) hapus.style.display = input.value === '' ? 'none' : '';
          clearTimeout(timer);
          if (input.value.trim() !== terakhir) timer = setTimeout(muat, 200);
        }

        input.addEventListener('input', atur);
        form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); muat(); });
        if (hapus) hapus.addEventListener('click', function (e) {
          e.preventDefault();
          input.value = '';
          hapus.style.display = 'none';
          input.focus();
          clearTimeout(timer);
          muat();
        });
      })();
      </script>
      <?php endif; ?>

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
            <img src="<?= htmlspecialchars(foto_admin_url(idLogin()), ENT_QUOTES, 'UTF-8') ?>" alt="Foto profil" class="navbar-profile-img">
            <span class="navbar-profile-name d-none d-md-inline"><?= htmlspecialchars((string) ($_SESSION['nama'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></span>
            <i class="bi bi-chevron-down navbar-profile-caret"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
            <li class="dropdown-header">Selamat datang!</li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php?page=akun"><i class="bi bi-person"></i> Akun Saya</a></li>
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