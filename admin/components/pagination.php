<?php
// admin/components/pagination.php
// Bantuan pagination, dipakai oleh semua halaman tabel.

// Halaman yang sedang dibuka (dari ?hal=2 di alamat), minimal 1
function halaman_aktif(): int
{
    return max(1, (int) ($_GET['hal'] ?? 1));
}

// Hitung LIMIT dan OFFSET untuk query. Pakai: [$per_halaman, $offset] = paginasi($total);
function paginasi(int $total, int $per_halaman = 10): array
{
    $total_hal = max(1, (int) ceil($total / $per_halaman));
    $hal       = min(halaman_aktif(), $total_hal);
    return [$per_halaman, ($hal - 1) * $per_halaman];
}

// Tampilkan tombol halaman. Tidak tampil kalau datanya cuma 1 halaman.
function render_pagination(int $total, int $per_halaman = 10): void
{
    $total_hal = (int) ceil($total / $per_halaman);
    if ($total_hal <= 1) {
        return;
    }
    $aktif = min(halaman_aktif(), $total_hal);

    // Bikin link ke halaman lain, parameter lain (page, aksi, dll) tetap dibawa
    $link = function (int $h) {
        $q = $_GET;
        $q['hal'] = $h;
        return '?' . http_build_query($q);
    };

    // Nomor yang ditampilkan: pertama, terakhir, dan 2 di kiri-kanan halaman aktif
    $nomor = [];
    for ($i = 1; $i <= $total_hal; $i++) {
        if ($i == 1 || $i == $total_hal || abs($i - $aktif) <= 2) {
            $nomor[] = $i;
        }
    }
    ?>
    <style>
      .pagination-custom { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 16px 24px; }
      .pagination-custom .info { font-size: 0.85rem; color: #6b7280; }
      .pagination-custom ul { display: flex; gap: 6px; list-style: none; margin: 0; padding: 0; }
      .pagination-custom a, .pagination-custom span.titik {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 36px; height: 36px; padding: 0 10px; border-radius: 10px;
        border: 1px solid #e5e7eb; background: #fff; color: #0b2e22;
        font-size: 0.85rem; font-weight: 600; text-decoration: none;
      }
      .pagination-custom a:hover { background: #f0fdf4; }
      .pagination-custom li.aktif a { background: #0b2e22; color: #fff; border-color: #0b2e22; }
      .pagination-custom li.mati a { opacity: .4; pointer-events: none; }
    </style>
    <div class="pagination-custom">
      <div class="info">Halaman <?= $aktif ?> dari <?= $total_hal ?></div>
      <ul>
        <li class="<?= $aktif <= 1 ? 'mati' : '' ?>"><a href="<?= $link($aktif - 1) ?>">&laquo;</a></li>
        <?php $sebelumnya = 0; foreach ($nomor as $n) : ?>
          <?php if ($n - $sebelumnya > 1) : ?><li><span class="titik">...</span></li><?php endif; ?>
          <li class="<?= $n == $aktif ? 'aktif' : '' ?>"><a href="<?= $link($n) ?>"><?= $n ?></a></li>
        <?php $sebelumnya = $n; endforeach; ?>
        <li class="<?= $aktif >= $total_hal ? 'mati' : '' ?>"><a href="<?= $link($aktif + 1) ?>">&raquo;</a></li>
      </ul>
    </div>
    <?php
}