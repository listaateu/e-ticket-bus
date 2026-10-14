<?php
/**
 * ============================================================
 * FILE         : sopir/function/komponen_kartu_jadwal.php
 * FUNGSI       : Komponen: kartu satu jadwal (rute, jam, bus, progres penumpang naik, tombol Manifest & Scan).
 * DIPANGGIL DARI: pages/beranda/beranda.php, pages/jadwal/jadwal.php
 * ============================================================
 */

/** Label status jadwal: [kelas badge bootstrap, teks] */
function status_jadwal(array $j): array
{
    if (!empty($j['sudah_tiba'])) {
        return ['dark', 'Selesai'];
    }
    if (!empty($j['sudah_berangkat'])) {
        return ['info text-dark', 'Dalam perjalanan'];
    }
    return ['success', 'Akan berangkat'];
}

function komponen_kartu_jadwal(array $j): void
{
    [$kelas_status, $label_status] = status_jadwal($j);
    $selesai = !empty($j['sudah_tiba']);
    ?>
    <div class="kartu-jadwal<?= $selesai ? ' selesai' : '' ?>">
      <div class="row align-items-center gy-3">
        <div class="col-lg-4">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-<?= $kelas_status ?> status-badge"><?= e($label_status) ?></span>
            <small class="text-muted">Jadwal #<?= (int) $j['id'] ?></small>
          </div>
          <div class="rute"><?= e($j['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= e($j['kota_tujuan']) ?></div>
          <small class="text-muted">
            <?= e(tanggal_indo($j['jam_berangkat'])) ?>
            <?php if (!empty($j['jam_tiba'])): ?> &middot; tiba <?= e(jam($j['jam_tiba'])) ?><?php endif; ?>
          </small>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small text-muted">Bus</div>
          <div class="fw-semibold"><?= e($j['nama_bus']) ?> &middot; <?= e($j['kelas']) ?></div>
          <small class="text-muted"><?= e($j['plat_nomor']) ?> &middot; <?= (int) $j['kapasitas_kursi'] ?> kursi</small>
        </div>
        <div class="col-lg-3 col-6">
          <?php komponen_progress_naik((int) $j['total_naik'], (int) $j['total_dewasa']); ?>
          <?php if ((int) $j['total_bayi'] > 0): ?>
            <small class="text-muted"><i class="bi bi-person-arms-up"></i> + <?= (int) $j['total_bayi'] ?> bayi</small>
          <?php endif; ?>
        </div>
        <div class="col-lg-2 d-grid gap-2">
          <a href="<?= url('manifest', ['id' => $j['id']]) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-check me-1"></i>Manifest</a>
          <?php if (!$selesai): ?>
            <a href="<?= url('scan', ['id' => $j['id']]) ?>" class="btn btn-primary btn-sm"><i class="bi bi-qr-code-scan me-1"></i>Scan Tiket</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php }