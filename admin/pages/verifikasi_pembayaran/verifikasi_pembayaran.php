<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/verifikasi_pembayaran/verifikasi_pembayaran.php
// Tabel daftar pembayaran + filter status.
// Kolom Total Transfer = harga tiket + kode unik (angka yang harus muncul di mutasi rekening).

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';
include_once __DIR__ . '/../../components/pencarian.php';

// ===== Nilai status di kolom payments.status (SESUAIKAN dengan databasemu) =====
$STATUS = [
    'pending' => ['label' => 'Menunggu',  'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',     'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',   'badge' => 'failed'],
];
// ================================================================================

$filter = param_enum('status', array_keys($STATUS));   // hanya nilai dari daftar resmi, kosong = tampilkan semua

// Pencarian dari kotak cari di topbar (?q=): kode booking, penumpang, rute, kursi, metode, status, total, atau jam berangkat
$cari = param_cari();
$label_sql = 'CASE p.status';                                  // "Menunggu" / "Lunas" / "Ditolak" (tulisan yang tampil di tabel)
foreach ($STATUS as $kode => $x) { $label_sql .= " WHEN '$kode' THEN '" . $x['label'] . "'"; }
$label_sql .= ' END';
$kolom_cari = ['b.kode_booking', 'u.nama', 'u.email', 's.kota_asal', 's.kota_tujuan', 'b.nomor_kursi', 'p.metode_pembayaran', 'p.status', $label_sql, "IF(s.jam_berangkat < NOW() AND p.status = 'pending', 'Bus sudah berangkat', '')", 'CAST(s.harga * b.jumlah_dewasa + COALESCE(b.kode_unik, 0) AS CHAR)', "DATE_FORMAT(s.jam_berangkat, '%d %b %Y, %H:%i')"];
$tipe_c  = '';
$param_c = [];
$syarat_cari = syarat_cari($kolom_cari, $cari, $tipe_c, $param_c);

// Bagian FROM dipakai bersama oleh hitungan tombol filter & tabel
$dari = "FROM payments p
         JOIN bookings  b ON b.id = p.booking_id
         JOIN users     u ON u.id = b.user_id
         JOIN schedules s ON s.id = b.schedule_id";

// Hitung jumlah per status untuk tombol filter (ikut pencarian)
$hitung = ['' => 0];
foreach ($STATUS as $kode => $x) { $hitung[$kode] = 0; }
$st_hitung = $koneksi->prepare("SELECT p.status AS status, COUNT(*) AS jml $dari WHERE 1=1 $syarat_cari GROUP BY p.status");
if ($tipe_c !== '') { $st_hitung->bind_param($tipe_c, ...$param_c); }
$st_hitung->execute();
$q = $st_hitung->get_result();
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
$tipe  = $tipe_c;
$param = $param_c;
$where = ' WHERE 1=1' . $syarat_cari;
if ($filter !== '') {
    $where .= ' AND p.status = ?';
    $tipe .= 's'; $param[] = $filter;
}
// Kalau tidak difilter, yang menunggu ditaruh paling atas supaya cepat terlihat
$urut = $filter !== '' ? 'p.id DESC' : "(p.status = 'pending') DESC, p.id DESC";
$stmt = $koneksi->prepare($sql . $where . " ORDER BY $urut LIMIT ? OFFSET ?");
$tipe .= 'ii'; $param[] = (int) $per_halaman; $param[] = (int) $offset;
$stmt->bind_param($tipe, ...$param);
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
$url_q = $cari !== '' ? '&q=' . urlencode($cari) : '';   // tombol filter ikut membawa pencarian
$url_reset = $url . ($filter !== '' ? '&status=' . $filter : '');   // alamat tanpa pencarian
?>
<!-- style tombol filter aktif (boleh dipindah ke file CSS admin nanti) -->
<style>
  .table-filter-group .btn-table-action.active {
    background: #B94E77;      /* samakan dengan warna tombol "Tambah" */
    border-color: #B94E77;
    color: #fff;
    font-weight: 600;
  }
</style>

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
      <a href="<?= $url . $url_q ?>" class="btn-table-action<?= $filter === '' ? ' active' : '' ?>">Semua (<?= $hitung[''] ?>)</a>
      <?php foreach ($STATUS as $kode => $info) : ?>
        <a href="<?= $url ?>&status=<?= $kode ?><?= $url_q ?>" class="btn-table-action<?= $filter === $kode ? ' active' : '' ?>"><?= $info['label'] ?> (<?= $hitung[$kode] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php render_info_cari($cari, $total, $url_reset); ?>

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
            <td colspan="9" class="text-center"><?= $cari !== '' ? 'Tidak ada pembayaran yang cocok dengan pencarian.' : 'Belum ada data pembayaran.' ?></td>
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