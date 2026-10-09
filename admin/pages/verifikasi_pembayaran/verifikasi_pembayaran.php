<?php
// admin/pages/verifikasi_pembayaran/verifikasi_pembayaran.php
// Tabel daftar pembayaran + filter status.

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// ===== Nilai status di kolom payments.status (SESUAIKAN dengan databasemu) =====
$STATUS = [
    'pending' => ['label' => 'Menunggu',  'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',     'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',   'badge' => 'failed'],
];
// ================================================================================

$filter = $_GET['status'] ?? '';
if (!isset($STATUS[$filter])) {
    $filter = '';   // kosong = tampilkan semua
}

// Hitung jumlah per status untuk tombol filter
$hitung = ['' => 0];
foreach ($STATUS as $kode => $x) { $hitung[$kode] = 0; }
$q = $koneksi->query("SELECT status, COUNT(*) AS jml FROM payments GROUP BY status");
while ($r = $q->fetch_assoc()) {
    if (isset($hitung[$r['status']])) { $hitung[$r['status']] = (int) $r['jml']; }
    $hitung[''] += (int) $r['jml'];
}

// Total data yang sedang ditampilkan (ikut filter), dipakai untuk pagination
$total = $hitung[$filter];
[$per_halaman, $offset] = paginasi($total);

$sql = "SELECT p.id, p.metode_pembayaran, p.status,
               b.kode_booking, b.nomor_kursi,
               u.nama,
               s.kota_asal, s.kota_tujuan, s.harga
        FROM payments p
        JOIN bookings  b ON b.id = p.booking_id
        JOIN users     u ON u.id = b.user_id
        JOIN schedules s ON s.id = b.schedule_id";
if ($filter !== '') {
    $stmt = $koneksi->prepare($sql . " WHERE p.status = ? ORDER BY p.id DESC LIMIT $per_halaman OFFSET $offset");
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $hasil = $stmt->get_result();
} else {
    // Yang menunggu ditaruh paling atas supaya cepat terlihat
    $hasil = $koneksi->query($sql . " ORDER BY (p.status = 'pending') DESC, p.id DESC LIMIT $per_halaman OFFSET $offset");
}

$pesan = $_GET['pesan'] ?? '';
$url   = '/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Verifikasi Pembayaran</h1>
    <p class="page-subtitle">Periksa bukti transfer penumpang, lalu setujui atau tolak.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Transaksi</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Verifikasi Pembayaran</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'setujui_ok') : ?>
  <div class="alert alert-success">Pembayaran disetujui. Status menjadi lunas.</div>
<?php elseif ($pesan === 'tolak_ok') : ?>
  <div class="alert alert-success">Pembayaran ditolak.</div>
<?php elseif ($pesan === 'sudah_diproses') : ?>
  <div class="alert alert-danger">Pembayaran itu sudah pernah diproses sebelumnya.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel: filter status -->
  <div class="table-header-control">
    <div class="table-pagination-info">Total: <strong><?= $total ?></strong> pembayaran</div>
    <div class="table-filter-group">
      <a href="<?= $url ?>" class="btn-table-action"<?= $filter === '' ? ' style="font-weight:700"' : '' ?>>Semua (<?= $hitung[''] ?>)</a>
      <?php foreach ($STATUS as $kode => $info) : ?>
        <a href="<?= $url ?>&status=<?= $kode ?>" class="btn-table-action"<?= $filter === $kode ? ' style="font-weight:700"' : '' ?>><?= $info['label'] ?> (<?= $hitung[$kode] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode Booking</th>
          <th>Penumpang</th>
          <th>Rute</th>
          <th>Kursi</th>
          <th>Total</th>
          <th>Metode</th>
          <th>Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="9" class="text-center">Belum ada data pembayaran.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($p = $hasil->fetch_assoc()) :
          $info = $STATUS[$p['status']] ?? ['label' => $p['status'], 'badge' => 'pending'];
        ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($p['kode_booking']) ?></td>
            <td><?= htmlspecialchars($p['nama']) ?></td>
            <td><?= htmlspecialchars($p['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($p['kota_tujuan']) ?></td>
            <td><?= htmlspecialchars($p['nomor_kursi']) ?></td>
            <td>Rp <?= number_format((int) $p['harga'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($p['metode_pembayaran']) ?></td>
            <td><span class="badge-table <?= $info['badge'] ?>"><?= htmlspecialchars($info['label']) ?></span></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="<?= $url ?>&aksi=detail&id=<?= (int) $p['id'] ?>" class="table-btn-action" title="Lihat &amp; verifikasi"><i class="bi bi-eye"></i></a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- tombol halaman (muncul hanya kalau data lebih dari 10) -->
  <?php render_pagination($total); ?>

</div>
<!-- kartu tabel -->