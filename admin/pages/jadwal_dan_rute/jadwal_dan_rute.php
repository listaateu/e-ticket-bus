<?php
// admin/pages/jadwal_dan_rute/jadwal_dan_rute.php
// Tabel jadwal & rute (bagian R = Read dari CRUD), bisa difilter per kelas bus.

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// Filter kelas dari alamat (?kelas=Bisnis). Nilai di luar daftar dianggap "semua".
$daftar_kelas = ['Eksekutif', 'Bisnis', 'Ekonomi'];
$kelas = $_GET['kelas'] ?? '';
if (!in_array($kelas, $daftar_kelas, true)) {
    $kelas = '';
}

// Hitung total jadwal (sesuai filter)
if ($kelas !== '') {
    $stmt = $koneksi->prepare(
        "SELECT COUNT(*) AS t FROM schedules s JOIN buses b ON b.id = s.bus_id WHERE b.kelas = ?"
    );
    $stmt->bind_param('s', $kelas);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['t'];
} else {
    $total = (int) $koneksi->query("SELECT COUNT(*) AS t FROM schedules")->fetch_assoc()['t'];
}

[$per_halaman, $offset] = paginasi($total);

// Gabungkan dengan tabel buses supaya nama bus, plat nomor & kelas bisa ditampilkan
// ASC = jadwal keberangkatan paling awal di paling atas
if ($kelas !== '') {
    $stmt = $koneksi->prepare(
        "SELECT s.*, b.nama_bus, b.plat_nomor, b.kelas
         FROM schedules s
         JOIN buses b ON b.id = s.bus_id
         WHERE b.kelas = ?
         ORDER BY s.jam_berangkat ASC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('sii', $kelas, $per_halaman, $offset);
    $stmt->execute();
    $hasil = $stmt->get_result();
} else {
    $hasil = $koneksi->query(
        "SELECT s.*, b.nama_bus, b.plat_nomor, b.kelas
         FROM schedules s
         JOIN buses b ON b.id = s.bus_id
         ORDER BY s.jam_berangkat ASC
         LIMIT $per_halaman OFFSET $offset"
    );
}

// Judul mengikuti kelas yang dipilih
$judul   = $kelas !== '' ? "Jadwal & Rute Kelas $kelas" : 'Jadwal & Rute';
$q_kelas = $kelas !== '' ? '&kelas=' . urlencode($kelas) : '';

$pesan = $_GET['pesan'] ?? '';
?>
<!-- warna badge kelas bus: tiap kelas punya warna sendiri -->
<style>
  .badge-kelas {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
  }
  /* titik kecil di depan tulisan, warnanya ikut warna teks */
  .badge-kelas::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }
  .kelas-ekonomi   { background: #dcfce7; color: #15803d; } /* hijau */
  .kelas-bisnis    { background: #dbeafe; color: #1d4ed8; } /* biru */
  .kelas-eksekutif { background: #fef3c7; color: #b45309; } /* emas */
</style>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($judul) ?></h1>
    <p class="page-subtitle">Kelola rute perjalanan, jadwal keberangkatan, dan harga tiket.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Master Data</li>
      <li class="breadcrumb-item active text-main" aria-current="page"><?= htmlspecialchars($judul) ?></li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tambah_ok') : ?>
  <div class="alert alert-success">Jadwal baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Jadwal berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">Jadwal berhasil dihapus.</div>
<?php elseif ($pesan === 'jadwal_dipakai') : ?>
  <div class="alert alert-danger">Jadwal tidak bisa dihapus karena sudah ada pemesanan tiket.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">
      Total: <strong><?= $total ?></strong> jadwal<?= $kelas !== '' ? ' kelas ' . htmlspecialchars($kelas) : '' ?>
    </div>
        <?php if ($kelas === '') : ?>
      <div class="table-filter-group">
        <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&aksi=tambah" class="btn-table-action">
          <i class="bi bi-plus-lg"></i> Tambah Jadwal
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Rute</th>
          <th>Bus</th>
          <th>Sopir</th>
          <th>Jam Berangkat</th>
          <th>Harga</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="7" class="text-center">Belum ada data jadwal<?= $kelas !== '' ? ' untuk kelas ' . htmlspecialchars($kelas) : '' ?>.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($j = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id">
              <?= htmlspecialchars($j['kota_asal']) ?>
              <i class="bi bi-arrow-right mx-1"></i>
              <?= htmlspecialchars($j['kota_tujuan']) ?>
            </td>
            <td>
              <?= htmlspecialchars($j['nama_bus']) ?>
              <small class="text-muted">(<?= htmlspecialchars($j['plat_nomor']) ?>)</small>
              <span class="badge-kelas kelas-<?= strtolower($j['kelas']) ?>"><?= htmlspecialchars($j['kelas']) ?></span>
            </td>
            <td><?= $j['sopir'] !== '' ? htmlspecialchars($j['sopir']) : '-' ?></td>
            <td><?= date('d M Y, H:i', strtotime($j['jam_berangkat'])) ?></td>
            <td>Rp <?= number_format((int) $j['harga'], 0, ',', '.') ?></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&aksi=update&id=<?= (int) $j['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <a href="/e-ticket-bus/admin/function/jadwal_dan_rute.php?aksi=hapus&id=<?= (int) $j['id'] ?><?= $q_kelas ?>" class="table-btn-action delete" title="Hapus" onclick="return confirm('Yakin hapus jadwal ini?')"><i class="bi bi-trash"></i></a>
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