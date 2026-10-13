<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/tiket/tiket.php
 * FUNGSI       : Halaman TIKET SAYA: daftar pemesanan + filter status.
 * DIPANGGIL DARI: index.php (page=tiket)
 * CATATAN      : URL: index.php?page=tiket
 * ============================================================
 */
$semua  = daftar_tiket_user($conn, $user_id);
$filter = $_GET['status'] ?? 'semua';
// 'lunas' = tiket aktif (bus belum berangkat); 'selesai' = tiket lunas yang busnya sudah berangkat
$tiket  = $filter === 'semua'
    ? $semua
    : array_values(array_filter($semua, function ($b) use ($filter) {
        if ($filter === 'selesai') { return tiket_selesai($b); }
        if ($filter === 'lunas')   { return status_tiket($b) === 'lunas' && !tiket_selesai($b); }
        return status_tiket($b) === $filter;
    }));

$tab = ['semua' => 'Semua', 'belum_bayar' => 'Belum Dibayar', 'pending' => 'Menunggu', 'lunas' => 'Lunas', 'gagal' => 'Ditolak', 'selesai' => 'Selesai'];
?>
    <?php komponen_page_title('Tiket Saya', 'Riwayat pemesanan dan e-ticket kamu.', ['Tiket Saya' => null]); ?>

    <section id="tiket-saya" class="section">
      <div class="container">
        <ul class="nav nav-pills tab-status mb-4" data-aos="fade-up">
          <?php foreach ($tab as $k => $label): ?>
            <li class="nav-item">
              <a class="nav-link <?= $filter === $k ? 'active' : '' ?>" href="<?= url('tiket-saya', ['status' => $k]) ?>"><?= e($label) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php if (!$tiket): ?>
          <div class="kosong text-center" data-aos="fade-up">
            <i class="fa-solid fa-ticket"></i>
            <h5>Belum ada tiket</h5>
            <p class="text-muted">Yuk pesan tiket perjalananmu sekarang.</p>
            <a href="<?= url('jadwal') ?>" class="btn btn-primary btn-sm">Cari Jadwal</a>
          </div>
        <?php endif; ?>

        <?php foreach ($tiket as $b) { komponen_card_tiket($b); } ?>
      </div>
    </section>

    <?php $id_menunggu = array_map('intval', array_column(array_filter($tiket, fn($b) => status_tiket($b) === 'pending'), 'id')); ?>
    <?php if ($id_menunggu): ?>
    <!-- Ada tiket "Menunggu Verifikasi": tanya server tiap 5 detik, muat ulang kalau ada yang berubah. -->
    <script>
    (function () {
      var menunggu = <?= json_encode($id_menunggu) ?>;
      var alamat   = <?= json_encode(url('tiket-saya', ['aksi' => 'cek_status']), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
      var selesai  = false;

      function cek() {
        if (selesai || document.hidden) return;
        fetch(alamat, { credentials: 'same-origin', cache: 'no-store' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var peta = data.status || {};
            var berubah = menunggu.some(function (id) { return (peta[id] || 'hilang') !== 'pending'; });
            if (!berubah || selesai) return;
            selesai = true;

            var d = document.createElement('div');
            d.className = 'alert alert-info shadow';
            d.style.cssText = 'position:fixed;top:90px;left:50%;transform:translateX(-50%);z-index:2000;min-width:280px;max-width:90%;text-align:center;font-weight:600';
            d.textContent = 'Status pembayaranmu diperbarui oleh admin.';
            document.body.appendChild(d);
            setTimeout(function () { location.reload(); }, 1500);
          })
          .catch(function () {});
      }

      setInterval(cek, 5000);
      document.addEventListener('visibilitychange', cek);
    })();
    </script>
    <?php endif; ?>