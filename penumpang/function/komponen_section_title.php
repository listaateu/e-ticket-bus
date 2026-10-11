<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_section_title.php
 * FUNGSI       : Komponen: judul kecil sebuah bagian (section).
 * DIPANGGIL DARI: pages/beranda/beranda.php
 * ============================================================
 */
function komponen_section_title(string $kecil, string $judul, string $deskripsi = ''): void
{ ?>
      <div class="container section-title" data-aos="fade-up">
        <span><?= e($kecil) ?></span>
        <h2><?= e($judul) ?></h2>
        <?php if ($deskripsi !== ''): ?><p><?= e($deskripsi) ?></p><?php endif; ?>
      </div>
<?php }