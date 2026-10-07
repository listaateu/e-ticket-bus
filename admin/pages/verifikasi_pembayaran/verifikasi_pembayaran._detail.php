<?php
// admin/pages/verifikasi_pembayaran/detail.php
// Detail satu pembayaran: info pemesanan, bukti transfer, tombol setujui / tolak.
// Tombolnya mengirim POST ke function/verifikasi_pembayaran.php.

include_once __DIR__ . '/../../database/koneksi.php';

// ===== SESUAIKAN dengan databasemu =====
$STATUS = [
    'pending' => ['label' => 'Menunggu',  'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',     'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',   'badge' => 'failed'],
];
$STATUS_MENUNGGU = 'pending';
// Folder tempat file bukti transfer disimpan (nanti dipakai halaman frontend saat upload)
$FOLDER_BUKTI = '/e-ticket-bus/uploads/bukti/';
// =======================================

$id = (int) ($_GET['id'] ?? 0);

$ambil = $koneksi->prepare(
    "SELECT p.id, p.metode_pembayaran, p.bukti_transfer, p.status,
            b.kode_booking, b.nomor_kursi,
            u.nama, u.email,
            s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.harga,
            bs.plat_nomor, bs.kelas
     FROM payments p
     JOIN bookings  b  ON b.id  = p.booking_id
     JOIN users     u  ON u.id  = b.user_id
     JOIN schedules s  ON s.id  = b.schedule_id
     JOIN buses     bs ON bs.id = s.bus_id
     WHERE p.id = ?"
);
$ambil->bind_param('i', $id);
$ambil->execute();
$p = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$p) {
    echo '<script>window.location.href = "/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran";</script>';
    return;
}

$info    = $STATUS[$p['status']] ?? ['label' => $p['status'], 'badge' => 'pending'];
$bukti   = trim((string) $p['bukti_transfer']);
$ext     = strtolower(pathinfo($bukti, PATHINFO_EXTENSION));
$gambar  = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
$url_bukti = $FOLDER_BUKTI . rawurlencode($bukti);
$pesan   = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Detail Pembayaran</h1>
    <p class="page-subtitle">Cocokkan bukti transfer dengan total yang harus dibayar.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran" class="text-decoration-none text-muted-green">Verifikasi Pembayaran</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Detail</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tanpa_bukti') : ?>
  <div class="alert alert-danger">Pembayaran tidak bisa disetujui karena belum ada bukti transfer.</div>
<?php endif; ?>

<div class="row g-3">

  <!-- info pemesanan -->
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Pemesanan</h5>
      <table class="table table-borderless mb-0">
        <tr><td class="text-muted" style="width:40%">Kode Booking</td><td><strong><?= htmlspecialchars($p['kode_booking']) ?></strong></td></tr>
        <tr><td class="text-muted">Penumpang</td><td><?= htmlspecialchars($p['nama']) ?><br><small class="text-muted"><?= htmlspecialchars($p['email']) ?></small></td></tr>
        <tr><td class="text-muted">Rute</td><td><?= htmlspecialchars($p['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($p['kota_tujuan']) ?></td></tr>
        <tr><td class="text-muted">Berangkat</td><td><?= date('d M Y, H:i', strtotime($p['jam_berangkat'])) ?></td></tr>
        <tr><td class="text-muted">Bus</td><td><?= htmlspecialchars($p['plat_nomor']) ?> (<?= htmlspecialchars($p['kelas']) ?>)</td></tr>
        <tr><td class="text-muted">Nomor Kursi</td><td><?= htmlspecialchars($p['nomor_kursi']) ?></td></tr>
        <tr><td class="text-muted">Total Bayar</td><td><strong>Rp <?= number_format((int) $p['harga'], 0, ',', '.') ?></strong></td></tr>
        <tr><td class="text-muted">Metode</td><td><?= htmlspecialchars($p['metode_pembayaran']) ?></td></tr>
        <tr><td class="text-muted">Status</td><td><span class="badge-table <?= $info['badge'] ?>"><?= htmlspecialchars($info['label']) ?></span></td></tr>
      </table>
    </div>
  </div>

  <!-- bukti transfer + tombol -->
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Bukti Transfer</h5>

      <?php if ($bukti === '') : ?>
        <div class="alert alert-warning mb-3">Penumpang belum mengunggah bukti transfer.</div>
      <?php elseif ($gambar) : ?>
        <a href="<?= $url_bukti ?>" target="_blank" rel="noopener">
          <img src="<?= $url_bukti ?>" alt="Bukti transfer" class="img-fluid rounded border mb-3" style="max-height:420px">
        </a>
      <?php else : ?>
        <p class="mb-3"><a href="<?= $url_bukti ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($bukti) ?></a></p>
      <?php endif; ?>

      <?php if ($p['status'] === $STATUS_MENUNGGU) : ?>
        <div class="d-flex gap-2">
          <form action="/e-ticket-bus/admin/function/verifikasi_pembayaran.php?aksi=setujui" method="post"
                onsubmit="return confirm('Setujui pembayaran ini?')">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" class="btn-custom btn-custom-primary"<?= $bukti === '' ? ' disabled' : '' ?>>
              <i class="bi bi-check-lg"></i> Setujui
            </button>
          </form>
          <form action="/e-ticket-bus/admin/function/verifikasi_pembayaran.php?aksi=tolak" method="post"
                onsubmit="return confirm('Tolak pembayaran ini?')">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" class="btn-custom btn-custom-light">
              <i class="bi bi-x-lg"></i> Tolak
            </button>
          </form>
          <a href="/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran" class="btn-custom btn-custom-light">Kembali</a>
        </div>
      <?php else : ?>
        <p class="text-muted mb-3">Pembayaran ini sudah diproses.</p>
        <a href="/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran" class="btn-custom btn-custom-light">Kembali</a>
      <?php endif; ?>
    </div>
  </div>

</div>