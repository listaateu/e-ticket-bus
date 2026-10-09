<?php
// admin/pages/data_pemesanan/data_pemesanan.php
// Tabel Data Pemesanan (Read): semua booking + status pembayarannya, dengan filter & pencarian.

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// ===== Status pembayaran (sama dengan payments.status). 'belum' = booking belum punya data pembayaran =====
$STATUS = [
    'belum'   => ['label' => 'Belum Bayar', 'badge' => 'pending'],
    'pending' => ['label' => 'Menunggu',    'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',       'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',     'badge' => 'failed'],
];
// ================================================================================

$filter = $_GET['status'] ?? '';
if (!isset($STATUS[$filter])) {
    $filter = '';
}
$cari = trim($_GET['q'] ?? '');

// Bagian FROM dipakai bersama oleh ringkasan & tabel.
// Ambil pembayaran TERBARU per booking supaya satu booking tidak muncul dobel.
$dari = "FROM bookings b
         JOIN users     u ON u.id = b.user_id
         JOIN schedules s ON s.id = b.schedule_id
         JOIN buses    bs ON bs.id = s.bus_id
         LEFT JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = b.id)";
$status_sql = "COALESCE(p.status, 'belum')";

// ---------- Ringkasan ----------
$hitung = ['' => 0, 'belum' => 0, 'pending' => 0, 'lunas' => 0, 'ditolak' => 0];
$pendapatan = 0;
$q = $koneksi->query("SELECT $status_sql AS st, COUNT(*) AS jml, SUM(s.harga) AS rupiah $dari GROUP BY st");
while ($r = $q->fetch_assoc()) {
    if (isset($hitung[$r['st']])) { $hitung[$r['st']] = (int) $r['jml']; }
    $hitung[''] += (int) $r['jml'];
    if ($r['st'] === 'lunas') { $pendapatan = (int) $r['rupiah']; }
}

// ---------- Syarat filter + cari (dipakai untuk COUNT dan untuk tabel) ----------
$where = " WHERE 1=1";
$tipe  = '';
$param = [];
if ($filter !== '') {
    $where .= " AND $status_sql = ?";
    $tipe .= 's'; $param[] = $filter;
}
if ($cari !== '') {
    $where .= " AND (b.kode_booking LIKE ? OR u.nama LIKE ? OR s.kota_asal LIKE ? OR s.kota_tujuan LIKE ?)";
    $like = '%' . $cari . '%';
    $tipe .= 'ssss'; array_push($param, $like, $like, $like, $like);
}

// ---------- Hitung total hasil (ikut filter & cari) untuk pagination ----------
$stmt_total = $koneksi->prepare("SELECT COUNT(*) AS t $dari $where");
if ($tipe !== '') { $stmt_total->bind_param($tipe, ...$param); }
$stmt_total->execute();
$total = (int) $stmt_total->get_result()->fetch_assoc()['t'];
[$per_halaman, $offset] = paginasi($total);

// ---------- Tabel: ambil 10 baris untuk halaman yang dibuka ----------
$sql = "SELECT b.id, b.kode_booking, b.nomor_kursi,
               u.nama, s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.harga,
               bs.plat_nomor, bs.kelas,
               p.metode_pembayaran, $status_sql AS status_bayar
        $dari $where
        ORDER BY b.id DESC
        LIMIT $per_halaman OFFSET $offset";

$stmt = $koneksi->prepare($sql);
if ($tipe !== '') { $stmt->bind_param($tipe, ...$param); }
$stmt->execute();
$hasil = $stmt->get_result();

$pesan = $_GET['pesan'] ?? '';
$url   = '/e-ticket-bus/admin/index.php?page=data_pemesanan';
$url_q = $cari !== '' ? '&q=' . urlencode($cari) : '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Data Pemesanan</h1>
    <p class="page-subtitle">Pantau semua tiket yang dipesan penumpang beserta status pembayarannya.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Transaksi</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Data Pemesanan</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">Pemesanan berhasil dibatalkan. Kursi bisa dipesan lagi.</div>
<?php elseif ($pesan === 'sudah_lunas') : ?>
  <div class="alert alert-danger">Pemesanan yang sudah lunas tidak bisa dibatalkan.</div>
<?php elseif ($pesan === 'gagal') : ?>
  <div class="alert alert-danger">Pemesanan gagal dibatalkan. Coba lagi.</div>
<?php endif; ?>

<!-- ringkasan -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Total Pemesanan</div>
      <div class="fs-4 fw-bold"><?= $hitung[''] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Menunggu Verifikasi</div>
      <div class="fs-4 fw-bold"><?= $hitung['pending'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Lunas</div>
      <div class="fs-4 fw-bold"><?= $hitung['lunas'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Pendapatan (Lunas)</div>
      <div class="fs-4 fw-bold">Rp <?= number_format($pendapatan, 0, ',', '.') ?></div>
    </div>
  </div>
</div>
<!-- ringkasan -->

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel: filter status + cari -->
  <div class="table-header-control">
    <div class="table-pagination-info">Menampilkan: <strong><?= $total ?></strong> pemesanan</div>
    <div class="table-filter-group">
      <a href="<?= $url . $url_q ?>" class="btn-table-action"<?= $filter === '' ? ' style="font-weight:700"' : '' ?>>Semua (<?= $hitung[''] ?>)</a>
      <?php foreach ($STATUS as $kode => $info) : ?>
        <a href="<?= $url ?>&status=<?= $kode ?><?= $url_q ?>" class="btn-table-action"<?= $filter === $kode ? ' style="font-weight:700"' : '' ?>><?= $info['label'] ?> (<?= $hitung[$kode] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>

  <form method="get" action="/e-ticket-bus/admin/index.php" class="d-flex gap-2 px-3 pb-3">
    <input type="hidden" name="page" value="data_pemesanan">
    <?php if ($filter !== '') : ?><input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($cari) ?>" class="form-control-custom"
           placeholder="Cari kode booking, nama penumpang, atau kota..." style="max-width:420px">
    <button type="submit" class="btn-custom btn-custom-primary"><i class="bi bi-search"></i> Cari</button>
    <?php if ($cari !== '') : ?>
      <a href="<?= $url ?><?= $filter !== '' ? '&status=' . $filter : '' ?>" class="btn-custom btn-custom-light">Reset</a>
    <?php endif; ?>
  </form>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode Booking</th>
          <th>Penumpang</th>
          <th>Rute</th>
          <th>Berangkat</th>
          <th>Bus</th>
          <th>Kursi</th>
          <th>Total</th>
          <th>Status Bayar</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="10" class="text-center">Belum ada data pemesanan.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($b = $hasil->fetch_assoc()) :
          $info = $STATUS[$b['status_bayar']] ?? ['label' => $b['status_bayar'], 'badge' => 'pending'];
        ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($b['kode_booking']) ?></td>
            <td><?= htmlspecialchars($b['nama']) ?></td>
            <td><?= htmlspecialchars($b['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($b['kota_tujuan']) ?></td>
            <td><?= date('d M Y, H:i', strtotime($b['jam_berangkat'])) ?></td>
            <td><?= htmlspecialchars($b['plat_nomor']) ?> <span class="badge-table success"><?= htmlspecialchars($b['kelas']) ?></span></td>
            <td><?= htmlspecialchars($b['nomor_kursi']) ?></td>
            <td>Rp <?= number_format((int) $b['harga'], 0, ',', '.') ?></td>
            <td><span class="badge-table <?= $info['badge'] ?>"><?= htmlspecialchars($info['label']) ?></span></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="<?= $url ?>&aksi=detail&id=<?= (int) $b['id'] ?>" class="table-btn-action" title="Lihat detail"><i class="bi bi-eye"></i></a>
                <?php if ($b['status_bayar'] !== 'lunas') : ?>
                  <form action="/e-ticket-bus/admin/function/data_pemesanan.php?aksi=hapus" method="post" class="d-inline"
                        onsubmit="return confirm('Batalkan pemesanan <?= htmlspecialchars($b['kode_booking'], ENT_QUOTES) ?>?')">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="table-btn-action delete" title="Batalkan pemesanan"><i class="bi bi-trash"></i></button>
                  </form>
                <?php endif; ?>
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