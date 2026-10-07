<?php
// admin/pages/jadwal_dan_rute/jadwal_dan_rute.php
// Tabel jadwal & rute (bagian R = Read dari CRUD).

include_once __DIR__ . '/../../database/koneksi.php';

// Gabungkan dengan tabel buses supaya plat nomor & kelas bisa ditampilkan
$hasil = $koneksi->query(
    "SELECT s.*, b.plat_nomor, b.kelas
     FROM schedules s
     JOIN buses b ON b.id = s.bus_id
     ORDER BY s.jam_berangkat DESC"
);
$total = $hasil->num_rows;

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Jadwal &amp; Rute</h1>
    <p class="page-subtitle">Kelola rute perjalanan, jadwal keberangkatan, dan harga tiket.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Master Data</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Jadwal &amp; Rute</li>
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
    <div class="table-pagination-info">Total: <strong><?= $total ?></strong> jadwal</div>
    <div class="table-filter-group">
      <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah Jadwal
      </a>
    </div>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Rute</th>
          <th>Bus</th>
          <th>Jam Berangkat</th>
          <th>Harga</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="6" class="text-center">Belum ada data jadwal.</td>
          </tr>
        <?php endif; ?>

        <?php $no = 1; while ($j = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id">
              <?= htmlspecialchars($j['kota_asal']) ?>
              <i class="bi bi-arrow-right mx-1"></i>
              <?= htmlspecialchars($j['kota_tujuan']) ?>
            </td>
            <td>
              <?= htmlspecialchars($j['plat_nomor']) ?>
              <span class="badge-table success"><?= htmlspecialchars($j['kelas']) ?></span>
            </td>
            <td><?= date('d M Y, H:i', strtotime($j['jam_berangkat'])) ?></td>
            <td>Rp <?= number_format((int) $j['harga'], 0, ',', '.') ?></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&aksi=update&id=<?= (int) $j['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <a href="/e-ticket-bus/admin/function/jadwal_dan_rute.php?aksi=hapus&id=<?= (int) $j['id'] ?>" class="table-btn-action delete" title="Hapus" onclick="return confirm('Yakin hapus jadwal ini?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- kartu tabel -->