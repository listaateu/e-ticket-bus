<?php
// admin/pages/data_pemesanan/detail.php
// Detail satu pemesanan: data penumpang, perjalanan, kursi, dan status pembayaran.

include_once __DIR__ . '/../../database/koneksi.php';

$STATUS = [
    'belum'   => ['label' => 'Belum Bayar', 'badge' => 'pending'],
    'pending' => ['label' => 'Menunggu',    'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',       'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',     'badge' => 'failed'],
];

$id = (int) ($_GET['id'] ?? 0);

$ambil = $koneksi->prepare(
    "SELECT b.id, b.kode_booking, b.nomor_kursi, b.schedule_id,
            u.nama, u.email,
            s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.harga,
            bs.plat_nomor, bs.kelas, bs.kapasitas_kursi,
            p.id AS payment_id, p.metode_pembayaran, COALESCE(p.status, 'belum') AS status_bayar
     FROM bookings b
     JOIN users     u  ON u.id  = b.user_id
     JOIN schedules s  ON s.id  = b.schedule_id
     JOIN buses     bs ON bs.id = s.bus_id
     LEFT JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = b.id)
     WHERE b.id = ?"
);
$ambil->bind_param('i', $id);
$ambil->execute();
$b = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$b) {
    echo '<script>window.location.href = "/e-ticket-bus/admin/index.php?page=data_pemesanan";</script>';
    return;
}

// Kursi terisi di jadwal yang sama (pembayaran yang ditolak tidak dihitung)
$hitung = $koneksi->prepare(
    "SELECT COUNT(*) AS terisi
     FROM bookings b
     LEFT JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = b.id)
     WHERE b.schedule_id = ? AND COALESCE(p.status, 'belum') <> 'ditolak'"
);
$hitung->bind_param('i', $b['schedule_id']);
$hitung->execute();
$terisi = (int) $hitung->get_result()->fetch_assoc()['terisi'];

$info = $STATUS[$b['status_bayar']] ?? ['label' => $b['status_bayar'], 'badge' => 'pending'];
$kembali = '/e-ticket-bus/admin/index.php?page=data_pemesanan';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Detail Pemesanan</h1>
    <p class="page-subtitle">Kode booking <?= htmlspecialchars($b['kode_booking']) ?></p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= $kembali ?>" class="text-decoration-none text-muted-green">Data Pemesanan</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Detail</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<div class="row g-3">

  <!-- penumpang & perjalanan -->
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Pemesanan</h5>
      <table class="table table-borderless mb-0">
        <tr><td class="text-muted" style="width:40%">Kode Booking</td><td><strong><?= htmlspecialchars($b['kode_booking']) ?></strong></td></tr>
        <tr><td class="text-muted">Penumpang</td><td><?= htmlspecialchars($b['nama']) ?><br><small class="text-muted"><?= htmlspecialchars($b['email']) ?></small></td></tr>
        <tr><td class="text-muted">Rute</td><td><?= htmlspecialchars($b['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($b['kota_tujuan']) ?></td></tr>
        <tr><td class="text-muted">Berangkat</td><td><?= date('d M Y, H:i', strtotime($b['jam_berangkat'])) ?></td></tr>
        <tr><td class="text-muted">Bus</td><td><?= htmlspecialchars($b['plat_nomor']) ?> (<?= htmlspecialchars($b['kelas']) ?>)</td></tr>
        <tr><td class="text-muted">Nomor Kursi</td><td><strong><?= htmlspecialchars($b['nomor_kursi']) ?></strong></td></tr>
        <tr><td class="text-muted">Kursi Terisi</td><td><?= $terisi ?> dari <?= (int) $b['kapasitas_kursi'] ?> kursi</td></tr>
      </table>
    </div>
  </div>

  <!-- pembayaran -->
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Pembayaran</h5>
      <table class="table table-borderless mb-4">
        <tr><td class="text-muted" style="width:40%">Total Bayar</td><td><strong>Rp <?= number_format((int) $b['harga'], 0, ',', '.') ?></strong></td></tr>
        <tr><td class="text-muted">Metode</td><td><?= $b['metode_pembayaran'] ? htmlspecialchars($b['metode_pembayaran']) : '-' ?></td></tr>
        <tr><td class="text-muted">Status</td><td><span class="badge-table <?= $info['badge'] ?>"><?= htmlspecialchars($info['label']) ?></span></td></tr>
      </table>

      <div class="d-flex flex-wrap gap-2">
        <?php if ($b['payment_id'] && $b['status_bayar'] === 'pending') : ?>
          <a href="/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran&aksi=detail&id=<?= (int) $b['payment_id'] ?>" class="btn-custom btn-custom-primary">
            <i class="bi bi-cash-coin"></i> Verifikasi Pembayaran
          </a>
        <?php endif; ?>

        <?php if ($b['status_bayar'] !== 'lunas') : ?>
          <form action="/e-ticket-bus/admin/function/data_pemesanan.php?aksi=hapus" method="post"
                onsubmit="return confirm('Batalkan pemesanan ini?')">
            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
            <button type="submit" class="btn-custom btn-custom-light"><i class="bi bi-trash"></i> Batalkan Pemesanan</button>
          </form>
        <?php endif; ?>

        <a href="<?= $kembali ?>" class="btn-custom btn-custom-light">Kembali</a>
      </div>
    </div>
  </div>

</div>