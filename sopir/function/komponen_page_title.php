<?php
/**
 * ============================================================
 * FILE         : sopir/function/komponen_page_title.php
 * FUNGSI       : Komponen: banner judul halaman + breadcrumb.
 * DIPANGGIL DARI: semua halaman di pages/ kecuali beranda
 * ============================================================
 */
/**
 * $crumbs: ['Jadwal Saya' => url, 'Halaman ini' => null]
 */
function komponen_page_title(string $judul, string $deskripsi = '', array $crumbs = []): void
{ ?>
    <section class="sp-hero datar">
      <div class="container position-relative">
        <ol class="sp-crumb">
          <li><a href="<?= url('beranda') ?>">Beranda</a></li>
          <?php foreach ($crumbs as $label => $link): ?>
            <?php if ($link): ?>
              <li><a href="<?= e($link) ?>"><?= e($label) ?></a></li>
            <?php else: ?>
              <li><?= e($label) ?></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ol>
        <h2><?= e($judul) ?></h2>
        <?php if ($deskripsi !== ''): ?><p><?= e($deskripsi) ?></p><?php endif; ?>
      </div>
    </section>
<?php }