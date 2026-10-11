<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_page_title.php
 * FUNGSI       : Komponen: banner judul halaman + breadcrumb.
 * DIPANGGIL DARI: semua halaman di pages/ kecuali beranda
 * ============================================================
 */
/**
 * Banner judul halaman (gaya template).
 * $crumbs: ['Beranda' => url, 'Halaman ini' => null]
 */
function komponen_page_title(string $judul, string $deskripsi = '', array $crumbs = []): void
{ ?>
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(assets/img/page-title-bg.jpg);">
      <div class="container position-relative">
        <h1><?= e($judul) ?></h1>
        <?php if ($deskripsi !== ''): ?><p><?= e($deskripsi) ?></p><?php endif; ?>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="<?= url('beranda') ?>">Beranda</a></li>
            <?php foreach ($crumbs as $label => $link): ?>
              <?php if ($link): ?>
                <li><a href="<?= e($link) ?>"><?= e($label) ?></a></li>
              <?php else: ?>
                <li class="current"><?= e($label) ?></li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ol>
        </nav>
      </div>
    </div>
<?php }