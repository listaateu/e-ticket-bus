<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/verifikasi_pembayaran/verifikasi_pembayaran.php
// Tabel daftar pembayaran + filter status.
// Kolom Total Transfer = harga tiket + kode unik (angka yang harus muncul di mutasi rekening).

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// ===== Nilai status di kolom payments.status (SESUAIKAN dengan databasemu) =====
$STATUS = [
    'pending' => ['label' => 'Menunggu',  'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',     'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',   'badge' => 'failed'],
];
// ================================================================================

$filter = param_enum('status', array_keys($STATUS));   // hanya nilai dari daftar resmi, kosong = tampilkan semua

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

// CATATAN: b.jumlah_bayi = nama kolom jumlah bayi di tabel bookings.
// Kalau di databasemu namanya beda, ganti di sini dan di bagian tabel bawah.
$sql = "SELECT p.id, p.metode_pembayaran, p.status,
               b.kode_booking, b.nomor_kursi, b.jumlah_dewasa, b.jumlah_bayi,
               u.nama,
               s.kota_asal, s.kota_tujuan,
               (s.jam_berangkat < NOW()) AS sudah_berangkat,
               s.harga * b.jumlah_dewasa + b.kode_unik AS total_transfer
        FROM payments p
        JOIN bookings  b ON b.id = p.booking_id
        JOIN users     u ON u.id = b.user_id
        JOIN schedules s ON s.id = b.schedule_id";
if ($filter !== '') {
    $stmt = $koneksi->prepare($sql . " WHERE p.status = ? ORDER BY p.id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('sii', $filter, $per_halaman, $offset);
} else {
    // Yang menunggu ditaruh paling atas supaya cepat terlihat
    $stmt = $koneksi->prepare($sql . " ORDER BY (p.status = 'pending') DESC, p.id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $per_halaman, $offset);
}
$stmt->execute();
$hasil = $stmt->get_result();

// Pembayaran yang masih "Menunggu" padahal bus-nya sudah berangkat: perlu refund manual, jangan disetujui
$perlu_refund = (int) $koneksi->query(
    "SELECT COUNT(*) AS jml
     FROM payments p
     JOIN bookings  b ON b.id = p.booking_id
     JOIN schedules s ON s.id = b.schedule_id
     WHERE p.status = 'pending' AND s.jam_berangkat < NOW()"
)->fetch_assoc()['jml'];

$pesan = $_GET['pesan'] ?? '';
$url   = BASE_URL . '/admin/index.php?page=verifikasi_pembayaran';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Verifikasi Pembayaran</h1>
    <p class="page-subtitle">Periksa bukti transfer penumpang, lalu setujui atau tolak.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Transaksi</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Verifikasi Pembayaran</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($perlu_refund > 0) : ?>
  <div class="alert alert-danger">
    <strong><?= $perlu_refund ?> pembayaran</strong> masih menunggu verifikasi padahal bus-nya sudah berangkat.
    Jangan disetujui: kembalikan dana ke penumpang secara manual, lalu tolak pembayarannya.
    Cari yang bertanda "Bus sudah berangkat" di tabel bawah.
  </div>
<?php endif; ?>

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
          <th>Total Transfer</th>
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
          $jml_dewasa = (int) $p['jumlah_dewasa'];
          $jml_bayi   = (int) $p['jumlah_bayi'];
        ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($p['kode_booking']) ?></td>
            <td>
              <?= htmlspecialchars($p['nama']) ?>
              <br><small class="text-muted"><?= $jml_dewasa ?> dewasa<?= $jml_bayi > 0 ? ', ' . $jml_bayi . ' bayi' : '' ?></small>
            </td>
            <td><?= htmlspecialchars($p['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($p['kota_tujuan']) ?></td>
            <td><?= htmlspecialchars($p['nomor_kursi']) ?></td>
            <td><strong>Rp <?= number_format((int) round((float) $p['total_transfer']), 0, ',', '.') ?></strong></td>
            <td><?= htmlspecialchars($p['metode_pembayaran']) ?></td>
            <td>
              <span class="badge-table <?= $info['badge'] ?>"><?= htmlspecialchars($info['label']) ?></span>
              <?php if ($p['status'] === 'pending' && !empty($p['sudah_berangkat'])) : ?>
                <br><span class="badge-table failed">Bus sudah berangkat</span>
              <?php endif; ?>
            </td>
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