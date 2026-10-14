<?php
/**
 * ============================================================
 * FILE         : sopir/pages/jadwal/jadwal.php
 * FUNGSI       : Daftar jadwal keberangkatan milik sopir (yang ditugaskan admin), dengan tab
 *                Akan Datang / Hari Ini / Selesai. Tiap jadwal punya tombol Manifest & Scan Tiket.
 * DIPANGGIL DARI: sopir/index.php (page=jadwal)
 * CATATAN      : URL: index.php?page=jadwal&tampil=akan|hari_ini|selesai
 * ============================================================
 */
$tab = [
    'akan'     => 'Akan Datang',
    'hari_ini' => 'Hari Ini',
    'selesai'  => 'Selesai',
];
$tampil = $_GET['tampil'] ?? 'akan';
if (!isset($tab[$tampil])) {
    $tampil = 'akan';
}
$daftar = jadwal_sopir($conn, $sopir['nama'], $tampil);
?>
    <?php komponen_page_title('Jadwal Saya', 'Keberangkatan yang ditugaskan admin kepada Anda.', ['Jadwal Saya' => null]); ?>

    <section id="jadwal" class="section">
      <div class="container">

        <ul class="nav nav-pills tab-status mb-4">
          <?php foreach ($tab as $kode => $label): ?>
            <li class="nav-item">
              <a class="nav-link<?= $tampil === $kode ? ' active' : '' ?>" href="<?= url('jadwal', ['tampil' => $kode]) ?>"><?= e($label) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php if ($daftar): ?>
          <?php foreach ($daftar as $j) { komponen_kartu_jadwal($j); } ?>
          <?php if (count($daftar) >= 60): ?>
            <p class="text-muted small text-center mt-3">Menampilkan 60 jadwal pertama.</p>
          <?php endif; ?>
        <?php else: ?>
          <div class="kosong text-center">
            <i class="bi bi-calendar-x d-block"></i>
            <h5>Tidak ada jadwal di tab ini</h5>
            <p class="text-muted mb-0">
              <?= $tampil === 'selesai' ? 'Belum ada perjalanan yang selesai.' : 'Jadwal ditentukan admin. Hubungi admin bila seharusnya Anda bertugas.' ?>
            </p>
          </div>
        <?php endif; ?>

      </div>
    </section>